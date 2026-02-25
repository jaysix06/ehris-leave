<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email Address</title>
</head>
<body style="margin:0;padding:24px;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#27272a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;margin:0 auto;">
        <tr>
            <td style="text-align:center;padding:12px 0 20px;">
                <img
                    src="{{ $message->embed(public_path('dous.png')) }}"
                    alt="DepEd Ozamiz DAWN Protocol Logo"
                    style="display:inline-block;max-width:280px;width:100%;height:auto;"
                >
            </td>
        </tr>
        <tr>
            <td style="background:#ffffff;border:1px solid #e4e4e7;border-radius:8px;padding:28px;">
                <h1 style="margin:0 0 16px;font-size:30px;line-height:1.2;color:#18181b;font-weight:700;">
                    Hello!
                </h1>
                <p style="margin:0 0 22px;font-size:18px;line-height:1.6;">
                    Please click the button below to verify your email address.
                </p>
                <p style="margin:0 0 28px;text-align:center;">
                    <a
                        href="{{ $url }}"
                        style="display:inline-block;background:#18181b;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:6px;font-size:16px;font-weight:600;"
                    >
                        Verify Email Address
                    </a>
                </p>
                <p style="margin:0 0 18px;font-size:18px;line-height:1.6;">
                    If you did not create an account, no further action is required.
                </p>
                <p style="margin:0;font-size:18px;line-height:1.6;">
                    Regards,<br>
                    {{ config('app.name') }}
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
