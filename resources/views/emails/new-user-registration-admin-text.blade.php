{{ $appName }} — New user registration requires approval

A new account has been submitted and is pending activation.

Registration details:
- Full name: {{ $data['name'] }}
- Email: {{ $data['email'] }}
- HRID: {{ $data['hrid'] ?? 'Pending assignment' }}
- District: {{ $data['district_name'] ?? '—' }} ({{ $data['district_id'] ?? '—' }})
- Office/School: {{ $data['station_name'] ?? '—' }} ({{ $data['station_id'] ?? '—' }})
- Submitted: {{ $data['requested_at'] }}

Review in User List:
{{ $userListUrl }}

This is an automated message from {{ $appName }}.

