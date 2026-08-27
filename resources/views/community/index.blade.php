<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Community - ExchangeIT</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="{{ asset('css/app-theme.css') }}" rel="stylesheet">
  <style>.post-photo{max-height:360px;object-fit:cover}</style>
</head>
<body>
@include('external.nav')

<div class="container mt-4">
  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div> @endif

  @if(!empty($isAdmin) && $isAdmin)
    <div class="card mb-4 border-0 shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Create Community Post</h5>
        <form action="{{ route('community.store') }}" method="POST" enctype="multipart/form-data">@csrf
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
            @error('title') <small class="text-danger">{{ $message }}</small> @enderror
          </div>
          <div class="mb-3">
            <label class="form-label">Content</label>
            <textarea name="content" rows="4" class="form-control @error('content') is-invalid @enderror" required>{{ old('content') }}</textarea>
            @error('content') <small class="text-danger">{{ $message }}</small> @enderror
          </div>
          <div class="mb-3">
            <label class="form-label">Photo (optional)</label>
            <input type="file" name="photo" class="form-control @error('photo') is-invalid @enderror" accept="image/*">
            @error('photo') <small class="text-danger">{{ $message }}</small> @enderror
          </div>
          <button class="btn btn-brand">Publish</button>
        </form>
      </div>
    </div>
  @endif

  <h4 class="mb-3">Latest Posts</h4>
  @forelse($posts as $post)
    <div class="card mb-4 border-0 shadow-sm">
      @if($post->photo)
        <img src="{{ asset('storage/'.$post->photo) }}" class="card-img-top post-photo" alt="Post photo">
      @endif
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <h5 class="card-title mb-1">{{ $post->title }}</h5>
            <small class="text-muted">by {{ $post->author?->name ?? 'Unknown' }} • {{ $post->created_at?->diffForHumans() }}</small>
          </div>
          @if(!empty($isAdmin) && $isAdmin)
            <div class="d-flex gap-2">
              <a href="{{ route('community.edit', $post->id) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
              <form action="{{ route('community.destroy', $post->id) }}" method="POST">@csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">Delete</button>
              </form>
            </div>
          @endif
        </div>

        <p class="card-text mt-3">{{ $post->content }}</p>

        <hr>
        <h6 class="mb-2">Comments</h6>
        @forelse($post->comments as $c)
          <div class="mb-2">
            <strong>{{ $c->author?->name ?? 'User' }}:</strong> <span>{{ $c->content }}</span><br>
            <small class="text-muted">{{ $c->created_at?->diffForHumans() }}</small>
          </div>
        @empty
          <div class="text-muted mb-2">No comments yet.</div>
        @endforelse

        <form action="{{ route('community.comments.store', $post->id) }}" method="POST" class="mt-2">@csrf
          <div class="input-group">
            <input type="text" name="content" class="form-control @error('content') is-invalid @enderror" placeholder="Write a comment..." required>
            <button class="btn btn-outline-primary">Comment</button>
          </div>
          @error('content') <small class="text-danger">{{ $message }}</small> @enderror
        </form>
      </div>
    </div>
  @empty
    <div class="alert alert-info">No posts yet.</div>
  @endforelse

  <div class="mt-3">{{ $posts->links() }}</div>
</div>
</body>
</html>
