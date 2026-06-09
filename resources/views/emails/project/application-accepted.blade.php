@extends('emails._base')

@section('title', 'Application accepted!')
@section('header_badge', 'Great News')
@section('preheader', "Congratulations! You've been accepted to {{ $projectTitle }}. Your journey starts now.")

@section('hero')
    <div class="hero-eyebrow" style="color:#00d4aa;">
        <span class="hero-eyebrow-dot" style="background:#00d4aa;"></span>
        Application accepted
    </div>
    <h1 class="hero-title">
        Welcome to the<br />
        <span class="hero-title-gradient">team, {{ explode(' ', $applicantName)[0] }}!</span>
    </h1>
    <p class="hero-subtitle">
        Your application to <strong style="color:#f1f1f5;">{{ $projectTitle }}</strong> was accepted.
        The team is excited to have you on board.
    </p>
@endsection

@section('content')

    {{-- Membership card --}}
    <div class="highlight-block">
        <p class="highlight-eyebrow">✦ &nbsp;You are now a member</p>
        <p class="highlight-title">{{ $projectTitle }}</p>
        @if ($roleName)
            <p class="highlight-sub">
                Joining as &nbsp;<span class="pill pill-accent">{{ $roleName }}</span>
            </p>
        @endif
    </div>

    <p class="body-text">
        You've been added to the project and now have full access to the workspace,
        team chat, milestones, and all project resources. Head over and introduce yourself.
    </p>

    {{-- Next steps --}}
    <p class="section-label">Your first steps</p>

    <div class="steps-list">
        <div class="step-item">
            <div class="step-number">1</div>
            <div class="step-text">
                <strong>Introduce yourself</strong>
                Drop a message in the project chat — let the team know who you are
                and what you're excited to work on.
            </div>
        </div>
        <div class="step-item">
            <div class="step-number">2</div>
            <div class="step-text">
                <strong>Review the milestones</strong>
                Get up to speed on where the project stands and what's coming next.
            </div>
        </div>
        <div class="step-item">
            <div class="step-number">3</div>
            <div class="step-text">
                <strong>Connect with your teammates</strong>
                Visit each team member's profile and start building those relationships.
            </div>
        </div>
    </div>

    {{-- CTA --}}
    <div class="btn-row">
        <a href="{{ $projectUrl }}" class="btn btn-accent">Go to project →</a>
        <a href="{{ $projectUrl }}/team" class="btn btn-ghost">Meet the team</a>
    </div>

    <div class="divider"></div>

    <div class="pro-tip">
        <span style="font-size:18px; flex-shrink:0;">💡</span>
        <span>
      <strong>Pro tip:</strong> The best collaborations start with a strong introduction.
      Share your background, your strengths, and what drew you to this project.
    </span>
    </div>

@endsection
