<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Negotiate Trade - ExchangeIT</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="{{ asset('css/app-theme.css') }}" rel="stylesheet">
  <style>
    .offer-card { border-radius: .75rem; }
    .offer-you   { background: rgba(14,165,233,.08); }
    .offer-them  { background: rgba(139,92,246,.08); }
  </style>
</head>
<body>
@include('external.nav')

<div class="container mt-4">
  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div> @endif

  <h4 class="mb-2">Negotiate: <span class="brand-gradient fw-bold">{{ $exchangeRequest->item->title }}</span></h4>
  <p class="text-muted mb-4">
    Owner: <strong>{{ $exchangeRequest->item->user->name }}</strong> •
    Requester: <strong>{{ $exchangeRequest->requester->name }}</strong> •
    Status: <span class="badge text-bg-secondary">{{ ucfirst($exchangeRequest->status) }}</span>
  </p>

  <div class="row">
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm p-3 mb-3">
        <h5 class="mb-3">Thread</h5>

        @forelse($offers as $o)
          @php $mine = $o->from_user_id === $userId; @endphp
          <div class="p-3 mb-3 offer-card {{ $mine ? 'offer-you' : 'offer-them' }}">
            <div class="d-flex justify-content-between">
              <div>
                <strong>{{ $mine ? 'You' : $o->fromUser->name }}</strong>
                <small class="text-muted ms-2">{{ $o->created_at?->diffForHumans() }}</small>
              </div>
              <span class="badge {{ $o->status === 'pending' ? 'text-bg-warning' : ($o->status === 'accepted' ? 'text-bg-success' : 'text-bg-secondary') }}">
                {{ ucfirst($o->status) }}
              </span>
            </div>

            @if($o->offered_item_id)
              <div class="mt-2"><strong>Offered Item:</strong> {{ $o->offeredItem?->title }}</div>
            @endif

            @if(!is_null($o->cash_adjustment))
              <div><strong>Cash Adjustment:</strong> {{ number_format($o->cash_adjustment, 2) }}</div>
            @endif

            @if($o->message)
              <div class="mt-2">{{ $o->message }}</div>
            @endif

            @if($o->status === 'pending' && $o->to_user_id === $userId)
              <div class="mt-3 d-flex gap-2">
                <form action="{{ route('offers.accept', $o->id) }}" method="POST">@csrf
                  <button class="btn btn-sm btn-success">Accept</button>
                </form>
                <form action="{{ route('offers.decline', $o->id) }}" method="POST">@csrf
                  <button class="btn btn-sm btn-outline-danger">Decline</button>
                </form>
              </div>
            @endif
          </div>
        @empty
          <div class="alert alert-info">No offers yet. Start the negotiation below.</div>
        @endforelse
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card border-0 shadow-sm p-3">
        <h5 class="mb-3">Send Offer / Counter-Offer</h5>
        <form action="{{ route('requests.offers.send', $exchangeRequest->id) }}" method="POST">
          @csrf
          <div class="mb-3">
            <label class="form-label">Offer one of your items (optional)</label>
            <select name="offered_item_id" class="form-select">
              <option value="">-- None --</option>
              @foreach($myItems as $it)
                <option value="{{ $it->id }}">{{ $it->title }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Cash Adjustment (optional)</label>
            <input type="number" step="0.01" min="0" name="cash_adjustment" class="form-control" placeholder="e.g., 10.00">
          </div>

          <div class="mb-3">
            <label class="form-label">Message (optional)</label>
            <textarea name="message" rows="3" class="form-control" placeholder="Add terms or notes..."></textarea>
          </div>

          <button class="btn btn-brand">Send Offer</button>
          <a href="{{ url('/requests') }}" class="btn btn-outline-secondary ms-2">Back</a>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
</html>
