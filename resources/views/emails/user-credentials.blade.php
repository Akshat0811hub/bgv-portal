<x-mail.shell heading="Your Orbit Verify Account">
    <p style="margin:0 0 14px;">Hello {{ $user->name }},</p>
    <p style="margin:0 0 14px;">An account has been created for you on Orbit Verify. Use the credentials below to sign in. You will be asked to set a new password on your first login.</p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin:0 0 18px;">
        <tr><td style="padding:14px 16px;font-size:13px;">
            <strong>Email:</strong> {{ $user->email }}<br>
            <strong>Temporary password:</strong> <span style="font-family:monospace;">{{ $temporaryPassword }}</span>
        </td></tr>
    </table>

    <p style="margin:0 0 22px;">
        <a href="{{ $loginUrl }}" style="display:inline-block;background-color:#0d9488;color:#ffffff;text-decoration:none;font-weight:bold;font-size:14px;padding:11px 22px;border-radius:8px;">Sign in to Orbit Verify</a>
    </p>

    <p style="margin:0;font-size:12px;color:#94a3b8;">For your security, please change this temporary password immediately after signing in.</p>
</x-mail.shell>
