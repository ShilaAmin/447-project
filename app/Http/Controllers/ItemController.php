<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Item;
use App\Models\Category;

class ItemController extends Controller
{
    public function create()
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'Please login first.');
        }

        $categories = Category::all();
        return view('items.create', compact('categories'));
    }

    public function store(Request $request)
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'Please login first.');
        }

        $request->validate([
            'title'             => 'required|string|max:255',
            'description'       => 'required|string',
            'preferred_product' => 'required|string|max:255',
            'photo'             => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
            'category'          => 'required|string|max:255'
        ]);

        $category  = Category::firstOrCreate(['name' => $request->category]);
        $photoPath = $request->file('photo')->store('uploads', 'public');

        Item::create([
            'user_id'           => session('user_id'),
            'category_id'       => $category->id,
            'title'             => $request->title,
            'description'       => $request->description,
            'preferred_product' => $request->preferred_product,
            'photo'             => $photoPath
        ]);

        return redirect('/dashboard')->with('success', 'Item submitted successfully!');
    }

    public function searchForm()
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'Please login first.');
        }

        $categories = Category::all();
        return view('items.search', compact('categories'));
    }

    public function searchResults(Request $request)
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'Please login first.');
        }

        $categoryName = $request->category;
        $category     = Category::where('name', $categoryName)->first();

        if (!$category) {
            return redirect()->back()->with('error', 'Category not found.');
        }

        $items = $category->items()->get();

        $userItems = [];
        if (session()->has('user_id')) {
            $userItems = Item::where('user_id', session('user_id'))->get();
        }

        $userRequestedItemIds = [];
        if (session()->has('user_id')) {
            $userRequestedItemIds = \App\Models\ExchangeRequest::where('requested_by', session('user_id'))
                ->pluck('item_id')->toArray();
        }

        $isAdmin = session('user_email') === 'admin@gmail.com';

        return view('items.search_results', compact(
            'items', 'categoryName', 'userItems', 'userRequestedItemIds', 'isAdmin'
        ));
    }

    // ---------- MY ITEMS (list) ----------
    public function myItems()
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'Please login first.');
        }

        $items = Item::with('category')
            ->where('user_id', session('user_id'))
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('items.my', compact('items'));
    }

    // ---------- EDIT ----------
    public function edit($id)
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'Please login first.');
        }

        $item = Item::findOrFail($id);

        // Owner only (allow admin too if you prefer)
        if ($item->user_id != session('user_id')) {
            abort(403, 'Unauthorized');
        }

        $categories = Category::all();
        return view('items.edit', compact('item', 'categories'));
    }

    // ---------- UPDATE ----------
    public function update(Request $request, $id)
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'Please login first.');
        }

        $item = Item::findOrFail($id);

        if ($item->user_id != session('user_id')) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'title'             => 'required|string|max:255',
            'description'       => 'required|string',
            'preferred_product' => 'required|string|max:255',
            'photo'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'category'          => 'required|string|max:255'
        ]);

        $category = Category::firstOrCreate(['name' => $request->category]);

        if ($request->hasFile('photo')) {
            if ($item->photo) {
                Storage::disk('public')->delete($item->photo);
            }
            $item->photo = $request->file('photo')->store('uploads', 'public');
        }

        $item->category_id       = $category->id;
        $item->title             = $request->title;
        $item->description       = $request->description;
        $item->preferred_product = $request->preferred_product;
        $item->save();

        return redirect()->route('items.mine')->with('success', 'Item updated successfully.');
    }

    // ---------- DELETE (owner or admin) ----------
    public function destroy($itemId)
    {
        $item = Item::with('user')->findOrFail($itemId);

        $isOwner = session('user_id') && $item->user_id == session('user_id');
        $isAdmin = session('user_email') === 'admin@gmail.com';

        if (!$isOwner && !$isAdmin) {
            abort(403, 'Unauthorized action.');
        }

        // notify owner if admin deletes
        if ($isAdmin && !$isOwner) {
            \App\Models\Notification::create([
                'user_id' => $item->user->id,
                'message' => "Your item '{$item->title}' was DELETED by Admin.",
                'read'    => 0,
            ]);
        }

        if ($item->photo) {
            Storage::disk('public')->delete($item->photo);
        }

        $item->delete();
        return redirect()->back()->with('success', 'Item deleted successfully.');
    }
}
