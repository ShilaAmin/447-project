<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

use App\Models\ExchangeRequest;
use App\Models\Item;
use App\Models\Notification;
use App\Models\User;
use App\Models\TradeOffer;

class ExchangeRequestController extends Controller
{
    // Store a trade request (NO contact info in notification)
    public function store(Request $request, $id)
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'You must be logged in to request a trade.');
        }

        $userId = session('user_id');

        $request->validate([
            'offered_item_id' => 'nullable|exists:items,id'
        ]);

        // Load item WITH owner (user)
        $item = Item::with('user')->find($id);
        if (!$item) {
            return redirect()->back()->with('error', 'Item not found.');
        }

        if ($item->user_id == $userId) {
            return redirect()->back()->with('error', 'You cannot request your own item.');
        }

        // Prevent duplicate request
        $exists = ExchangeRequest::where('item_id', $id)
            ->where('requested_by', $userId)
            ->first();

        if ($exists) {
            return redirect()->back()->with('error', 'You have already requested this item.');
        }

        $exchangeRequest = ExchangeRequest::create([
            'item_id' => $id,
            'requested_by' => $userId,
            'offered_item_id' => $request->offered_item_id ?? null,
            'status' => 'pending',
        ]);

        // 🔔 Notify the item owner about the new request (NO personal contact details here)
        $requester = User::find($userId);
        $offeredTitle = null;
        if (!empty($exchangeRequest->offered_item_id)) {
            $offered = Item::find($exchangeRequest->offered_item_id);
            $offeredTitle = $offered?->title;
        }

        $message = "New trade request for '{$item->title}' from {$requester->name}.";
        if ($offeredTitle) {
            $message .= " Offered item: {$offeredTitle}.";
        }
        $message .= " Open your Requests page to review.";

        Notification::create([
            'user_id' => $item->user->id, // owner
            'message' => $message,
            'read'    => 0,
        ]);

        return redirect()->back()->with('success', 'Trade request sent successfully!');
    }

    // List incoming requests for owner
    public function index()
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'You must be logged in to view requests.');
        }

        $userId = session('user_id');

        $requests = ExchangeRequest::whereHas('item', function($q) use ($userId) {
            $q->where('user_id', $userId);
        })->with(['item', 'requester', 'offeredItem'])
          ->orderBy('created_at', 'desc')
          ->get();

        return view('requests.index', compact('requests'));
    }

    public function myRequests()
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'You must be logged in to view your requests.');
        }

        $userId = session('user_id');

        // Requests that *you* sent
        $requests = \App\Models\ExchangeRequest::with(['item', 'offeredItem'])
            ->where('requested_by', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('requests.my', compact('requests'));
    }

    // Accept a request → notify requester WITH OWNER CONTACT INFO (contact shared here only)
    public function accept($id)
    {
        // Load the request with item & owner
        $exchangeRequest = ExchangeRequest::with(['item.user'])->findOrFail($id);

        // Only the item owner can accept
        if (!session()->has('user_id') || $exchangeRequest->item->user_id != session('user_id')) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        // Update status (+ timestamp column if you added it)
        $exchangeRequest->status = 'accepted';
        if (Schema::hasColumn('exchange_requests', 'accepted_at')) {
            $exchangeRequest->accepted_at = now();
        }
        $exchangeRequest->save();

        // Owner contact details
        $owner = $exchangeRequest->item->user;
        $ownerName  = $owner->name  ?? 'Owner';
        $ownerEmail = $owner->email ?? '—';
        $ownerPhone = $owner->phone ?? '—';

        // Notify requester including contact info
        \App\Models\Notification::create([
            'user_id' => $exchangeRequest->requested_by,
            'message' =>
                "Your offer for '{$exchangeRequest->item->title}' was ACCEPTED.\n".
                "Owner: {$ownerName}\n".
                "Email: {$ownerEmail}\n".
                "Phone: {$ownerPhone}",
            'read' => 0,
        ]);

        return redirect()->back()->with('success', 'Request accepted and requester notified with contact info.');
    }

    // ✅ Mark request as completed → keep the request row (for stats), remove item from listings
    public function complete($id)
    {
        $exchangeRequest = ExchangeRequest::with('item.user')->findOrFail($id);

        // Only item owner can complete
        if (!session()->has('user_id') || $exchangeRequest->item->user_id != session('user_id')) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        // Mark the request as completed and set completed_at
        $exchangeRequest->status = 'completed';
        if (Schema::hasColumn('exchange_requests', 'completed_at')) {
            $exchangeRequest->completed_at = now();
        }
        $exchangeRequest->save();

        $owner = $exchangeRequest->item->user;

        Notification::create([
            'user_id' => $exchangeRequest->requested_by,
            'message' => "Trade for '{$exchangeRequest->item->title}' has been COMPLETED by {$owner->name}. Item removed from listings.",
            'read'    => 0,
        ]);

        // Remove the item from listings, but KEEP the exchange request record
        // Because we changed FK to ON DELETE SET NULL, the request survives.
        if ($exchangeRequest->item) {
            $exchangeRequest->item->delete();
        }

        return redirect()->back()->with('success', 'Trade completed, item removed, requester notified.');
    }

    // Decline request → notify requester (no contact info)
    public function decline($id)
    {
        $exchangeRequest = ExchangeRequest::with(['item.user', 'requester'])->find($id);
        if (!$exchangeRequest) {
            return redirect()->back()->with('error', 'Request not found.');
        }

        // Only owner can decline
        if (!session()->has('user_id') || $exchangeRequest->item->user_id != session('user_id')) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        $exchangeRequest->status = 'declined';
        $exchangeRequest->save();

        Notification::create([
            'user_id' => $exchangeRequest->requested_by,
            'message' => "Your trade request for '{$exchangeRequest->item->title}' has been DECLINED by {$exchangeRequest->item->user->name}.",
            'read'    => 0,
        ]);

        return redirect()->back()->with('success', 'Request declined and requester notified!');
    }

    /* ==========================
       Negotiation (Offers & Counters)
       ========================== */

    // open the negotiation thread view
    public function negotiate($id)
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'You must be logged in.');
        }

        $userId = session('user_id');

        $exchangeRequest = ExchangeRequest::with(['item.user', 'requester', 'offeredItem'])->findOrFail($id);

        // Only the requester or the item owner can view the negotiation
        if ($exchangeRequest->requested_by !== $userId && $exchangeRequest->item->user_id !== $userId) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        // guard: if table doesn't exist yet, show helpful message
        if (!Schema::hasTable('trade_offers')) {
            return redirect()->back()->with('error', 'Negotiation feature not initialized. Please run: php artisan migrate');
        }

        $offers = TradeOffer::with(['fromUser', 'toUser', 'offeredItem'])
            ->where('exchange_request_id', $exchangeRequest->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $myItems = Item::where('user_id', $userId)->orderBy('created_at', 'desc')->get();

        return view('requests.negotiate', compact('exchangeRequest', 'offers', 'myItems', 'userId'));
    }

    // send a new offer or counter-offer
    public function sendOffer(Request $request, $id)
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'You must be logged in.');
        }
        $userId = session('user_id');

        $exchangeRequest = ExchangeRequest::with(['item.user', 'requester'])->findOrFail($id);

        // Only requester or owner can send offers
        $isRequester = ($exchangeRequest->requested_by === $userId);
        $isOwner = ($exchangeRequest->item->user_id === $userId);
        if (!$isRequester && !$isOwner) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        $request->validate([
            'offered_item_id' => 'nullable|exists:items,id',
            'cash_adjustment' => 'nullable|numeric|min:0',
            'message' => 'nullable|string|max:2000',
        ]);

        // recipient is the other party
        $toUserId = $isOwner ? $exchangeRequest->requested_by : $exchangeRequest->item->user_id;

        // basic guard: if offered_item_id is present, it must belong to sender
        if ($request->filled('offered_item_id')) {
            $owned = Item::where('id', $request->offered_item_id)
                ->where('user_id', $userId)->exists();
            if (!$owned) {
                return back()->with('error', 'You can only offer your own item.');
            }
        }

        $offer = TradeOffer::create([
            'exchange_request_id' => $exchangeRequest->id,
            'from_user_id'        => $userId,
            'to_user_id'          => $toUserId,
            'offered_item_id'     => $request->offered_item_id ?: null,
            'cash_adjustment'     => $request->cash_adjustment ?: null,
            'message'             => $request->message,
            'status'              => 'pending',
        ]);

        // notify recipient
        Notification::create([
            'user_id' => $toUserId,
            'message' => "New offer on trade '{$exchangeRequest->item->title}'.",
            'read'    => 0,
        ]);

        return redirect()->route('requests.negotiate', $exchangeRequest->id)
            ->with('success', 'Offer sent.');
    }

    // accept a specific offer (keeps your accept flow consistent)
    public function acceptOffer($offerId)
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'You must be logged in.');
        }
        $userId = session('user_id');

        $offer = TradeOffer::with(['exchangeRequest.item.user', 'fromUser', 'toUser'])->findOrFail($offerId);

        // Only recipient can accept
        if ($offer->to_user_id !== $userId) {
            return back()->with('error', 'Unauthorized.');
        }

        if ($offer->status !== 'pending') {
            return back()->with('error', 'Offer already handled.');
        }

        DB::transaction(function () use ($offer) {
            // mark offer accepted; decline the rest
            $offer->status = 'accepted';
            $offer->save();

            TradeOffer::where('exchange_request_id', $offer->exchange_request_id)
                ->where('id', '!=', $offer->id)
                ->where('status', 'pending')
                ->update(['status' => 'declined']);

            // move the request to accepted
            $er = $offer->exchangeRequest;
            $er->status = 'accepted';
            if (Schema::hasColumn('exchange_requests', 'accepted_at')) {
                $er->accepted_at = now();
            }
            $er->save();

            // notify sender (no sensitive contact info here; acceptance notification w/ contact
            // will be sent by the normal accept() path if the owner uses it,
            // or you can add it here similarly if you want.)
            Notification::create([
                'user_id' => $offer->from_user_id,
                'message' => "Your offer for '{$er->item->title}' was ACCEPTED.",
                'read'    => 0,
            ]);
        });

        return back()->with('success', 'Offer accepted. The trade is now accepted; you may proceed to complete it after meeting.');
    }

    // decline a specific offer
    public function declineOffer($offerId)
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'You must be logged in.');
        }
        $userId = session('user_id');

        $offer = TradeOffer::with(['exchangeRequest.item'])->findOrFail($offerId);

        if ($offer->to_user_id !== $userId) {
            return back()->with('error', 'Unauthorized.');
        }

        if ($offer->status !== 'pending') {
            return back()->with('error', 'Offer already handled.');
        }

        $offer->status = 'declined';
        $offer->save();

        Notification::create([
            'user_id' => $offer->from_user_id,
            'message' => "Your offer for '{$offer->exchangeRequest->item->title}' was DECLINED.",
            'read'    => 0,
        ]);

        return back()->with('success', 'Offer declined.');
    }
}
