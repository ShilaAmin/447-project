<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin - All Items</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="{{ asset('css/app-theme.css') }}" rel="stylesheet">
  <style>
    .thumb {
      width: 80px; height: 64px; object-fit: cover; border-radius: .5rem;
      border: 1px solid rgba(14,116,144,.12);
      background: #fff;
    }
  </style>
</head>
<body>
@include('external.nav')

<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">All Items</h3>
    <a href="{{ url('/dashboard') }}" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
  </div>

  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div> @endif

  @if($items->isEmpty())
    <div class="alert alert-info">No items found.</div>
  @else
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>Photo</th>
            <th>Title</th>
            <th>Owner</th>
            <th>Category</th>
            <th>Created</th>
            <th style="width: 160px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($items as $item)
            <tr>
              <td>
                @if($item->photo)
                  <img src="{{ asset('storage/'.$item->photo) }}" alt="" class="thumb">
                @else
                  <span class="text-muted">—</span>
                @endif
              </td>
              <td class="fw-semibold">{{ $item->title }}</td>
              <td>
                {{ $item->user?->name ?? 'Unknown' }}<br>
                <small class="text-muted">{{ $item->user?->email }}</small>
              </td>
              <td>{{ $item->category?->name ?? '—' }}</td>
              <td>{{ $item->created_at?->format('Y-m-d') }}</td>
              <td>
                <form method="POST" action="{{ route('items.destroy', $item->id) }}" onsubmit="return confirm('Delete this item?')">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="mt-3">
      {{ $items->links() }}
    </div>
  @endif
</div>
</body>
</html>
