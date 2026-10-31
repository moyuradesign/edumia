@extends('layouts.default')
@section('title', $siteSettings['site_name'].' — Learn. Teach. Grow.')
@section('content')
@php
    $coursesUrl = \App\Helpers\Localization::url($locale, 'courses');
    $bootcampsUrl = \App\Helpers\Localization::url($locale, 'bootcamps');
    $ebooksUrl = \App\Helpers\Localization::url($locale, 'ebooks');
    $spotlightCourse = $courses->first();
@endphp

<section class="home-hero" aria-label="Learning highlights">
    <div class="home-hero-container">
        <div class="home-hero-slider" data-hero-slider aria-roledescription="carousel" aria-label="Learning highlights">
            <div class="home-hero-viewport">
                <div class="home-hero-track" data-hero-track>
                    <article class="home-hero-slide home-hero-slide--first" data-hero-slide role="group" aria-roledescription="slide" aria-label="1 of 3">
                        <div class="home-hero-content">
                            <div class="home-hero-copy">
                                <div class="home-hero-eyebrow"><span class="home-hero-eyebrow-mark" aria-hidden="true">✦</span> {{ \App\Helpers\Localization::label('eyebrow', $locale) }}</div>
                                <h1>{{ \App\Helpers\Localization::label('headline', $locale) }}</h1>
                                <p class="home-hero-description">{{ \App\Helpers\Localization::label('intro', $locale) }}</p>
                                <form class="home-hero-search" action="{{ $coursesUrl }}" method="get" role="search">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="10.8" cy="10.8" r="7"/><path d="m16 16 5 5"/></svg>
                                    <input type="search" name="q" maxlength="100" aria-label="{{ \App\Helpers\Localization::label('search_courses', $locale) }}" placeholder="{{ \App\Helpers\Localization::label('search_placeholder', $locale) }}">
                                    <button type="submit">{{ \App\Helpers\Localization::label('search', $locale) }}</button>
                                </form>
                                @if(trim($siteSettings['homepage_notice']) !== '')
                                    <p class="home-hero-notice" role="status">{{ $siteSettings['homepage_notice'] }}</p>
                                @endif
                            </div>
                            <div class="home-hero-visual" aria-hidden="true">
                                <div class="home-hero-orbit home-hero-orbit--one"></div>
                                <div class="home-hero-orbit home-hero-orbit--two"></div>
                                <div class="home-hero-visual-card">
                                    <div class="home-hero-visual-top"><span class="home-hero-visual-icon">✦</span><span>{{ $siteSettings['site_name'] }}</span><span>•••</span></div>
                                    <div class="home-hero-book">
                                        <svg viewBox="0 0 260 195" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <ellipse cx="130" cy="175" rx="90" ry="13" fill="#183C7E" opacity=".10"/>
                                            <rect x="58" y="32" width="145" height="112" rx="16" fill="#EAF1FF" stroke="#B6C9F5" stroke-width="2"/>
                                            <rect x="71" y="45" width="119" height="84" rx="9" fill="#fff"/>
                                            <rect x="87" y="60" width="87" height="9" rx="4.5" fill="#D7E4FA"/>
                                            <rect x="87" y="77" width="70" height="7" rx="3.5" fill="#E4ECFA"/>
                                            <rect x="87" y="91" width="81" height="7" rx="3.5" fill="#E4ECFA"/>
                                            <rect x="87" y="107" width="49" height="8" rx="4" fill="#FDBA74"/>
                                            <path d="M37 156c28-21 59-21 93-7v37c-34-14-65-14-93 7v-37Z" fill="#F97316"/>
                                            <path d="M130 149c34-14 65-14 93 7v37c-28-21-59-21-93-7v-37Z" fill="#2563EB"/>
                                            <path d="M130 149v37M54 160c24-12 44-11 63-4m26 0c24-7 44-8 64 4" stroke="#fff" stroke-opacity=".65" stroke-width="4" stroke-linecap="round"/>
                                            <circle cx="200" cy="29" r="22" fill="#FDBA74"/><path d="m190 30 7 7 13-16" stroke="#fff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </div>
                                    <div class="home-hero-visual-lines"><i></i><i></i><i></i></div>
                                </div>
                                <span class="home-hero-float home-hero-float--top">✦</span><span class="home-hero-float home-hero-float--bottom">✧</span>
                            </div>
                        </div>
                    </article>
                    <article class="home-hero-slide home-hero-slide--second" data-hero-slide role="group" aria-roledescription="slide" aria-label="2 of 3">
                        <div class="home-hero-content">
                            <div class="home-hero-copy">
                                <div class="home-hero-eyebrow"><span class="home-hero-eyebrow-mark" aria-hidden="true">✦</span> {{ \App\Helpers\Localization::label('nav_courses', $locale) }}</div>
                                <h2>{{ $spotlightCourse?->title ?? \App\Helpers\Localization::label('featured', $locale) }}</h2>
                                <p class="home-hero-description">{{ $spotlightCourse?->summary ?: \App\Helpers\Localization::label('featured_intro', $locale) }}</p>
                                <div class="home-hero-actions">
                                    @if($spotlightCourse)
                                        <a class="home-hero-button home-hero-button--primary" href="{{ \App\Helpers\Localization::url($locale, 'courses/'.$spotlightCourse->slug) }}">{{ \App\Helpers\Localization::label('view_course', $locale) }} <span aria-hidden="true">↗</span></a>
                                    @endif
                                    <a class="home-hero-button home-hero-button--light" href="{{ $coursesUrl }}">{{ \App\Helpers\Localization::label('browse_all', $locale) }} <span aria-hidden="true">→</span></a>
                                </div>
                            </div>
                            <div class="home-hero-visual home-hero-visual--path" aria-hidden="true">
                                <div class="home-hero-feature">
                                    <div class="home-hero-feature-header"><span class="home-hero-feature-logo">✦</span><span>{{ $siteSettings['site_name'] }}</span></div>
                                    <div class="home-hero-feature-graphic"><svg viewBox="0 0 220 140" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="110" cy="70" r="61" fill="#DDE9FF"/><rect x="54" y="28" width="112" height="82" rx="14" fill="#fff" stroke="#8DB4FB" stroke-width="2"/><rect x="67" y="42" width="86" height="11" rx="5.5" fill="#D1DFFA"/><rect x="67" y="62" width="64" height="8" rx="4" fill="#ECF2FD"/><rect x="67" y="77" width="76" height="8" rx="4" fill="#ECF2FD"/><circle cx="153" cy="103" r="21" fill="#F97316"/><path d="m143 103 7 7 13-15" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                                    <span class="home-hero-feature-footer">{{ \App\Helpers\Localization::label('featured', $locale) }}</span>
                                </div>
                            </div>
                        </div>
                    </article>
                    <article class="home-hero-slide home-hero-slide--third" data-hero-slide role="group" aria-roledescription="slide" aria-label="3 of 3">
                        <div class="home-hero-content">
                            <div class="home-hero-copy">
                                <div class="home-hero-eyebrow"><span class="home-hero-eyebrow-mark" aria-hidden="true">✦</span> {{ $siteSettings['site_name'] }}</div>
                                <h2>{{ \App\Helpers\Localization::label('about', $locale) }}</h2>
                                <p class="home-hero-description">{{ \App\Helpers\Localization::label('about_copy', $locale) }}</p>
                                <div class="home-hero-actions">
                                    <a class="home-hero-button home-hero-button--primary" href="{{ $bootcampsUrl }}">{{ \App\Helpers\Localization::label('nav_bootcamps', $locale) }} <span aria-hidden="true">↗</span></a>
                                    <a class="home-hero-button home-hero-button--light" href="{{ $ebooksUrl }}">{{ \App\Helpers\Localization::label('nav_ebooks', $locale) }} <span aria-hidden="true">→</span></a>
                                </div>
                            </div>
                            <div class="home-hero-visual home-hero-visual--library" aria-hidden="true">
                                <div class="home-hero-library">
                                    <div class="home-hero-library-top"><span>✧</span><span>{{ $siteSettings['site_name'] }}</span></div>
                                    <div class="home-hero-library-books">
                                        <span class="home-hero-library-book home-hero-library-book--one"></span>
                                        <span class="home-hero-library-book home-hero-library-book--two"></span>
                                        <span class="home-hero-library-book home-hero-library-book--three"></span>
                                        <span class="home-hero-library-book home-hero-library-book--four"></span>
                                    </div>
                                    <div class="home-hero-library-shelf"></div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
            <div class="home-hero-controls" aria-label="Slider controls">
                <div class="home-hero-dots" role="group" aria-label="Choose a slide">
                    <button class="home-hero-dot is-active" type="button" data-hero-dot="0" aria-label="Show slide 1 of 3" aria-current="true"></button>
                    <button class="home-hero-dot" type="button" data-hero-dot="1" aria-label="Show slide 2 of 3"></button>
                    <button class="home-hero-dot" type="button" data-hero-dot="2" aria-label="Show slide 3 of 3"></button>
                </div>
                <span class="home-hero-count" data-hero-count aria-live="off">01 / 03</span>
                <div class="home-hero-control-buttons">
                    <button class="home-hero-pause" type="button" data-hero-pause aria-label="Pause slides" aria-pressed="false"><span aria-hidden="true">Ⅱ</span></button>
                    <button class="home-hero-arrow" type="button" data-hero-prev aria-label="Previous slide"><svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m14 5-7 7 7 7"/></svg></button>
                    <button class="home-hero-arrow" type="button" data-hero-next aria-label="Next slide"><svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m10 5 7 7-7 7"/></svg></button>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="course-section home-course-section" aria-labelledby="home-courses-title">
    <div class="container home-course-container">
        <div class="section-header home-course-heading">
            <div>
                <p class="section-kicker">{{ $siteSettings['site_name'] }} · {{ \App\Helpers\Localization::label('nav_courses', $locale) }}</p>
                <h2 id="home-courses-title">{{ \App\Helpers\Localization::label('featured', $locale) }}</h2>
                <p>{{ \App\Helpers\Localization::label('featured_intro', $locale) }}</p>
            </div>
            <a class="home-course-browse" href="{{ $coursesUrl }}">{{ \App\Helpers\Localization::label('browse_all', $locale) }} <span aria-hidden="true">↗</span></a>
        </div>
        <div class="home-course-grid">
            @forelse($courses as $course)
                <article class="home-course-card">
                    <a class="home-course-cover home-course-cover--{{ ($loop->index % 6) + 1 }}"
                       href="{{ \App\Helpers\Localization::url($locale, 'courses/'.$course->slug) }}"
                       aria-label="{{ \App\Helpers\Localization::label('view_course', $locale) }}: {{ $course->title }}">
                        <span class="home-course-cover-glow" aria-hidden="true"></span>
                        <span class="home-course-cover-symbol" aria-hidden="true">
                            <svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="27" y="17" width="66" height="80" rx="12" fill="white" fill-opacity=".94"/>
                                <rect x="38" y="30" width="44" height="6" rx="3" fill="currentColor" fill-opacity=".28"/>
                                <rect x="38" y="44" width="34" height="5" rx="2.5" fill="currentColor" fill-opacity=".17"/>
                                <rect x="38" y="55" width="42" height="5" rx="2.5" fill="currentColor" fill-opacity=".17"/>
                                <rect x="38" y="67" width="30" height="5" rx="2.5" fill="currentColor" fill-opacity=".17"/>
                                <circle cx="83" cy="85" r="17" fill="currentColor"/>
                                <path d="m76 85 5 5 10-11" stroke="white" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span class="home-course-cover-corner" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></span>
                        <span class="home-course-cover-brand">{{ $siteSettings['site_name'] }}</span>
                    </a>
                    <div class="home-course-body">
                        <span class="home-course-category">{{ $course->category?->name ?? 'Learning' }}</span>
                        <h3><a href="{{ \App\Helpers\Localization::url($locale, 'courses/'.$course->slug) }}">{{ $course->title }}</a></h3>
                        <p class="home-course-summary">{{ $course->summary }}</p>
                        <div class="home-course-details">
                            <span><svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h6a3 3 0 0 1 3 3v12a3 3 0 0 0-3-3H4a2 2 0 0 0-2 2V7a2 2 0 0 1 2-2Zm16 0h-6a3 3 0 0 0-3 3v12a3 3 0 0 1 3-3h6a2 2 0 0 1 2 2V7a2 2 0 0 0-2-2Z"/></svg>{{ $course->lessons_count }} {{ \App\Helpers\Localization::label('lessons', $locale) }}</span>
                            <span>{{ ucfirst($course->level ?: 'all') }}</span>
                        </div>
                        <div class="home-course-bottom">
                            <strong class="home-course-price">{{ $course->isFree() ? \App\Helpers\Localization::label('free', $locale) : \App\Helpers\Localization::money((string) $course->price, (string) ($course->currency_code ?: 'USD'), $locale) }}</strong>
                            <a href="{{ \App\Helpers\Localization::url($locale, 'courses/'.$course->slug) }}" class="home-course-view" aria-label="{{ \App\Helpers\Localization::label('view_course', $locale) }}: {{ $course->title }}">↗</a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="empty-state home-course-empty">
                    <h3>{{ \App\Helpers\Localization::label('no_courses', $locale) }}</h3>
                    <p>{{ \App\Helpers\Localization::label('featured_intro', $locale) }}</p>
                </div>
            @endforelse
        </div>
    </div>
</section>
<section class="about-section"><div class="container about-card"><div><p class="section-kicker">{{ $siteSettings['site_name'] }}</p><h2>{{ \App\Helpers\Localization::label('about',$locale) }}</h2><p>{{ \App\Helpers\Localization::label('about_copy',$locale) }}</p></div><div class="about-symbol">e<span>.</span></div></div></section>
@endsection
