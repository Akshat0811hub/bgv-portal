@props(['heading' => 'Orbit Verify'])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $heading }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background-color:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">
                    {{-- Header --}}
                    <tr>
                        <td style="background-color:#0f2044;padding:20px 28px;">
                            <span style="color:#ffffff;font-size:18px;font-weight:bold;">Orbit&nbsp;Verify</span>
                            <span style="color:#94a3b8;font-size:12px;display:block;margin-top:2px;">Background Verification Platform</span>
                        </td>
                    </tr>
                    {{-- Body --}}
                    <tr>
                        <td style="padding:28px;">
                            <h1 style="margin:0 0 16px;font-size:18px;color:#0f2044;">{{ $heading }}</h1>
                            <div style="font-size:14px;line-height:1.6;color:#334155;">
                                {{ $slot }}
                            </div>
                        </td>
                    </tr>
                    {{-- Footer --}}
                    <tr>
                        <td style="padding:18px 28px;background-color:#f8fafc;border-top:1px solid #e2e8f0;">
                            <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;">
                                This is an automated message from Orbit Verify. Please do not reply to this email.<br>
                                &copy; {{ date('Y') }} Orbit Verify. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
