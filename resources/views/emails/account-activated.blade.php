<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Activated</title>
</head>
<body style="margin:0;padding:24px;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#27272a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:680px;margin:0 auto;">
    <tr>
        <td style="padding:0 0 14px;">
            <div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#71717a;">
                {{ $appName }}
            </div>
            <div style="font-size:26px;line-height:1.25;font-weight:800;color:#18181b;margin-top:6px;">
                Your account is now active
            </div>
            <div style="font-size:14px;line-height:1.6;color:#52525b;margin-top:8px;">
                Your registration has been reviewed and approved. Use the credentials below to sign in, then change your password after your first login.
            </div>
        </td>
    </tr>

    <tr>
        <td style="background:#ffffff;border:1px solid #e4e4e7;border-radius:12px;padding:20px;">
            <div style="font-size:14px;color:#18181b;font-weight:700;margin-bottom:10px;">
                Your login credentials
            </div>

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                <tr>
                    <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#71717a;font-size:13px;width:160px;">Name</td>
                    <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#18181b;font-size:13px;font-weight:600;">{{ $data['name'] }}</td>
                </tr>
                <tr>
                    <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#71717a;font-size:13px;">Official DepEd email (login)</td>
                    <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#18181b;font-size:13px;font-weight:600;">{{ $data['official_email'] }}</td>
                </tr>
                <tr>
                    <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#71717a;font-size:13px;">Default password</td>
                    <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#18181b;font-size:13px;font-weight:600;">{{ $data['default_password'] }}</td>
                </tr>
                <tr>
                    <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#71717a;font-size:13px;">HRID</td>
                    <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#18181b;font-size:13px;font-weight:600;">{{ $data['hrid'] ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#71717a;font-size:13px;">Activated</td>
                    <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#18181b;font-size:13px;font-weight:600;">{{ $data['activated_at'] }}</td>
                </tr>
            </table>

            <div style="margin-top:16px;padding-top:16px;border-top:1px solid #f4f4f5;text-align:center;">
                <a href="{{ $data['sign_in_url'] }}"
                   style="display:inline-block;background:#0f766e;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:10px;font-size:14px;font-weight:700;">
                    Sign in to {{ $appName }}
                </a>
                <div style="font-size:12px;color:#71717a;margin-top:10px;line-height:1.5;">
                    Please change your password after your first login. If you did not request this account, contact your system administrator.
                </div>
            </div>
        </td>
    </tr>

    <tr>
        <td style="padding:14px 2px 0;color:#71717a;font-size:12px;line-height:1.6;">
            This is an automated message from {{ $appName }}.
        </td>
    </tr>
</table>
</body>
</html>

