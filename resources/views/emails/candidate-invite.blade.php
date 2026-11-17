<x-mail.shell :heading="$requestDocuments ? 'Background Verification Document Request' : 'Background Verification Case Created'">
    <div style="font-size:15px;color:#0f2044;font-weight:600;margin-bottom:12px;">
        Dear {{ $case->candidate_name }},
    </div>

    <p style="margin:0 0 16px;color:#334155;line-height:1.6;font-size:14px;">
        <strong style="color:#0f2044;">{{ $organizationName }}</strong> has initiated a pre-employment background verification screening for you via <strong>Orbit Verify</strong>.
    </p>

    {{-- Case Info Card --}}
    <div style="background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px;margin-bottom:20px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;line-height:1.6;">
            <tr>
                <td style="color:#64748b;width:40%;padding-bottom:6px;">Case Reference:</td>
                <td style="color:#0f2044;font-weight:600;padding-bottom:6px;">{{ $case->case_number }}</td>
            </tr>
            <tr>
                <td style="color:#64748b;padding-bottom:6px;">Hiring Company:</td>
                <td style="color:#0f2044;font-weight:600;padding-bottom:6px;">{{ $organizationName }}</td>
            </tr>
            @if($case->candidate_designation)
            <tr>
                <td style="color:#64748b;padding-bottom:6px;">Role / Designation:</td>
                <td style="color:#0f2044;font-weight:600;padding-bottom:6px;">{{ $case->candidate_designation }}</td>
            </tr>
            @endif
            @if($requestDocuments && $expiresAt)
            <tr>
                <td style="color:#64748b;">Upload Valid Until:</td>
                <td style="color:#b45309;font-weight:600;">{{ $expiresAt->format('d M Y, h:i A') }}</td>
            </tr>
            @endif
        </table>
    </div>

    @if($checkNames->isNotEmpty())
    <div style="margin-bottom:20px;">
        <div style="font-size:13px;font-weight:700;color:#0f2044;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:10px;">
            Required Verification Checks:
        </div>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            @foreach($checkNames as $name)
            <tr>
                <td style="padding:6px 0;font-size:13px;color:#334155;">
                    <span style="display:inline-block;width:18px;height:18px;line-height:18px;text-align:center;background-color:#ccfbf1;color:#0d9488;border-radius:50%;font-size:11px;font-weight:bold;margin-right:8px;">✓</span>
                    {{ $name }}
                </td>
            </tr>
            @endforeach
        </table>
    </div>
    @endif

    @if($requestDocuments)
    {{-- Call to action button --}}
    <div style="text-align:center;margin:28px 0 24px 0;">
        <a href="{{ $uploadUrl }}"
           target="_blank"
           style="display:inline-block;background-color:#0d9488;color:#ffffff;text-decoration:none;font-weight:bold;font-size:15px;padding:13px 32px;border-radius:10px;box-shadow:0 2px 4px rgba(13,148,136,0.25);">
            Upload Your Documents Securely &rarr;
        </a>
    </div>

    {{-- Fallback direct link --}}
    <div style="background-color:#f1f5f9;border:1px dashed #cbd5e1;border-radius:8px;padding:12px;font-size:12px;color:#64748b;margin-bottom:20px;word-break:break-all;">
        <strong style="color:#334155;">Direct link:</strong><br>
        <a href="{{ $uploadUrl }}" style="color:#0d9488;text-decoration:underline;">{{ $uploadUrl }}</a>
    </div>

    @else
    <p>Your organization is handling document submission. No upload is requested in this email.</p>
    @endif

    <div style="font-size:12px;color:#64748b;line-height:1.5;border-top:1px solid #e2e8f0;padding-top:16px;">
        Keep your verification link private. If you were not expecting this verification, contact your hiring organization before sharing documents.
    </div>
</x-mail.shell>
