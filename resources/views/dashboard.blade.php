<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard - ExchangeIT</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="{{ asset('css/app-theme.css') }}" rel="stylesheet">
</head>
<body>
  @include('external.nav')

  <main class="container mt-5">
    <h2 class="fw-bold">Welcome, {{ $userName }}!</h2>
    <p class="text-muted">What would you like to do today?</p>

    <div class="row g-3 mt-3">
      <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">Submit Item</h5>
            <p class="card-text">Add a new item for exchange.</p>
            <a href="{{ url('/items/create') }}" class="btn btn-brand mt-auto">Open</a>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">Search Items</h5>
            <p class="card-text">Browse items by category.</p>
            <a href="{{ route('items.search.form') }}" class="btn btn-brand mt-auto">Open</a>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">My Items</h5>
            <p class="card-text">Manage items you’ve submitted.</p>
            <a href="{{ route('items.mine') }}" class="btn btn-brand mt-auto">Open</a>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">My Requests</h5>
            <p class="card-text">See trade requests you’ve sent.</p>
            <a href="{{ route('requests.mine') }}" class="btn btn-brand mt-auto">Open</a>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">Profile Settings</h5>
            <p class="card-text">Update your information and password.</p>
            <a href="{{ route('profile.edit') }}" class="btn btn-brand mt-auto">Open</a>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
