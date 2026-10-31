@extends('layouts.panel')
@section('title', 'My ebook library')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">MY LEARNING</span><h1>My ebook library</h1>
<p>Download the digital resources you have added to your personal library.</p></div>
<a class="btn btn-secondary" href="{{ route('ebooks.index') }}">Browse ebooks</a></div>
<section class="panel-card">
@forelse($books as $purchase)
<article class="builder-section"><h2>{{ $purchase->ebook?->title ?? 'Unavailable ebook' }}</h2>
<p>Added {{ $purchase->purchased_at?->format('M d, Y') }} · Free resource</p>
@if($purchase->ebook)
<a class="btn btn-primary" href="{{ route('ebooks.download', $purchase->ebook) }}">Download PDF</a>
@endif</article>
@empty<p>You have not added any ebooks yet.</p>@endforelse
{{ $books->links() }}
</section>
@endsection
