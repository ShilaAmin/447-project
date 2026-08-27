<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>All Users</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="{{ asset('css/app-theme.css') }}" rel="stylesheet">
</head>
<body>
@include('external.nav')

<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">All Users</h3>
    <a href="{{ url('/dashboard') }}" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
  </div>

  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div> @endif

  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>NID</th>
          <th>Joined</th>
          <th style="width: 200px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($users as $user)
          <tr>
            <td class="fw-semibold">{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->phone ?? '—' }}</td>
            <td>{{ $user->nid_no ?? '—' }}</td>
            <td>{{ $user->created_at?->format('Y-m-d') }}</td>
            <td>
              @if($user->email === 'admin@gmail.com')
                <span class="badge bg-secondary">Admin</span>
              @else
                <form action="{{ route('admin.users.delete', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this user?')">
                  @csrf
                  <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
                <form action="{{ route('admin.users.warn', $user->id) }}" method="POST" class="d-inline">
                  @csrf
                  <button type="submit" class="btn btn-sm btn-outline-warning">Warn</button>
                </form>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="text-muted">No users found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
