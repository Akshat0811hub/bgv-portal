<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">@include('reports.styles')</head><body>
<div class="brand">ORBIT <span>VERIFY</span><small>{{ $case->case_number }} / {{ $case->candidate_name }}</small></div>
<div class="check-top"><div class="eyebrow">ADDITIONAL EVIDENCE</div><h1>General case documents</h1></div>
<p class="muted">Available previews follow this list. Sign in to Orbit Verify with access to this case to open original files.</p>
@include('reports.document-list')
</body></html>
