@extends('layouts.panel')
@section('title', 'Saved courses')
@section('content')
<div class="panel-heading">
    <div><span class="section-kicker">YOUR LEARNING</span><h1>Saved courses</h1>
        <p>Keep track of courses you may want to join.</p></div>
    <a class="btn btn-secondary" href="{{ route('courses.index') }}">Explore catalogue</a>
</div>
<section class="panel-card">
    @if($savedCourses->isEmpty())
        <p>No saved courses yet. Browse published courses and save one for later.</p>
    @else
        <div class="course-grid">
        @foreach($savedCourses as $item)
            <article class="panel-card">
                <h2>{{ $item->course->title }}</h2>
                <p>{{ $item->course->summary }}</p>
                <a class="btn btn-primary" href="{{ route('courses.show', $item->course->slug) }}">View course</a>
                <form method="post" action="{{ route('saved-courses.destroy', $item->course) }}">
                    @csrf @method('DELETE')
                    <button class="btn btn-quiet" type="submit">Remove</button>
                </form>
            </article>
        @endforeach
        </div>
        {{ $savedCourses->links() }}
    @endif
</section>
@endsection
