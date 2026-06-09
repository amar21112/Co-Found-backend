{{ strtoupper(config('app.name')) }} — RESET YOUR PASSWORD
==========================================================

Hi {{ $userName }},

We received a request to reset your Co-Found password.

Reset your password:
{{ $resetUrl }}

This link expires in {{ $expiresInMins }} minutes.

SECURITY NOTICE: If you didn't request a password reset, please contact
support immediately at {{ config('app.url') }}/support

If you didn't request this, no action is needed — the link will expire automatically.

—
© {{ date('Y') }} {{ config('app.name') }}
{{ config('app.url') }}
