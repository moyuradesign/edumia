@php
    $categories = \App\Models\Category::query()
        ->where('status', 'active')
        ->whereHas('courses', fn ($query) => $query->published())
        ->orderBy('sort_order')->orderBy('name')->limit(9)->get(['id', 'name', 'slug', 'description']);
    $cartIds = session('cart_course_ids', []);
    $cartCount = is_array($cartIds) ? count(array_unique(array_filter($cartIds, fn ($id) => is_int($id) && $id > 0))) : 0;
    $courseLink = \App\Helpers\Localization::url($locale, 'courses');
@endphp
<header class="site-header premium-header" data-premium-header>
    <div class="container header-inner premium-header-layout">
        <a class="brand premium-brand" href="{{ \App\Helpers\Localization::url($locale) }}" aria-label="{{ $siteName }} home">
            <span class="brand-mark premium-brand-symbol" aria-hidden="true">
                <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="m5 15 15-8 15 8-15 8-15-8Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/>
                    <path d="M10 19v10c7 5 13 5 20 0V19" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M35 15v11" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                </svg>
            </span>
            <span class="premium-brand-name">{{ $siteName }}<span class="brand-dot">.</span></span>
        </a>

        <form class="premium-header-search" action="{{ $courseLink }}" method="get" role="search">
            <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                <circle cx="10.5" cy="10.5" r="7"/><path d="m16 16 5 5"/>
            </svg>
            <input type="search" name="q" maxlength="100"
                value="{{ $currentPath === 'courses' ? (is_string(request()->query('q')) ? request()->query('q') : '') : '' }}"
                aria-label="{{ \App\Helpers\Localization::label('search_courses', $locale) }}"
                placeholder="{{ \App\Helpers\Localization::label('search_placeholder', $locale) }}">
            <button type="submit" aria-label="{{ \App\Helpers\Localization::label('search_courses', $locale) }}">
                <span>{{ \App\Helpers\Localization::label('search', $locale) }}</span>
                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
            </button>
        </form>

        <button class="menu-toggle premium-mobile-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation"
            aria-label="{{ \App\Helpers\Localization::label('menu_open', $locale) }}"
            data-open-label="{{ \App\Helpers\Localization::label('menu_open', $locale) }}"
            data-close-label="{{ \App\Helpers\Localization::label('menu_close', $locale) }}">
            <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            <span>{{ \App\Helpers\Localization::label('menu', $locale) }}</span>
        </button>

        <nav id="primary-navigation" class="primary-nav premium-header-actions" aria-label="Primary navigation">
            <details class="premium-popover premium-category-popover" data-header-details="categories">
                <summary class="premium-header-action premium-categories-trigger">
                    <svg class="premium-action-icon" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7" rx="1.4"/><rect x="14" y="3" width="7" height="7" rx="1.4"/>
                        <rect x="3" y="14" width="7" height="7" rx="1.4"/><rect x="14" y="14" width="7" height="7" rx="1.4"/>
                    </svg>
                    <span>{{ \App\Helpers\Localization::label('nav_categories', $locale) }}</span>
                    <svg class="premium-chevron" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m6 9 6 6 6-6"/></svg>
                </summary>
                <div class="premium-dropdown premium-mega-menu">
                    <div class="premium-mega-heading">
                        <div><span class="premium-dropdown-eyebrow">{{ \App\Helpers\Localization::label('nav_discover', $locale) }}</span>
                            <h2>{{ \App\Helpers\Localization::label('nav_categories', $locale) }}</h2></div>
                        <a class="premium-mega-all" href="{{ $courseLink }}">{{ \App\Helpers\Localization::label('browse_all', $locale) }} <span aria-hidden="true">↗</span></a>
                    </div>
                    <div class="premium-mega-columns">
                        <section class="premium-mega-main" aria-label="{{ \App\Helpers\Localization::label('nav_categories', $locale) }}">
                            <h3>{{ \App\Helpers\Localization::label('nav_popular', $locale) }}</h3>
                            <div class="premium-mega-category-grid">
                                @forelse($categories as $category)
                                    <a class="premium-category-link" href="{{ $courseLink.'?'.http_build_query(['category' => $category->slug]) }}">
                                        <span class="premium-category-symbol" aria-hidden="true">✦</span>
                                        <span class="premium-category-name">{{ $category->name }}</span>
                                        <span class="premium-category-arrow" aria-hidden="true">↗</span>
                                    </a>
                                @empty
                                    <p class="premium-menu-empty">{{ \App\Helpers\Localization::label('nav_empty_categories', $locale) }}</p>
                                @endforelse
                            </div>
                        </section>
                        <section class="premium-mega-side" aria-label="{{ \App\Helpers\Localization::label('nav_learning', $locale) }}">
                            <h3>{{ \App\Helpers\Localization::label('nav_learning', $locale) }}</h3>
                            <a href="{{ $courseLink }}">{{ \App\Helpers\Localization::label('nav_courses', $locale) }}</a>
                            <a href="{{ \App\Helpers\Localization::url($locale, 'bootcamps') }}">{{ \App\Helpers\Localization::label('nav_bootcamps', $locale) }}</a>
                            <a href="{{ \App\Helpers\Localization::url($locale, 'ebooks') }}">{{ \App\Helpers\Localization::label('nav_ebooks', $locale) }}</a>
                            <h3 class="premium-menu-section-break">{{ \App\Helpers\Localization::label('nav_resources', $locale) }}</h3>
                            <a href="{{ \App\Helpers\Localization::url($locale, 'blog') }}">{{ \App\Helpers\Localization::label('nav_blog', $locale) }}</a>
                            <a href="{{ \App\Helpers\Localization::url($locale, 'help') }}">{{ \App\Helpers\Localization::label('nav_help', $locale) }}</a>
                            <a href="{{ \App\Helpers\Localization::url($locale, 'contact') }}">{{ \App\Helpers\Localization::label('nav_contact', $locale) }}</a>
                        </section>
                    </div>
                </div>
            </details>

            <a class="premium-header-action premium-cart-link" href="{{ route('cart.index') }}"
                aria-label="{{ \App\Helpers\Localization::label('nav_cart', $locale) }}{{ $cartCount ? ' ('.$cartCount.')' : '' }}">
                <span class="premium-cart-icon">
                    <svg class="premium-action-icon" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="20" r="1"/><circle cx="19" cy="20" r="1"/><path d="M2 3h2l3 13h13l2-10H5"/>
                    </svg>
                    @if($cartCount > 0)<span class="premium-cart-count" aria-hidden="true">{{ $cartCount }}</span>@endif
                </span>
                <span>{{ \App\Helpers\Localization::label('nav_cart', $locale) }}</span>
            </a>

            <details class="premium-popover premium-account-popover" data-header-details="profile">
                <summary class="premium-profile-trigger" aria-label="{{ \App\Helpers\Localization::label('nav_account', $locale) }}">
                    <span class="premium-avatar" aria-hidden="true">
                        @auth
                            <span class="premium-avatar-initial">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round">
                                <circle cx="12" cy="8" r="3.6"/><path d="M5 20c.6-4.3 3-6.3 7-6.3s6.4 2 7 6.3"/>
                            </svg>
                        @endauth
                    </span>
                    <span class="premium-profile-chevron" aria-hidden="true">⌄</span>
                </summary>
                <div class="premium-dropdown premium-account-menu">
                    @auth
                        <div class="premium-account-identity">
                            <span class="premium-account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                            <div><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->email }}</small></div>
                        </div>
                        <a href="{{ route('dashboard') }}">{{ \App\Helpers\Localization::label('dashboard', $locale) }}</a>
                        <a href="{{ route('account.preferences.edit') }}">{{ \App\Helpers\Localization::label('nav_my_profile', $locale) }}</a>
                        <a href="{{ route('checkout.orders.index') }}">{{ \App\Helpers\Localization::label('nav_my_orders', $locale) }}</a>
                        <a href="{{ route('saved-courses.index') }}">{{ \App\Helpers\Localization::label('nav_saved', $locale) }}</a>
                        <form method="post" action="{{ route('logout') }}" class="premium-logout-form">
                            @csrf <button type="submit">{{ \App\Helpers\Localization::label('nav_sign_out', $locale) }}</button>
                        </form>
                    @else
                        <div class="premium-account-intro">
                            <span class="premium-dropdown-eyebrow">{{ $siteName }}</span>
                            <strong>{{ \App\Helpers\Localization::label('nav_account', $locale) }}</strong>
                        </div>
                        <a href="{{ route('login', ['locale' => $locale]) }}">{{ \App\Helpers\Localization::label('sign_in', $locale) }}</a>
                        <a href="{{ route('register', ['locale' => $locale]) }}" class="premium-account-join">{{ \App\Helpers\Localization::label('join', $locale) }} <span aria-hidden="true">↗</span></a>
                    @endauth
                    <div class="premium-account-preferences">
                        <label for="language-select">{{ \App\Helpers\Localization::label('language', $locale) }}</label>
                        <select id="language-select" class="language-select" aria-label="{{ \App\Helpers\Localization::label('language', $locale) }}">
                            @foreach($languages as $code => $name)
                                <option value="{{ $currentPath === 'cart' ? route('cart.index', ['locale' => $code]) : \App\Helpers\Localization::url($code, $currentPath).(request()->only(['q', 'category', 'page']) ? '?'.http_build_query(request()->only(['q', 'category', 'page'])) : '') }}" @selected($locale === $code)>{{ $name }}</option>
                            @endforeach
                        </select>
                        <button class="theme-toggle premium-account-theme" type="button" aria-label="Toggle color theme">
                            <span class="theme-icon" aria-hidden="true">◐</span>
                            <span>{{ \App\Helpers\Localization::label('dark', $locale) }}</span>
                        </button>
                    </div>
                </div>
            </details>
        </nav>
    </div>
</header>
