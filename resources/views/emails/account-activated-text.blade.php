{{ $appName }} — Account activated

Hello {{ $data['name'] }},

Your account has been activated by the administrator. You can now sign in using your registered email address.

Account details:
- Email: {{ $data['email'] }}
- HRID: {{ $data['hrid'] ?? '—' }}
- Activated: {{ $data['activated_at'] }}

Sign in:
{{ $data['sign_in_url'] }}

If you did not request this account, please contact your system administrator.

