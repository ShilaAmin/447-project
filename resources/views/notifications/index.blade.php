<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Notifications</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="{{ asset('css/app-theme.css') }}" rel="stylesheet">
</head>
<body>
@include('external.nav')

<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Notifications</h3>
    <form action="{{ route('notifications.markAll') }}" method="POST">@csrf
      <button class="btn btn-outline-secondary btn-sm">Mark all as read</button>
    </form>
  </div>

  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div> @endif

  @forelse($notifications as $n)
    <a href="{{ route('notifications.open', $n->id) }}" class="text-decoration-none text-reset">
      <div class="card mb-2 {{ $n->read ? '' : 'border-warning' }}">
        <div class="card-body d-flex justify-content-between align-items-start">
          <div>
            <div class="{{ $n->read ? '' : 'fw-bold' }}">{{ $n->message }}</div>
            <small class="text-muted">{{ $n->created_at?->diffForHumans() }}</small>
          </div>
          @unless($n->read)
            <form action="{{ route('notifications.read', $n->id) }}" method="POST">@csrf
              <button class="btn btn-sm btn-outline-primary">Mark as read</button>
            </form>
          @endunless
        </div>
      </div>
    </a>
  @empty
    <div class="alert alert-info">No notifications.</div>
  @endforelse

  <div class="mt-3">{{ $notifications->links() }}</div>
</div>
</body>
</html>
