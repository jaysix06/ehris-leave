{{ $appName }} — Account activated

Hello {{ $data['name'] }},

Your account has been activated. Use the credentials below to sign in, then change your password after your first login.

Your login credentials:
- Official DepEd email (login): {{ $data['official_email'] }}
- Default password: {{ $data['default_password'] }}
- HRID: {{ $data['hrid'] ?? '—' }}
- Activated: {{ $data['activated_at'] }}

Sign in: {{ $data['sign_in_url'] }}

Please change your password after your first login. If you did not request this account, contact your system administrator.

