{{ strtoupper(config('app.name')) }} — YOU'VE BEEN INVITED
==========================================================

Hi {{ $recipientName }},

{{ $senderName }} has invited you to {{ $invitationType }} on Co-Found.
@if($projectTitle)

    Project: {{ $projectTitle }}
@endif
@if($expiresAt)
    Expires: {{ $expiresAt }}
@endif
@if($message)

    Their message:
    "{{ $message }}"
@endif

Accept this invitation:
{{ $inviteUrl }}?action=accept

Decline this invitation:
{{ $inviteUrl }}?action=decline

Manage all your invitations: {{ config('app.url') }}/invitations

—
© {{ date('Y') }} {{ config('app.name') }}
{{ config('app.url') }}
