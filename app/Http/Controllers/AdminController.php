<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

use App\Models\User;
use App\Models\Item;
use App\Models\ExchangeRequest;
use App\Models\Notification;

class AdminController extends Controller
{
    private function isAdmin(): bool
    {
        return session()->has('user_email') && session('user_email') === 'admin@gmail.com';
    }

    // GET /admin/users -> shows all users by name + actions
    public function usersIndex()
    {
        if (!$this->isAdmin()) {
            return redirect('/dashboard')->with('error', 'Unauthorized.');
        }

        $users = User::orderBy('name')->get();
        return view('admin.users.index', compact('users'));
    }

    // POST /admin/users/{id}/delete -> delete user
    public function deleteUser($id)
    {
        if (!$this->isAdmin()) {
            return redirect('/dashboard')->with('error', 'Unauthorized.');
        }

        $user = User::findOrFail($id);

        if ($user->email === 'admin@gmail.com') {
            return back()->with('error', 'Cannot delete the admin account.');
        }

        $user->delete();
        return back()->with('success', 'User deleted successfully.');
    }

    // POST /admin/users/{id}/warn -> send notification
    public function warnUser(Request $request, $id)
    {
        if (!$this->isAdmin()) {
            return redirect('/dashboard')->with('error', 'Unauthorized.');
        }

        $user = User::findOrFail($id);
        $message = $request->input('message') ?: 'Admin Warning: Please follow the community guidelines.'

        ;

        Notification::create([
            'user_id' => $user->id,
            'message' => $message,
            'read'    => 0, // unread
        ]);

        return back()->with('success', 'Warning sent to the user.');
    }

    // NEW: Admin - list all items with delete action
    public function itemsIndex()
    {
        if (!$this->isAdmin()) {
            return redirect('/dashboard')->with('error', 'Unauthorized.');
        }

        // List newest first; include owner & category
        $items = Item::with(['user', 'category'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.items.index', compact('items'));
    }

    public function stats()
    {
        if (!$this->isAdmin()) {
            return redirect('/dashboard')->with('error', 'Unauthorized.');
        }

        // Users registered per month
        $usersPerMonth = User::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as c")
            ->groupBy('ym')->pluck('c', 'ym');

        // Items submitted per month (additions only; deletions do not affect the count)
        $itemsPerMonth = Item::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as c")
            ->groupBy('ym')->pluck('c', 'ym');

        // Trades completed in that month (count when completed_at is set)
        $tradedPerMonth = ExchangeRequest::whereNotNull('completed_at')
            ->selectRaw("DATE_FORMAT(completed_at, '%Y-%m') as ym, COUNT(*) as c")
            ->groupBy('ym')->pluck('c', 'ym');

        // Pending trades created that month and still pending
        $pendingPerMonth = ExchangeRequest::where('status', 'pending')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as c")
            ->groupBy('ym')->pluck('c', 'ym');

        // Merge all months
        $months = collect()
            ->merge($usersPerMonth->keys())
            ->merge($itemsPerMonth->keys())
            ->merge($tradedPerMonth->keys())
            ->merge($pendingPerMonth->keys())
            ->unique()
            ->sort()
            ->values();

        // Build rows for the view
        $rows = $months->map(function ($ym) use ($usersPerMonth, $itemsPerMonth, $tradedPerMonth, $pendingPerMonth) {
            $label = Carbon::createFromFormat('Y-m', $ym)->format('M Y'); // e.g., "Jan 2025"
            return [
                'ym'           => $ym,
                'month_label'  => $label,
                'total_users'  => (int)($usersPerMonth[$ym] ?? 0),
                'total_items'  => (int)($itemsPerMonth[$ym] ?? 0),
                'traded_items' => (int)($tradedPerMonth[$ym] ?? 0),
                'pending_items'=> (int)($pendingPerMonth[$ym] ?? 0),
            ];
        });

        return view('admin.stats.index', [
            'rows' => $rows,
        ]);
    }
}
