{{ strtoupper(config('app.name')) }} — VERIFY YOUR EMAIL
========================================================

Hi {{ $userName }},

Welcome to Co-Found! Please verify your email address to activate your account.

Verify your email:
{{ $verificationUrl }}

This link expires in {{ $expiresInHours }} hours.

If you didn't create a Co-Found account, you can safely ignore this email.

—
© {{ date('Y') }} {{ config('app.name') }}
{{ config('app.url') }}
