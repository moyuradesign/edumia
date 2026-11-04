@extends('layouts.default')
@section('title', 'Team training — Edumia')
@section('content')
<section class="page-hero"><div class="container">
    <p class="section-kicker">LEARN TOGETHER</p>
    <h1>Team training</h1>
    <p>Organize approved free courses for your team. Each member accepts their invitation before accessing lessons.</p>
</div></section>
<section class="course-section"><div class="container">
    <div class="course-grid">
        @forelse($packages as $package)
            <article class="course-card"><div class="course-body">
                <span class="course-category">Free · {{ $package->seats }} seats</span>
                <h2>{{ $package->name }}</h2>
                <p>{{ $package->description }}</p>
                <p>Included course: <strong>{{ $package->course->title }}</strong></p>
                @auth
                    <form method="post" action="{{ route('team.packages.activate',$package) }}" class="stack-form">
                        @csrf
                        <label>Team or organization name
                            <input name="organization_name" minlength="3" maxlength="190" required></label>
                        <button class="btn btn-primary">Create free team</button>
                    </form>
                @else
                    <a class="btn btn-primary" href="{{ route('login') }}">Sign in to create a team</a>
                @endauth
            </div></article>
        @empty
            <div class="empty-state"><h2>No team packages available</h2>
                <p>Browse the course catalogue for individual learning.</p>
                <a class="btn btn-secondary" href="{{ route('courses.index') }}">Explore courses</a></div>
        @endforelse
    </div>
    {{ $packages->links() }}
</div></section>
@endsection
