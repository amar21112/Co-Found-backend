@extends('emails._base')

@section('title', 'You have a new invitation')
@section('header_badge', 'Invitation')
@section('preheader', "{{ $senderName }} invited you to {{ $invitationType }} on Co-Found. See what they have in mind.")

@section('hero')
    <div class="hero-eyebrow">
        <span class="hero-eyebrow-dot"></span>
        New invitation
    </div>
    <h1 class="hero-title">
        You've been<br />
        <span class="hero-title-gradient">invited</span>
    </h1>
    <p class="hero-subtitle">
        Hi <strong style="color:#f1f1f5;">{{ $recipientName }}</strong> —
        <strong style="color:#f1f1f5;">{{ $senderName }}</strong> wants to
        {{ $invitationType }} with you on Co-Found.
    </p>
@endsection

@section('content')

    {{-- Sender profile --}}
    <p class="section-label">From</p>

    <div class="profile-card">
        <div class="profile-avatar profile-avatar-accent">{{ strtoupper(substr($senderName, 0, 1)) }}</div>
        <div>
            <p class="profile-name">{{ $senderName }}</p>
            <p class="profile-title">Sent you an invitation on Co-Found</p>
        </div>
        <div class="profile-badge">
            <span class="pill pill-accent">{{ ucfirst($invitationType) }}</span>
        </div>
    </div>

    {{-- Invitation details --}}
    <div class="meta-grid">
        <div class="meta-row">
            <div class="meta-icon" style="background:rgba(0,212,170,.10); border-color:rgba(0,212,170,.18);">🎯</div>
            <div class="meta-content">
                <p class="meta-label">Invitation type</p>
                <p class="meta-value">{{ ucfirst($invitationType) }}</p>
            </div>
        </div>
        @if ($projectTitle)
            <div class="meta-row">
                <div class="meta-icon" style="background:rgba(0,212,170,.10); border-color:rgba(0,212,170,.18);">📁</div>
                <div class="meta-content">
                    <p class="meta-label">Project</p>
                    <p class="meta-value">{{ $projectTitle }}</p>
                </div>
            </div>
        @endif
        @if ($expiresAt)
            <div class="meta-row">
                <div class="meta-icon" style="background:rgba(245,158,11,.10); border-color:rgba(245,158,11,.18);">⏳</div>
                <div class="meta-content">
                    <p class="meta-label">Expires</p>
                    <p class="meta-value text-warning">{{ $expiresAt }}</p>
                </div>
            </div>
        @endif
    </div>

    {{-- Personal message --}}
    @if ($message)
        <p class="section-label">Personal message</p>
        <div class="quote-block accent">
            <p class="quote-label">{{ $senderName }} says</p>
            <p class="quote-text">&ldquo;{{ $message }}&rdquo;</p>
        </div>
    @endif

    {{-- Actions --}}
    <div class="btn-row">
        <a href="{{ $manageUrl }}" class="btn btn-accent">View invitation →</a>
    </div>

    <div class="divider"></div>

    <p class="footer-note">
        Open your <a href="{{ $manageUrl }}">invitations page</a> to accept or decline.
        @if ($expiresAt)
            This invitation expires on {{ $expiresAt }}.
        @endif
    </p>

@endsection
