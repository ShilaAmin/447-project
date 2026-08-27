<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function index()
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'Please login first.');
        }

        $notifications = Notification::where('user_id', session('user_id'))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead($id)
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'Please login first.');
        }

        $notif = Notification::where('id', $id)
            ->where('user_id', session('user_id'))
            ->firstOrFail();

        $notif->read = 1;
        $notif->save();

        return back()->with('success', 'Notification marked as read.');
    }

    // ✅ click-to-mark (GET) — makes the whole card/link mark read in one click
    public function open($id)
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'Please login first.');
        }

        $notif = Notification::where('id', $id)
            ->where('user_id', session('user_id'))
            ->firstOrFail();

        if (!$notif->read) {
            $notif->read = 1;
            $notif->save();
        }

        // If you later add deep links per notification, redirect there.
        return redirect()->route('notifications.index')->with('success', 'Notification opened.');
    }

    // (optional) one-click 'mark all'
    public function markAll()
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'Please login first.');
        }

        Notification::where('user_id', session('user_id'))
            ->where('read', 0)
            ->update(['read' => 1]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
