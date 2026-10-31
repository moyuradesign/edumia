@extends('layouts.default')
@section('title', \App\Helpers\Localization::label('nav_cart', $locale).' — '.app(\App\Services\System\SettingsRepository::class)->general()['site_name'])
@section('content')
<section class="premium-cart-page">
    <div class="container">
        <div class="premium-cart-heading">
            <span class="section-kicker">{{ \App\Helpers\Localization::label('nav_learning', $locale) }}</span>
            <h1>{{ \App\Helpers\Localization::label('nav_cart', $locale) }}</h1>
            <p>Review your selected courses. Each course is checked out separately using the existing secure order flow.</p>
        </div>
        @if(session('success')) <div class="flash" role="status">{{ session('success') }}</div> @endif
        @if(session('error')) <div class="flash error" role="alert">{{ session('error') }}</div> @endif
        <div class="premium-cart-items">
            @forelse($courses as $course)
                <article class="premium-cart-item">
                    <div class="premium-cart-cover" aria-hidden="true">✦</div>
                    <div class="premium-cart-details">
                        <span>{{ $course->category?->name ?? 'Learning' }}</span>
                        <h2><a href="{{ \App\Helpers\Localization::url($locale, 'courses/'.$course->slug) }}">{{ $course->title }}</a></h2>
                        <p>{{ $course->summary }}</p>
                        <strong>{{ $course->isFree() ? \App\Helpers\Localization::label('free', $locale) : \App\Helpers\Localization::money((string) $course->price, (string) $course->currency_code, $locale) }}</strong>
                    </div>
                    <div class="premium-cart-buttons">
                        @auth
                            @if($course->isFree())
                                <form method="post" action="{{ route('enrollments.store', $course) }}">
                                    @csrf <button class="btn btn-primary" type="submit">Enroll for free</button>
                                </form>
                            @else
                                <form method="post" action="{{ route('checkout.orders.store', $course) }}">
                                    @csrf <button class="btn btn-primary" type="submit">Review order</button>
                                </form>
                            @endif
                        @else
                            <a class="btn btn-primary" href="{{ route('login', ['locale' => $locale]) }}">{{ \App\Helpers\Localization::label('sign_in', $locale) }} to continue</a>
                        @endauth
                        <form method="post" action="{{ route('cart.items.destroy', $course) }}">
                            @csrf @method('DELETE')
                            <button class="premium-cart-remove" type="submit">Remove</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="premium-cart-empty">
                    <span aria-hidden="true">✧</span>
                    <h2>Your cart is empty</h2>
                    <p>Explore courses and add the ones you want to study.</p>
                    <a class="btn btn-primary" href="{{ \App\Helpers\Localization::url($locale, 'courses') }}">{{ \App\Helpers\Localization::label('browse_all', $locale) }}</a>
                </div>
            @endforelse
        </div>
    </div>
</section>
@endsection
