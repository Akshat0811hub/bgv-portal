<x-mail.shell heading="Document Upload Request">
    <p style="margin:0 0 14px;">Dear {{ $case->candidate_name }},</p>
    <p style="margin:0 0 14px;">{{ $organizationName }} has initiated a background verification for you through Orbit Verify. Please upload the required documents using the secure link below.</p>

    @if($checkNames->isNotEmpty())
    <p style="margin:0 0 8px;font-weight:bold;font-size:13px;">Checks requested:</p>
    <ul style="margin:0 0 18px;padding-left:20px;font-size:13px;color:#334155;">
        @foreach($checkNames as $name)
        <li style="margin-bottom:4px;">{{ $name }}</li>
        @endforeach
    </ul>
    @endif

    <p style="margin:0 0 22px;">
        <a href="{{ $uploadUrl }}" style="display:inline-block;background-color:#0d9488;color:#ffffff;text-decoration:none;font-weight:bold;font-size:14px;padding:11px 22px;border-radius:8px;">Upload Your Documents</a>
    </p>

    @if($expiresAt)
    <p style="margin:0;font-size:12px;color:#b45309;">This secure link expires on {{ $expiresAt->format('d M Y, h:i A') }}.</p>
    @endif
</x-mail.shell>
