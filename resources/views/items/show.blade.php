<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>{{ $item->title }} - ExchangeIT</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="{{ asset('css/app-theme.css') }}" rel="stylesheet">
</head>
<body>
  @include('external.nav')

  @php
    $isAdmin = isset($isAdmin) ? $isAdmin : (bool) session('is_admin');
  @endphp

  <div class="container mt-5">
    <h3 class="fw-bold">{{ $item->title }}</h3>
    @if($item->photo)
      <img src="{{ asset('storage/' . $item->photo) }}" alt="Item photo" class="img-fluid mb-3 rounded">
    @endif
    <p>{{ $item->description }}</p>
    <p><strong>Preferred Exchange:</strong> {{ $item->preferred_product }}</p>

    @if(session()->has('user_id') && session('user_id') != $item->user_id)
      <form action="{{ url('/items/' . $item->id . '/request') }}" method="POST" class="mb-3 card border-0 shadow-sm p-3">
        @csrf
        <div class="mb-2">
          <label class="form-label">Offer one of your items (optional)</label>
          <select name="offered_item_id" class="form-select">
            <option value="">-- None --</option>
            @foreach($userItems as $userItem)
              <option value="{{ $userItem->id }}">{{ $userItem->title }}</option>
            @endforeach
          </select>
        </div>
        <button type="submit" class="btn btn-brand">Request Trade</button>
      </form>
    @endif

    @if(session('user_id') == $item->user_id)
      <form method="POST" action="{{ route('items.destroy', $item->id) }}" class="d-inline">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-outline-warning">Delete</button>
      </form>
    @endif

    @if($isAdmin)
      <form method="POST" action="{{ route('items.destroy', $item->id) }}" class="d-inline ms-2">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-outline-danger">Admin Delete</button>
      </form>
    @endif
  </div>
</body>
</html>
