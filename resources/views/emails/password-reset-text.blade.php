{{ $appName }} — Password reset (admin request)

Hello {{ $data['name'] }},

Your password has been reset by an administrator after a manual request. Use the temporary credentials below to sign in, then change your password after your first login.

Your login credentials:
- Login email: {{ $data['login_email'] }}
- Temporary password: {{ $data['temporary_password'] }}

Sign in: {{ $data['sign_in_url'] }}

Please change your password after your first login. If you did not request an admin reset, contact your system administrator.
