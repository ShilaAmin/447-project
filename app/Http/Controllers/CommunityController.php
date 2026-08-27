<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Post;
use App\Models\Comment;

class CommunityController extends Controller
{
    private function isAdmin(): bool
    {
        return session()->has('user_email') && session('user_email') === 'admin@gmail.com';
    }

    public function index()
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'Please login first.');
        }

        $isAdmin = $this->isAdmin();

        // newest first; eager-load author & comments with their authors
        $posts = Post::with(['author', 'comments.author'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('community.index', compact('posts', 'isAdmin'));
    }

    // ADMIN: create post
    public function store(Request $request)
    {
        if (!$this->isAdmin()) {
            return redirect()->route('community.index')->with('error', 'Unauthorized.');
        }

        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
            'photo'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('community', 'public');
        }

        Post::create([
            'title'   => $request->title,
            'content' => $request->content,
            'photo'   => $photoPath,
            'user_id' => session('user_id'),
        ]);

        return redirect()->route('community.index')->with('success', 'Post published.');
    }

    // ADMIN: edit form
    public function edit($id)
    {
        if (!$this->isAdmin()) {
            return redirect()->route('community.index')->with('error', 'Unauthorized.');
        }

        $post = Post::findOrFail($id);
        return view('community.edit', compact('post'));
    }

    // ADMIN: update post
    public function update(Request $request, $id)
    {
        if (!$this->isAdmin()) {
            return redirect()->route('community.index')->with('error', 'Unauthorized.');
        }

        $post = Post::findOrFail($id);

        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
            'photo'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        if ($request->hasFile('photo')) {
            // optional: delete old photo
            if ($post->photo) {
                Storage::disk('public')->delete($post->photo);
            }
            $post->photo = $request->file('photo')->store('community', 'public');
        }

        $post->title = $request->title;
        $post->content = $request->content;
        $post->save();

        return redirect()->route('community.index')->with('success', 'Post updated.');
    }

    // ADMIN: delete post
    public function destroy($id)
    {
        if (!$this->isAdmin()) {
            return redirect()->route('community.index')->with('error', 'Unauthorized.');
        }

        $post = Post::findOrFail($id);

        // delete photo if exists
        if ($post->photo) {
            Storage::disk('public')->delete($post->photo);
        }

        // delete comments then post
        $post->comments()->delete();
        $post->delete();

        return redirect()->route('community.index')->with('success', 'Post deleted.');
    }

    // USERS: add comment
    public function storeComment(Request $request, $postId)
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'Please login first.');
        }

        $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $post = Post::findOrFail($postId);

        Comment::create([
            'post_id' => $post->id,
            'user_id' => session('user_id'),
            'content' => $request->content,
        ]);

        return redirect()->route('community.index')->with('success', 'Comment added.');
    }
}
