@extends('emails._base')

@section('title', 'Verify your email')
@section('header_badge', 'Account Setup')
@section('preheader', 'One quick step — confirm your email to activate your Co-Found account.')

@section('hero')
    <div class="hero-eyebrow">
        <span class="hero-eyebrow-dot"></span>
        Action required
    </div>
    <h1 class="hero-title">
        Confirm your<br />
        <span class="hero-title-gradient">email address</span>
    </h1>
    <p class="hero-subtitle">
        Hey <strong style="color:#f1f1f5;">{{ $userName }}</strong> — welcome to Co-Found.
        You're one step away from joining a community of builders, founders, and makers.
    </p>
@endsection

@section('content')

    {{-- Primary CTA --}}
    <p class="body-text">
        Click the button below to verify your email and activate your account.
        Your link is valid for <span class="text-highlight">{{ $expiresInHours }} hours</span>.
    </p>

    <div class="btn-row">
        <a href="{{ $verificationUrl }}" class="btn btn-accent">Verify my email →</a>
    </div>

    <div class="divider"></div>

    {{-- What happens next --}}
    <p class="section-label">What happens next</p>

    <div class="steps-list">
        <div class="step-item">
            <div class="step-number">1</div>
            <div class="step-text">
                <strong>Verify your email</strong>
                Click the button above — takes 2 seconds.
            </div>
        </div>
        <div class="step-item">
            <div class="step-number">2</div>
            <div class="step-text">
                <strong>Complete your profile</strong>
                Add your skills, experience, and what you're looking to build.
            </div>
        </div>
        <div class="step-item">
            <div class="step-number">3</div>
            <div class="step-text">
                <strong>Find your co-founders</strong>
                Browse projects or post your own idea and start connecting.
            </div>
        </div>
    </div>

    {{-- Fallback URL --}}
    <p class="footer-note" style="text-align:left; margin-bottom:8px;">
        Button not working? Paste this into your browser:
    </p>
    <div class="token-block">{{ $verificationUrl }}</div>

    <p class="footer-note">
        Didn't sign up for Co-Found? You can safely ignore this email.
    </p>

@endsection
