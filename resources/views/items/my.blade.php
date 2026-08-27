<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Items - ExchangeIT</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="{{ asset('css/app-theme.css') }}" rel="stylesheet">
  <style>.thumb{width:100%;height:180px;object-fit:cover;border-top-left-radius:.8rem;border-top-right-radius:.8rem}</style>
</head>
<body>
@include('external.nav')

<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">My Items</h3>
    <a href="{{ url('/items/create') }}" class="btn btn-brand btn-sm">Submit New</a>
  </div>

  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div> @endif

  @if($items->isEmpty())
    <div class="alert alert-info">You haven't submitted any items yet.</div>
  @else
    <div class="row row-cols-1 row-cols-md-3 g-4">
      @foreach($items as $item)
        <div class="col">
          <div class="card h-100 border-0 shadow-sm">
            @if($item->photo)
              <img src="{{ asset('storage/'.$item->photo) }}" class="thumb" alt="Item">
            @endif
            <div class="card-body d-flex flex-column">
              <h5 class="card-title">{{ $item->title }}</h5>
              <div class="small text-muted mb-2">{{ $item->category?->name ?? '—' }}</div>
              <p class="card-text">
                {{ \Illuminate\Support\Str::limit($item->description, 120) }}
              </p>

              <div class="mt-auto d-flex gap-2">
                <a href="{{ route('items.edit', $item->id) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                <form method="POST" action="{{ route('items.destroy', $item->id) }}" onsubmit="return confirm('Delete this item?')">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
              </div>
            </div>
            <div class="card-footer bg-transparent">
              <small class="text-muted">Preferred: {{ $item->preferred_product }}</small>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    <div class="mt-3">{{ $items->links() }}</div>
  @endif
</div>
</body>
</html>
