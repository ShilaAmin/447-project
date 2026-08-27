<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Tradings Statistics - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="{{ asset('css/app-theme.css') }}" rel="stylesheet">
</head>
<body>
@include('external.nav')

<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Tradings — Monthly Statistics</h3>
    <a href="{{ url('/dashboard') }}" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
  </div>

  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th>Month</th>
          <th>Total Users</th>
          <th>Total Items</th>
          <th>Traded Items</th>
          <th>Pending Items</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $r)
          <tr>
            <td>{{ $r['month_label'] }}</td>
            <td>{{ $r['total_users'] }}</td>
            <td>{{ $r['total_items'] }}</td>
            <td>{{ $r['traded_items'] }}</td>
            <td>{{ $r['pending_items'] }}</td>
          </tr>
        @empty
          <tr><td colspan="5" class="text-muted">No data yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <small class="text-muted">
    <strong>Notes:</strong>
    “Total Users” = signups in that month.
    “Total Items” = items submitted in that month (deletions don’t reduce this).
    “Traded Items” = trades completed in that month (when marked completed).
    “Pending Items” = requests created that month and still pending.
  </small>
</div>
</body>
</html>
