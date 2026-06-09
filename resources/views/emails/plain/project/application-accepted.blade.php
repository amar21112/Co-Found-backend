{{ strtoupper(config('app.name')) }} — YOU'RE IN!
=================================================

Congratulations {{ $applicantName }}!

Your application to "{{ $projectTitle }}" was accepted.
@if($roleName)
    You've been added as: {{ $roleName }}
@endif

Head over to the project to meet your team and get started:
{{ $projectUrl }}

Pro tip: Introduce yourself in the project chat and check out the milestones
to see what's coming up next.

—
© {{ date('Y') }} {{ config('app.name') }}
{{ config('app.url') }}
