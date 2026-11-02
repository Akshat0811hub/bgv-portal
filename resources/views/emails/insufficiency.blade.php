<x-mail.shell heading="Action Required: Additional Information Needed">
    <p style="margin:0 0 14px;">Dear {{ $case->candidate_name }},</p>
    <p style="margin:0 0 14px;">We need additional or corrected information to complete the following check in your background verification:</p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;background-color:#fffbeb;border:1px solid #fde68a;border-radius:8px;margin:0 0 18px;">
        <tr><td style="padding:14px 16px;font-size:13px;">
            <strong>Check:</strong> {{ $checkName }}
            @if($reason)
            <br><strong>Reason:</strong> {{ $reason }}
            @endif
        </td></tr>
    </table>

    <p style="margin:0 0 22px;">
        <a href="{{ $uploadUrl }}" style="display:inline-block;background-color:#0d9488;color:#ffffff;text-decoration:none;font-weight:bold;font-size:14px;padding:11px 22px;border-radius:8px;">Re-upload Documents</a>
    </p>

    @if($expiresAt)
    <p style="margin:0;font-size:12px;color:#b45309;">This secure link expires on {{ $expiresAt->format('d M Y, h:i A') }}.</p>
    @endif
</x-mail.shell>
