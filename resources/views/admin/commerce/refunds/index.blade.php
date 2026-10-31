@extends('layouts.panel')
@section('title', 'Refund reviews')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">FINANCE SUPPORT</span><h1>Refund reviews</h1>
<p>These are customer requests and administrator notes. Marking a review never sends money or confirms a provider refund.</p></div></div>
<section class="panel-card">
@forelse($requests as $refundRequest)
<article class="builder-section">
<h2>Order {{ $refundRequest->order?->reference ?? $refundRequest->order_id }} · {{ $refundRequest->status }}</h2>
<p>Customer: {{ $refundRequest->user?->email ?? 'Unavailable' }}</p>
<p>{{ $refundRequest->reason }}</p>
@if($refundRequest->review_note)<p><strong>Admin note:</strong> {{ $refundRequest->review_note }}</p>@endif
@if(in_array($refundRequest->status, ['open', 'reviewed'], true))
<form class="stack-form" method="post" action="{{ route('admin.commerce.refunds.update', $refundRequest) }}">
@csrf @method('PATCH')
<label>Review status<select name="status"><option value="reviewed">Reviewed</option><option value="closed">Closed</option></select></label>
<label>Review note<textarea name="review_note" required rows="3" minlength="3" maxlength="2000">{{ old('review_note') }}</textarea></label>
<button class="btn btn-secondary" type="submit">Save review</button>
</form>@endif
</article>
@empty<p>No refund reviews yet.</p>@endforelse
{{ $requests->links() }}
</section>
@endsection
