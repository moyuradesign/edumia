@extends('layouts.panel')
@section('title', 'Ebook approvals')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">CONTENT APPROVAL</span><h1>Ebook approvals</h1>
<p>Only uploaded free ebooks can be made publicly available. Paid ebooks remain unpublished until checkout is verified.</p></div></div>
<section class="panel-card">
@forelse($ebooks as $ebook)
<article class="builder-section">
<h2>{{ $ebook->title }}</h2><p>Author: {{ $ebook->author?->name ?? 'Unknown' }} ·
{{ $ebook->isFree() ? 'Free' : '$'.$ebook->price }} · {{ ucfirst($ebook->status) }}</p>
<p>{{ $ebook->description }}</p>
<a href="{{ route('ebooks.download', $ebook) }}">Review PDF privately</a> ·
<a href="{{ route('instructor.ebooks.edit', $ebook) }}">Edit</a>
<form method="post" class="inline-form" action="{{ route('admin.ebooks.publication', $ebook) }}">
@csrf @method('PATCH')
<input type="hidden" name="published" value="{{ $ebook->status === 'published' ? 0 : 1 }}">
<button class="btn btn-secondary" type="submit">
{{ $ebook->status === 'published' ? 'Unpublish' : 'Publish free ebook' }}</button>
</form>
</article>
@empty<p>No ebooks submitted.</p>@endforelse
{{ $ebooks->links() }}
</section>
@endsection
