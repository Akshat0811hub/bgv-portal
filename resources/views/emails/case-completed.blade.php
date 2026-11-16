<x-mail.shell heading="Background Verification Report Completed">
    <p style="margin:0 0 14px;">Hello,</p>
    <p style="margin:0 0 14px;">The background verification report for <strong>{{ $case->candidate_name }}</strong> (Case: <strong>{{ $case->case_number }}</strong>) has been completed.</p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin:0 0 18px;">
        <tr><td style="padding:14px 16px;font-size:13px;line-height:1.7;">
            <strong>Candidate:</strong> {{ $case->candidate_name }}<br>
            <strong>Case Number:</strong> {{ $case->case_number }}<br>
            <strong>Overall Status:</strong> {{ $case->status }}<br>
            <strong>Closed Date:</strong> {{ optional($case->report_closed_date)->format('d M Y') ?? now()->format('d M Y') }}
        </td></tr>
    </table>

    <p style="margin:0 0 22px;">
        <a href="{{ $caseUrl }}" style="display:inline-block;background-color:#0f2044;color:#ffffff;text-decoration:none;font-weight:bold;font-size:14px;padding:11px 22px;border-radius:8px;margin-right:8px;">View Case Details</a>
        <a href="{{ $reportUrl }}" style="display:inline-block;background-color:#0d9488;color:#ffffff;text-decoration:none;font-weight:bold;font-size:14px;padding:11px 22px;border-radius:8px;">Download PDF Report</a>
    </p>

    <p style="margin:0;font-size:12px;color:#64748b;">Sign in to your Orbit Verify client portal to view all verified findings and supporting documents.</p>
</x-mail.shell>
