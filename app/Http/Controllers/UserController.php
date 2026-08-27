<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserController extends Controller
{
    public function showSignupForm()
    {
        return view('signup_form');
    }

    public function signup(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'required|string|max:20',
            'nid_no'   => 'required|string|max:50',
            'password' => 'required|string|min:6|confirmed',
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'nid_no'   => $request->nid_no,
            'password' => bcrypt($request->password),
        ]);

        return redirect('/login')->with('success', 'Account created successfully! Please login.');
    }

    public function showLoginForm()
    {
        return view('login_form');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            session([
                'user_id'    => $user->id,
                'user_name'  => $user->name,
                'user_email' => $user->email,
            ]);

            return redirect('/dashboard');
        }

        return back()->with('error', 'Invalid email or password');
    }

    public function dashboard()
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'Please login first.');
        }

        $userName = session('user_name');
        $isAdmin  = session('user_email') === 'admin@gmail.com';

        if ($isAdmin) {
            return view('admin.dashboard', compact('userName'));
        }

        // NEW: user dashboard with cards
        return view('dashboard', compact('userName'));
    }

    // NEW: Profile settings (show)
    public function editProfile()
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'Please login first.');
        }

        $user = User::findOrFail(session('user_id'));
        return view('profile.edit', compact('user'));
    }

    // NEW: Profile settings (update)
    public function updateProfile(Request $request)
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'Please login first.');
        }

        $user = User::findOrFail(session('user_id'));

        $request->validate([
            'name'   => 'required|string|max:255',
            'phone'  => 'required|string|max:20',
            'nid_no' => 'required|string|max:50',
            // Password is optional; validate only if provided
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $user->name  = $request->name;
        $user->phone = $request->phone;
        $user->nid_no = $request->nid_no;

        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        // keep session name in sync
        session(['user_name' => $user->name]);

        return redirect()->route('profile.edit')->with('success', 'Profile updated successfully.');
    }

    public function logout()
    {
        session()->flush();
        return redirect('/login')->with('success', 'Logged out successfully!');
    }
}
