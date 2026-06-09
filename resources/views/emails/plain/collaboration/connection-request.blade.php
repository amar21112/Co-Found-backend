{{ strtoupper(config('app.name')) }} — NEW CONNECTION REQUEST
=============================================================

Hi {{ $recipientName }},

{{ $requesterName }} wants to connect with you on Co-Found.
@if($requesterTitle)
    {{ $requesterTitle }}
@endif

Growing your network opens doors to new projects, collaborations,
and co-founder opportunities.

View their profile:
{{ $profileUrl }}

Accept this request:
{{ $connectionsUrl }}?action=accept

Manage all your connection requests: {{ $connectionsUrl }}

—
© {{ date('Y') }} {{ config('app.name') }}
{{ config('app.url') }}
