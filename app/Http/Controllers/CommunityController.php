<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Post;
use App\Models\Comment;
use App\Services\PostSecurity;
use App\Services\ProfileSecurity;

class CommunityController extends Controller
{
    private function isAdmin(): bool
    {
        return (bool) session('is_admin');
    }

    public function index(PostSecurity $posts, ProfileSecurity $profiles)
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'Please login first.');
        }

        $isAdmin = $this->isAdmin();

        $paginator = Post::with(['author', 'comments.author'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $paginator->getCollection()->transform(function ($post) use ($posts, $profiles) {
            $hydrated = $posts->hydratePost($post);
            if (!$hydrated) {
                $post->setAttribute('title', '[integrity failed]');
                $post->setAttribute('content', '');
            }
            if ($post->author) {
                $post->setRelation('author', $profiles->hydrateName($post->author));
            }
            $post->setRelation('comments', $post->comments->map(function ($c) use ($posts, $profiles) {
                $hc = $posts->hydrateComment($c) ?? $c;
                if ($hc->author) {
                    $hc->setRelation('author', $profiles->hydrateName($hc->author));
                }
                return $hc;
            }));
            return $post;
        });

        return view('community.index', ['posts' => $paginator, 'isAdmin' => $isAdmin]);
    }

    public function store(Request $request, PostSecurity $posts)
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

        $enc = $posts->encryptPost([
            'title' => $request->title,
            'content' => $request->content,
        ]);

        Post::create([
            'title'   => $enc['title'],
            'content' => $enc['content'],
            'mac'     => $enc['mac'],
            'photo'   => $photoPath,
            'user_id' => session('user_id'),
        ]);

        return redirect()->route('community.index')->with('success', 'Post published.');
    }

    public function edit($id, PostSecurity $posts)
    {
        if (!$this->isAdmin()) {
            return redirect()->route('community.index')->with('error', 'Unauthorized.');
        }

        $post = Post::findOrFail($id);
        $hydrated = $posts->hydratePost($post);
        if (!$hydrated) {
            return redirect()->route('community.index')->with('error', 'Post integrity check failed.');
        }

        return view('community.edit', ['post' => $hydrated]);
    }

    public function update(Request $request, $id, PostSecurity $posts)
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
            if ($post->photo) {
                Storage::disk('public')->delete($post->photo);
            }
            $post->photo = $request->file('photo')->store('community', 'public');
        }

        $enc = $posts->encryptPost([
            'title' => $request->title,
            'content' => $request->content,
        ]);

        $post->title = $enc['title'];
        $post->content = $enc['content'];
        $post->mac = $enc['mac'];
        $post->save();

        return redirect()->route('community.index')->with('success', 'Post updated.');
    }

    public function destroy($id)
    {
        if (!$this->isAdmin()) {
            return redirect()->route('community.index')->with('error', 'Unauthorized.');
        }

        $post = Post::findOrFail($id);

        if ($post->photo) {
            Storage::disk('public')->delete($post->photo);
        }

        $post->comments()->delete();
        $post->delete();

        return redirect()->route('community.index')->with('success', 'Post deleted.');
    }

    public function storeComment(Request $request, $postId, PostSecurity $posts)
    {
        if (!session()->has('user_id')) {
            return redirect('/login')->with('error', 'Please login first.');
        }

        $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $post = Post::findOrFail($postId);
        $enc = $posts->encryptComment($request->content);

        Comment::create([
            'post_id' => $post->id,
            'user_id' => session('user_id'),
            'content' => $enc['content'],
            'mac' => $enc['mac'],
        ]);

        return redirect()->route('community.index')->with('success', 'Comment added.');
    }
}
