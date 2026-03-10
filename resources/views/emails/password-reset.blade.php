<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
</head>
<body style="margin:0;padding:24px;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#27272a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:420px;margin:0 auto;">
    <tr>
        <td style="background:#ffffff;border:1px solid #e4e4e7;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,.08);overflow:hidden;">
            <div style="padding:20px 20px 0;text-align:center;">
                <img
                    src="{{ $message->embed(public_path('ehris.png')) }}"
                    alt="{{ $appName }}"
                    width="280"
                    height="144"
                    style="display:block;width:100%;max-width:280px;height:auto;margin:0 auto;border:0;outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;"
                >
            </div>

            <div style="padding:24px 24px 20px;">
                <h1 style="margin:0 0 8px;font-size:22px;line-height:1.3;font-weight:600;color:#18181b;text-align:center;">
                    Your password has been reset
                </h1>
                <p style="margin:0 0 20px;font-size:14px;line-height:1.5;color:#52525b;text-align:center;">
                    This reset was made by an administrator after a manual request. Use the temporary credentials below to sign in, then change your password after your first login.
                </p>

                <div style="font-size:14px;color:#18181b;font-weight:700;margin-bottom:12px;">
                    Your login credentials
                </div>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                    <tr>
                        <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#71717a;font-size:13px;width:120px;">Name</td>
                        <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#18181b;font-size:13px;font-weight:600;">{{ $data['name'] }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#71717a;font-size:13px;">Login email</td>
                        <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#18181b;font-size:13px;font-weight:600;">{{ $data['login_email'] }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#71717a;font-size:13px;">Temporary password</td>
                        <td style="padding:10px 0;border-top:1px solid #f4f4f5;color:#18181b;font-size:13px;font-weight:600;">{{ $data['temporary_password'] }}</td>
                    </tr>
                </table>

                <div style="margin-top:20px;padding-top:20px;border-top:1px solid #f4f4f5;text-align:center;">
                    <a href="{{ $data['sign_in_url'] }}"
                       style="display:inline-block;background:#0f766e;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:10px;font-size:14px;font-weight:600;">
                        Sign in to {{ $appName }}
                    </a>
                    <p style="font-size:12px;color:#71717a;margin:12px 0 0;line-height:1.5;">
                        Please change your password after your first login. If you did not request an admin reset, contact your system administrator.
                    </p>
                </div>
            </div>
        </td>
    </tr>

    <tr>
        <td style="padding:16px 8px 0;color:#71717a;font-size:12px;line-height:1.5;text-align:center;">
            This is an automated message from {{ $appName }}.
        </td>
    </tr>
</table>
</body>
</html>
