<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Post - Community</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="{{ asset('css/app-theme.css') }}" rel="stylesheet">
</head>
<body>
@include('external.nav')

<div class="container mt-4">
  <h4 class="mb-3">Edit Post</h4>

  <form action="{{ route('community.update', $post->id) }}" method="POST" enctype="multipart/form-data">@csrf @method('PUT')
    <div class="mb-3">
      <label class="form-label">Title</label>
      <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $post->title) }}" required>
      @error('title') <small class="text-danger">{{ $message }}</small> @enderror
    </div>

    <div class="mb-3">
      <label class="form-label">Content</label>
      <textarea name="content" rows="5" class="form-control @error('content') is-invalid @enderror" required>{{ old('content', $post->content) }}</textarea>
      @error('content') <small class="text-danger">{{ $message }}</small> @enderror
    </div>

    <div class="mb-3">
      <label class="form-label">Replace Photo (optional)</label>
      <input type="file" name="photo" class="form-control @error('photo') is-invalid @enderror" accept="image/*">
      @error('photo') <small class="text-danger">{{ $message }}</small> @enderror

      @if($post->photo)
        <div class="mt-2">
          <small class="text-muted d-block">Current photo:</small>
          <img src="{{ asset('storage/'.$post->photo) }}" alt="Current" style="max-height:120px;">
        </div>
      @endif
    </div>

    <button class="btn btn-brand">Save Changes</button>
    <a href="{{ route('community.index') }}" class="btn btn-outline-secondary ms-2">Cancel</a>
  </form>
</div>
</body>
</html>
