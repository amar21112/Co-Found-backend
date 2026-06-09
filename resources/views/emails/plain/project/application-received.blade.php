{{ strtoupper(config('app.name')) }} — NEW APPLICATION RECEIVED
===============================================================

Hi {{ $ownerName }},

{{ $applicantName }} just applied to "{{ $projectTitle }}".
@if($roleName)

    Role applied for: {{ $roleName }}
@endif
@if($coverNote)

    Their message:
    "{{ $coverNote }}"
@endif

Review their application:
{{ $reviewUrl }}

You can accept or decline from your project dashboard.

—
© {{ date('Y') }} {{ config('app.name') }}
{{ config('app.url') }}
