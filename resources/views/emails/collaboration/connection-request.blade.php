@extends('emails._base')

@section('title', 'New connection request')
@section('header_badge', 'Network')
@section('preheader', "{{ $requesterName }} wants to connect with you on Co-Found.")

@section('hero')
    <div class="hero-eyebrow">
        <span class="hero-eyebrow-dot"></span>
        Connection request
    </div>
    <h1 class="hero-title">
        Someone wants to<br />
        <span class="hero-title-gradient">connect with you</span>
    </h1>
    <p class="hero-subtitle">
        Hi <strong style="color:#f1f1f5;">{{ $recipientName }}</strong> —
        you have a new connection request from someone on Co-Found.
    </p>
@endsection

@section('content')

    {{-- Requester profile --}}
    <p class="section-label">Connection request from</p>

    <div class="profile-card">
        <div class="profile-avatar">{{ strtoupper(substr($requesterName, 0, 1)) }}</div>
        <div style="flex:1; min-width:0;">
            <p class="profile-name">{{ $requesterName }}</p>
            @if ($requesterTitle)
                <p class="profile-title">{{ $requesterTitle }}</p>
            @else
                <p class="profile-title">Co-Found member</p>
            @endif
        </div>
        <div class="profile-badge">
            <span class="pill pill-purple">Wants to connect</span>
        </div>
    </div>

    <p class="body-text">
        <span class="text-highlight">{{ $requesterName }}</span> found your profile on Co-Found
        and would like to add you to their network. Connecting opens the door to collaboration,
        co-founder conversations, and new project opportunities.
    </p>

    {{-- Why connect --}}
    <div class="meta-grid">
        <div class="meta-row">
            <div class="meta-icon">🚀</div>
            <div class="meta-content">
                <p class="meta-label">Collaboration</p>
                <p class="meta-value">Work together on projects and ideas</p>
            </div>
        </div>
        <div class="meta-row">
            <div class="meta-icon">💬</div>
            <div class="meta-content">
                <p class="meta-label">Direct messaging</p>
                <p class="meta-value">Chat privately once connected</p>
            </div>
        </div>
        <div class="meta-row">
            <div class="meta-icon">🌐</div>
            <div class="meta-content">
                <p class="meta-label">Network</p>
                <p class="meta-value">Grow your founder network</p>
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="btn-row">
        <a href="{{ $manageUrl }}" class="btn btn-primary">View request →</a>
        <a href="{{ $profileUrl }}" class="btn btn-outline">View profile</a>
    </div>

    <div class="divider"></div>

    <p class="footer-note">
        Open your <a href="{{ $manageUrl }}">connections page</a> to accept or decline.
    </p>

@endsection
