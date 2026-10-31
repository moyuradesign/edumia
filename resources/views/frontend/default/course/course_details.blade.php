@extends('layouts.default')
@section('title',$course->title.' — Edumia')
@section('content')
<section class="detail-section"><div class="container"><a class="back-link" href="{{ \App\Helpers\Localization::url($locale,'courses') }}">← Back to courses</a>
<div class="detail-grid"><div><span class="section-kicker">{{ $course->category?->name??'Learning' }}</span><h1>{{ $course->title }}</h1><p class="detail-lead">{{ $course->summary }}</p>
<div class="detail-stats"><span>{{ $course->lessons_count }} lessons</span><span>{{ ucfirst($course->level) }}</span><span>{{ $course->isFree() ? \App\Helpers\Localization::label('free', $locale) : \App\Helpers\Localization::money((string) $course->price, (string) $course->currency_code, $locale) }}</span></div>
<p>{{ $course->description }}</p><div class="curriculum"><h2>Course curriculum</h2>
@foreach($course->sections as $section)<h3>{{ $section->title }}</h3><ul>@foreach($section->lessons as $lesson)<li>✓ {{ $lesson->title }}</li>@endforeach</ul>@endforeach
</div></div><aside class="detail-aside"><div class="detail-cover tone-amber"><span>✦</span><b>edumia.</b></div><div class="detail-aside-body">
<p>{{ \App\Helpers\Localization::label('instructor', $locale) }}: <strong>{{ $course->instructor->name }}</strong></p>
@if(auth()->check())
@if(auth()->user()->enrollments()->where('course_id',$course->id)->whereIn('status', ['active', 'completed'])->exists())<a class="btn btn-primary btn-block" href="{{ route('learning.course',$course) }}">Continue learning</a>
@elseif($course->isFree())<form action="{{ route('enrollments.store',$course) }}" method="post">@csrf<button class="btn btn-primary btn-block">Enroll for free</button></form>
@else
<p>Paid access is granted only after a verified payment. You can review an order before any charge.</p>
<form action="{{ route('checkout.orders.store', $course) }}" method="post">@csrf
<button type="submit" class="btn btn-primary btn-block">Review pending order</button></form>
@endif
@else<a class="btn btn-primary btn-block" href="{{ route('login') }}">Sign in to enroll</a>@endif
@auth
@if($saved)
    <form action="{{ route('saved-courses.destroy', $course) }}" method="post">@csrf @method('DELETE')
        <button class="btn btn-quiet btn-block" type="submit">Remove from saved courses</button></form>
@else
    <form action="{{ route('saved-courses.store', $course) }}" method="post">@csrf
        <button class="btn btn-secondary btn-block" type="submit">Save course for later</button></form>
@endif
@endauth
</div></aside></div></div></section>
<section class="course-section"><div class="container">
    <h2>Learner reviews</h2>
    @forelse($reviews as $review)
        <article class="panel-card">
            <h3>{{ $review->title ?: 'Course feedback' }}</h3>
            <p><strong>{{ $review->rating }} / 5</strong> · {{ $review->author?->name ?? 'Learner' }}</p>
            <div class="lesson-content">{!! nl2br(e($review->body)) !!}</div>
        </article>
    @empty
        <p>No approved reviews yet.</p>
    @endforelse
    @auth
        @if($enrolled)
            <div class="panel-card">
                <h3>{{ $existingReview ? 'Update your review' : 'Share your experience' }}</h3>
                @if($existingReview)
                    <p class="subtle">Your review status: {{ ucfirst($existingReview->status) }}. Updating it submits it for moderation again.</p>
                @endif
                <form class="stack-form" method="post" action="{{ route('course-reviews.store', $course) }}">
                    @csrf
                    <label>Rating
                        <select name="rating" required>
                            @for($star = 1; $star <= 5; $star++)
                                <option value="{{ $star }}" @selected((int) old('rating', $existingReview?->rating ?? 5) === $star)>{{ $star }} / 5</option>
                            @endfor
                        </select>
                    </label>
                    <label>Title <input name="title" maxlength="190" value="{{ old('title', $existingReview?->title) }}"></label>
                    <label>Review <textarea name="body" rows="5" required minlength="10" maxlength="5000">{{ old('body', $existingReview?->body) }}</textarea></label>
                    <button class="btn btn-primary" type="submit">Submit for review</button>
                </form>
            </div>
        @endif
    @endauth
</div></section>
@endsection
