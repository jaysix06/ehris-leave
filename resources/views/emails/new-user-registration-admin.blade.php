<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New User Registration</title>
</head>
<body style="margin:0;padding:24px;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#27272a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:680px;margin:0 auto;">
    <tr>
        <td style="padding:0 0 14px;">
            <div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#71717a;">
                {{ $appName }}
            </div>
            <div style="font-size:26px;line-height:1.25;font-weight:800;color:#18181b;margin-top:6px;">
                New user registration requires approval
            </div>
            <div style="font-size:14px;line-height:1.6;color:#52525b;margin-top:8px;">
                A new account has been submitted and is pending activation by an administrator.
            </div>
        </td>
    </tr>

    <tr>
        <td style="background:#ffffff;border:1px solid #e4e4e7;border-radius:12px;padding:20px;">
            <div style="font-size:14px;color:#18181b;font-weight:700;margin-bottom:10px;">
                Registration details
            </div>

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                <tr>
                    <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#71717a;font-size:13px;width:160px;">Full name</td>
                    <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#18181b;font-size:13px;font-weight:600;">{{ $data['name'] }}</td>
                </tr>
                <tr>
                    <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#71717a;font-size:13px;">Email</td>
                    <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#18181b;font-size:13px;font-weight:600;">{{ $data['email'] }}</td>
                </tr>
                <tr>
                    <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#71717a;font-size:13px;">HRID</td>
                    <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#18181b;font-size:13px;font-weight:600;">
                        {{ $data['hrid'] ?? 'Pending assignment' }}
                    </td>
                </tr>
                <tr>
                    <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#71717a;font-size:13px;">District</td>
                    <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#18181b;font-size:13px;font-weight:600;">
                        {{ $data['district_name'] ?? '—' }}
                        <span style="color:#71717a;font-weight:500;">({{ $data['district_id'] ?? '—' }})</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#71717a;font-size:13px;">Office/School</td>
                    <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#18181b;font-size:13px;font-weight:600;">
                        {{ $data['station_name'] ?? '—' }}
                        <span style="color:#71717a;font-weight:500;">({{ $data['station_id'] ?? '—' }})</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#71717a;font-size:13px;">Submitted</td>
                    <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#18181b;font-size:13px;font-weight:600;">{{ $data['requested_at'] }}</td>
                </tr>
            </table>

            <div style="margin-top:16px;padding-top:16px;border-top:1px solid #f4f4f5;text-align:center;">
                <a href="{{ $userListUrl }}"
                   style="display:inline-block;background:#0f172a;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:10px;font-size:14px;font-weight:700;">
                    Review in User List
                </a>
                <div style="font-size:12px;color:#71717a;margin-top:10px;line-height:1.5;">
                    Open <span style="font-weight:700;color:#52525b;">Utilities → User List</span> and activate the account when ready.
                </div>
            </div>
        </td>
    </tr>

    <tr>
        <td style="padding:14px 2px 0;color:#71717a;font-size:12px;line-height:1.6;">
            This is an automated message from {{ $appName }}. If you believe you received this in error, please contact your system administrator.
        </td>
    </tr>
</table>
</body>
</html>

