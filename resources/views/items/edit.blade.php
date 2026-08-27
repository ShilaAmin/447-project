<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Item - ExchangeIT</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="{{ asset('css/app-theme.css') }}" rel="stylesheet">
</head>
<body>
@include('external.nav')

<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Edit Item</h4>
    <a href="{{ route('items.mine') }}" class="btn btn-outline-secondary btn-sm">Back to My Items</a>
  </div>

  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div> @endif

  <form method="POST" action="{{ route('items.update', $item->id) }}" enctype="multipart/form-data" class="card border-0 shadow-sm p-4">
    @csrf
    @method('PUT')

    <div class="mb-3">
      <label class="form-label fw-semibold">Title</label>
      <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
             value="{{ old('title', $item->title) }}" required>
      @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
      <label class="form-label fw-semibold">Description</label>
      <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror" required>{{ old('description', $item->description) }}</textarea>
      @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
      <label class="form-label fw-semibold">Preferred Product</label>
      <input type="text" name="preferred_product" class="form-control @error('preferred_product') is-invalid @enderror"
             value="{{ old('preferred_product', $item->preferred_product) }}" required>
      @error('preferred_product') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
      <label class="form-label fw-semibold">Category</label>
      <input type="text" name="category" class="form-control @error('category') is-invalid @enderror"
             list="categoryList" value="{{ old('category', $item->category?->name) }}" required>
      <datalist id="categoryList">
        @foreach($categories as $category)
          <option value="{{ $category->name }}">
        @endforeach
      </datalist>
      @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
      <label class="form-label fw-semibold">Replace Photo (optional)</label>
      <input type="file" name="photo" class="form-control @error('photo') is-invalid @enderror" accept="image/*">
      @error('photo') <div class="invalid-feedback">{{ $message }}</div> @enderror

      @if($item->photo)
        <div class="mt-2">
          <small class="text-muted d-block">Current photo:</small>
          <img src="{{ asset('storage/'.$item->photo) }}" alt="Current" style="max-height:160px;border-radius:.5rem;">
        </div>
      @endif
    </div>

    <div class="d-flex gap-2">
      <button class="btn btn-brand">Save Changes</button>
      <a href="{{ route('items.mine') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>
</body>
</html>
