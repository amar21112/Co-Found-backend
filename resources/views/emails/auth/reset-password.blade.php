@extends('emails._base')

@section('title', 'Reset your password')
@section('header_badge', 'Security')
@section('preheader', 'Reset your Co-Found password — this link expires in 60 minutes.')

@section('hero')
    <div class="hero-eyebrow" style="color: #f87171;">
        <span class="hero-eyebrow-dot" style="background:#ef4444;"></span>
        Security request
    </div>
    <h1 class="hero-title">Reset your<br /><span class="hero-title-gradient">password</span></h1>
    <p class="hero-subtitle">
        Hi <strong style="color:#f1f1f5;">{{ $userName }}</strong> — we received a request
        to reset the password on your Co-Found account.
    </p>
@endsection

@section('content')

    <p class="body-text">
        Use the button below to choose a new password.
        For your security this link expires in
        <span class="text-highlight">{{ $expiresInMins }} minutes</span>
        and can only be used once.
    </p>

    <div class="btn-row">
        <a href="{{ $resetUrl }}" class="btn btn-primary">Reset my password →</a>
    </div>

    {{-- Security warning --}}
    <div class="warning-block">
        <span class="warning-icon" aria-hidden="true">⚠️</span>
        <div>
            <p class="warning-title">Didn't request this?</p>
            <p class="warning-body">
                If you didn't ask for a password reset your account credentials may be compromised.
                <a href="{{ config('app.url') }}/support">Contact our support team</a> immediately
                and do not click the link above.
            </p>
        </div>
    </div>

    <div class="divider"></div>

    {{-- Security tips --}}
    <p class="section-label">Keep your account safe</p>

    <div class="steps-list">
        <div class="step-item">
            <div class="step-number" style="background:rgba(239,68,68,.8);">✓</div>
            <div class="step-text">
                <strong>Use a strong, unique password</strong>
                At least 12 characters with a mix of letters, numbers, and symbols.
            </div>
        </div>
        <div class="step-item">
            <div class="step-number" style="background:rgba(239,68,68,.8);">✓</div>
            <div class="step-text">
                <strong>Never reuse passwords</strong>
                Use a password manager if you need help keeping track.
            </div>
        </div>
    </div>

    <p class="footer-note" style="text-align:left; margin-bottom:8px;">
        Or paste this link into your browser:
    </p>
    <div class="token-block">{{ $resetUrl }}</div>

    <p class="footer-note">
        This link expires automatically — no action needed if you didn't request it.
    </p>

@endsection
