<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8">@include('reports.styles')</head>
<body>
<div class="brand">ORBIT <span>VERIFY</span><small>BACKGROUND SCREENING / CONFIDENTIAL</small></div>
<div class="hero"><div class="eyebrow">CONSOLIDATED CASE REPORT</div><h1>Background Verification Report</h1><p>{{ $case->candidate_name }}</p><div class="ref">{{ $case->case_number }} / {{ $case->clientOrganization->name ?? 'Client organization' }}</div></div>
<h2>Report details</h2>
<table class="details">
<tr><th>Candidate name</th><td>{{ $case->candidate_name }}</td><th>Overall status</th><td><span class="status">{{ $case->status }}</span></td></tr>
<tr><th>Email</th><td>{{ $case->candidate_email ?: 'Not provided' }}</td><th>Case start date</th><td>{{ optional($case->case_start_date)->format('d M Y') ?: optional($case->created_at)->format('d M Y') }}</td></tr>
<tr><th>Phone</th><td>{{ $case->candidate_phone ?: 'Not provided' }}</td><th>Report generated</th><td>{{ now()->format('d M Y') }}</td></tr>
<tr><th>Designation</th><td>{{ $case->candidate_designation ?: 'Not provided' }}</td><th>Report closed</th><td>{{ optional($case->report_closed_date)->format('d M Y') ?: 'In progress' }}</td></tr>
</table>
<h2>Executive summary</h2>
<table class="comparison">
<thead><tr><th style="width:6%">#</th><th>Verification check</th><th style="width:23%">Status</th><th style="width:20%">Completed</th><th style="width:10%">Files</th></tr></thead>
<tbody>@foreach($case->checks as $index => $check)
<tr><td>{{ $index + 1 }}</td><td><strong>{{ ucwords(str_replace('_', ' ', $check->check_type)) }}</strong></td><td>{{ $check->status }}</td><td>{{ optional($check->completed_at)->format('d M Y') ?: 'Pending' }}</td><td>{{ $check->documents->count() }}</td></tr>
@endforeach</tbody></table>
@if($case->admin_notes)<h2>Overall remarks</h2><div class="remarks">{{ $case->admin_notes }}</div>@endif
<div class="note"><strong>All evidence in one document</strong><br>This report contains {{ $case->checks->count() }} verification checks and {{ $documents->count() }} uploaded files. Each check is followed by its supporting documents, including all pages of uploaded PDFs and converted office files. General case documents appear at the end.</div>
<h2>Status guide</h2>
<table class="legend"><tr><td><strong class="green">Clear / Completed</strong><br>No adverse finding recorded for this check.</td><td><strong class="red">Discrepancy / Failed</strong><br>Review the check findings and remarks.</td></tr><tr><td><strong class="amber">Amber / Insufficiency</strong><br>Further review, information or documents needed.</td><td><strong>Pending / In progress</strong><br>The check has not reached a final outcome.</td></tr></table>
<p class="muted">Supporting evidence is reproduced as uploaded. Its inclusion does not establish authenticity or alter the verification status. This report reflects information available when downloaded.</p>
</body></html>
