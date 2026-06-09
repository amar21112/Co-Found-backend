@extends('emails._base')

@section('title', 'Application update')
@section('header_badge', 'Application Update')
@section('preheader', "An update on your application to {{ $projectTitle }}. Keep building — the right opportunity is close.")

@section('hero')
    <div class="hero-eyebrow" style="color:#9ca3af;">
        <span class="hero-eyebrow-dot" style="background:#6b7280;"></span>
        Application update
    </div>
    <h1 class="hero-title">
        Keep<br />
        <span class="hero-title-gradient">building.</span>
    </h1>
    <p class="hero-subtitle">
        Hi <strong style="color:#f1f1f5;">{{ $applicantName }}</strong> —
        we have an update on your application to
        <strong style="color:#f1f1f5;">{{ $projectTitle }}</strong>.
    </p>
@endsection

@section('content')

    <p class="body-text">
        After careful consideration, the team at
        <span class="text-highlight">{{ $projectTitle }}</span>
        has decided to move forward with other candidates for this role.
        This is not a reflection of your ability or potential.
    </p>

    <p class="body-text">
        Building a startup team is deeply contextual — timing, existing skills on the team,
        and specific needs all play a role. The right project for you is out there.
    </p>

    {{-- Opportunity cards --}}
    <p class="section-label">What to do next</p>

    <div class="opportunity-grid">
        <div class="opportunity-card">
            <span class="opportunity-icon">🔍</span>
            <p class="opportunity-text">Browse projects that match your skills</p>
        </div>
        <div class="opportunity-card">
            <span class="opportunity-icon">✨</span>
            <p class="opportunity-text">Strengthen your profile & portfolio</p>
        </div>
        <div class="opportunity-card">
            <span class="opportunity-icon">🤝</span>
            <p class="opportunity-text">Connect with founders in your field</p>
        </div>
    </div>

    {{-- CTA --}}
    <div class="btn-row">
        <a href="{{ $exploreUrl }}" class="btn btn-primary">Explore open projects →</a>
    </div>

    <div class="divider"></div>

    <div class="pro-tip">
        <span style="font-size:18px; flex-shrink:0;">🚀</span>
        <span>
      <strong>Consider starting your own project.</strong>
      If you have an idea you're passionate about, Co-Found makes it easy to
      post a project and find the right co-founders to build it with you.
    </span>
    </div>

    <div style="margin-top: 24px;">
        <p class="footer-note">We're rooting for you. Keep building.</p>
    </div>

@endsection
