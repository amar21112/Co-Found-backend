@extends('emails._base')

@section('title', 'New application received')
@section('header_badge', 'Project')
@section('preheader', '{{ $applicantName }} applied to "{{ $projectTitle }}" — review their application.')

@section('hero')
    <div class="hero-eyebrow">
        <span class="hero-eyebrow-dot"></span>
        New application
    </div>
    <h1 class="hero-title">
        Someone applied to<br />
        <span class="hero-title-gradient">{{ $projectTitle }}</span>
    </h1>
    <p class="hero-subtitle">
        Hi <strong style="color:#f1f1f5;">{{ $ownerName }}</strong> —
        you have a new applicant waiting for your review.
    </p>
@endsection

@section('content')

    {{-- Applicant profile --}}
    <p class="section-label">Applicant</p>

    <div class="profile-card">
        <div class="profile-avatar">{{ strtoupper(substr($applicantName, 0, 1)) }}</div>
        <div>
            <p class="profile-name">{{ $applicantName }}</p>
            @if ($roleName)
                <p class="profile-title">Applied for <span class="text-accent">{{ $roleName }}</span></p>
            @else
                <p class="profile-title">General application</p>
            @endif
        </div>
        @if ($roleName)
            <div class="profile-badge">
                <span class="pill pill-purple">{{ $roleName }}</span>
            </div>
        @endif
    </div>

    {{-- Application details --}}
    <div class="meta-grid">
        <div class="meta-row">
            <div class="meta-icon">📁</div>
            <div class="meta-content">
                <p class="meta-label">Project</p>
                <p class="meta-value">{{ $projectTitle }}</p>
            </div>
        </div>
        <div class="meta-row">
            <div class="meta-icon">📅</div>
            <div class="meta-content">
                <p class="meta-label">Received</p>
                <p class="meta-value">{{ now()->format('M j, Y \a\t g:i A') }}</p>
            </div>
        </div>
        <div class="meta-row">
            <div class="meta-icon">⏳</div>
            <div class="meta-content">
                <p class="meta-label">Status</p>
                <p class="meta-value"><span class="pill pill-warning">Awaiting review</span></p>
            </div>
        </div>
    </div>

    {{-- Cover note --}}
    @if ($coverNote)
        <p class="section-label">Cover note</p>
        <div class="quote-block">
            <p class="quote-label">In their own words</p>
            <p class="quote-text">&ldquo;{{ $coverNote }}&rdquo;</p>
        </div>
    @endif

    {{-- CTA --}}
    <div class="btn-row">
        <a href="{{ $reviewUrl }}" class="btn btn-primary">Review applications →</a>
    </div>

    <p class="footer-note">
        Accept or decline from your
        <a href="{{ $reviewUrl }}">project applications page</a>.
    </p>

@endsection
