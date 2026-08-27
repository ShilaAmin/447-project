<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Submit Item - ExchangeIT</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="{{ asset('css/app-theme.css') }}" rel="stylesheet">
</head>
<body>
  @include('external.nav')

  <div class="container mt-5">
    <h3 class="mb-4 brand-gradient fw-bold">Submit Your Item for Exchange</h3>

    <form method="POST" action="{{ url('/items') }}" enctype="multipart/form-data" class="card border-0 shadow-sm p-4">
      @csrf
      <div class="mb-3">
        <label class="form-label fw-semibold">Item Title</label>
        <input type="text" name="title" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Description</label>
        <textarea name="description" class="form-control" rows="3" required></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Preferred Product</label>
        <input type="text" name="preferred_product" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Upload Photo</label>
        <input type="file" name="photo" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Category</label>
        <input type="text" name="category" class="form-control" list="categoryList" placeholder="Type or choose..." required>
        <datalist id="categoryList">
          @foreach($categories as $category)
            <option value="{{ $category->name }}">
          @endforeach
        </datalist>
      </div>
      <button type="submit" class="btn btn-brand fw-bold">Submit Item</button>
    </form>
  </div>
</body>
</html>
