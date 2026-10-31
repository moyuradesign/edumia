<footer class="site-footer premium-footer">
    <div class="premium-footer-newsletter">
        <div class="container premium-footer-newsletter-grid">
            <div>
                <span class="premium-footer-eyebrow">{{ $siteName }} · {{ \App\Helpers\Localization::label('nav_learning', $locale) }}</span>
                <h2>{{ $siteName }} — Newsletter</h2>
                <p>{{ \App\Helpers\Localization::label('newsletter_intro', $locale) }}</p>
            </div>
            <form class="premium-footer-signup" method="post" action="{{ route('newsletter.subscribe') }}" aria-label="Newsletter subscription">
                @csrf
                <div class="premium-footer-fields">
                    <label class="sr-only" for="newsletter-email">{{ \App\Helpers\Localization::label('email_address', $locale) }}</label>
                    <input id="newsletter-email" type="email" name="email" maxlength="190" required autocomplete="email"
                           placeholder="{{ \App\Helpers\Localization::label('email_address', $locale) }}">
                    <label class="sr-only" for="newsletter-name">{{ \App\Helpers\Localization::label('newsletter_name', $locale) }}</label>
                    <input id="newsletter-name" name="name" maxlength="160" autocomplete="name"
                           placeholder="{{ \App\Helpers\Localization::label('newsletter_name', $locale) }}">
                    <button type="submit">{{ \App\Helpers\Localization::label('newsletter_subscribe', $locale) }} <span aria-hidden="true">↗</span></button>
                </div>
                <label class="premium-footer-consent"><input type="checkbox" name="consent" value="1" required>
                    <span>{{ \App\Helpers\Localization::label('newsletter_consent', $locale) }}</span></label>
            </form>
        </div>
    </div>
    <div class="container premium-footer-main">
        <div class="premium-footer-about">
            <a class="premium-footer-logo" href="{{ \App\Helpers\Localization::url($locale) }}" aria-label="{{ $siteName }} home">
                <span class="premium-footer-logo-symbol" aria-hidden="true">✦</span>
                <span>{{ $siteName }}<span class="brand-dot">.</span></span>
            </a>
            <p>{{ \App\Helpers\Localization::label('about_copy', $locale) }}</p>
            @if($publicSettings['support_email'] !== '')
                <a class="premium-footer-email" href="mailto:{{ $publicSettings['support_email'] }}">{{ $publicSettings['support_email'] }}</a>
            @endif
        </div>
        <nav class="premium-footer-column" aria-label="{{ \App\Helpers\Localization::label('nav_learning', $locale) }}">
            <h2>{{ \App\Helpers\Localization::label('nav_learning', $locale) }}</h2>
            <a href="{{ \App\Helpers\Localization::url($locale, 'courses') }}">{{ \App\Helpers\Localization::label('nav_courses', $locale) }}</a>
            <a href="{{ \App\Helpers\Localization::url($locale, 'bootcamps') }}">{{ \App\Helpers\Localization::label('nav_bootcamps', $locale) }}</a>
            <a href="{{ \App\Helpers\Localization::url($locale, 'ebooks') }}">{{ \App\Helpers\Localization::label('nav_ebooks', $locale) }}</a>
            <a href="{{ route('cart.index') }}">{{ \App\Helpers\Localization::label('nav_cart', $locale) }}</a>
        </nav>
        <nav class="premium-footer-column" aria-label="{{ \App\Helpers\Localization::label('nav_resources', $locale) }}">
            <h2>{{ \App\Helpers\Localization::label('nav_resources', $locale) }}</h2>
            <a href="{{ \App\Helpers\Localization::url($locale, 'blog') }}">{{ \App\Helpers\Localization::label('nav_blog', $locale) }}</a>
            <a href="{{ \App\Helpers\Localization::url($locale, 'help') }}">{{ \App\Helpers\Localization::label('nav_help', $locale) }}</a>
            <a href="{{ \App\Helpers\Localization::url($locale, 'contact') }}">{{ \App\Helpers\Localization::label('nav_contact', $locale) }}</a>
        </nav>
        <nav class="premium-footer-column" aria-label="{{ \App\Helpers\Localization::label('nav_account', $locale) }}">
            <h2>{{ \App\Helpers\Localization::label('nav_account', $locale) }}</h2>
            @auth
                <a href="{{ route('dashboard') }}">{{ \App\Helpers\Localization::label('dashboard', $locale) }}</a>
                <a href="{{ route('account.preferences.edit') }}">{{ \App\Helpers\Localization::label('nav_my_profile', $locale) }}</a>
                <a href="{{ route('checkout.orders.index') }}">{{ \App\Helpers\Localization::label('nav_my_orders', $locale) }}</a>
            @else
                <a href="{{ route('login', ['locale' => $locale]) }}">{{ \App\Helpers\Localization::label('sign_in', $locale) }}</a>
                <a href="{{ route('register', ['locale' => $locale]) }}">{{ \App\Helpers\Localization::label('join', $locale) }}</a>
            @endauth
        </nav>
    </div>
    <div class="container premium-footer-bottom">
        <p>© {{ date('Y') }} {{ $siteName }}. {{ \App\Helpers\Localization::label('rights', $locale) }}</p>
        <a href="{{ \App\Helpers\Localization::url($locale) }}">{{ \App\Helpers\Localization::label('home', $locale) }} ↑</a>
    </div>
</footer>
