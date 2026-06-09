<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="x-apple-disable-message-reformatting" />
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no" />
    <meta name="color-scheme" content="dark" />
    <meta name="supported-color-schemes" content="dark" />
    <title>@yield('title') — Co-Found</title>
    <link rel="stylesheet" href="{{ asset('css/email.dist.css') }}" />
</head>
<body>

<div class="preheader" aria-hidden="true">
    @yield('preheader', config('app.name') . ' — Build something great.')
</div>

<div class="email-wrapper">
    <div class="email-container">

        {{-- ── Header ── --}}
        <header class="email-header">
            <a href="{{ config('app.url') }}" class="logo-mark">
                <img
                    src="{{ config('app.url') }}/images/logo.jpg"
                    alt="{{ config('app.name') }}"
                    class="logo-img"
                />
                <!--[if !mso]><!-->
                <span class="logo-wordmark" style="display:none;" aria-hidden="true">Co-Found</span>
                <!--<![endif]-->
            </a>
            <span class="header-badge">@yield('header_badge', 'Co-Found')</span>
        </header>

        {{-- ── Card ── --}}
        <main class="email-card" role="main">

            {{-- Hero section — each template fills this --}}
            <div class="card-hero">
                @yield('hero')
            </div>

            {{-- Body section --}}
            <div class="card-body">
                @yield('content')
            </div>

        </main>

        {{-- ── Footer ── --}}
        <footer class="email-footer">
            <nav class="footer-links" aria-label="Footer links">
                <a href="{{ config('app.url') }}/privacy" class="footer-link">Privacy</a>
                <span class="footer-sep">·</span>
                <a href="{{ config('app.url') }}/terms"   class="footer-link">Terms</a>
                <span class="footer-sep">·</span>
                <a href="{{ config('app.url') }}/support" class="footer-link">Support</a>
            </nav>
            <p class="footer-copy">
                You're receiving this because you have an account on
                <a href="{{ config('app.url') }}">{{ config('app.name') }}</a>.
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </footer>

    </div>
</div>

</body>
</html>
