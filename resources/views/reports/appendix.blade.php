<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 48px; }
        body { font-family: "DejaVu Sans", sans-serif; color: #1e293b; font-size: 12px; line-height: 1.6; }
        .label { color: #0d9488; font-size: 12px; text-transform: uppercase; }
        h1 { color: #0f2044; font-size: 24px; margin: 8px 0 28px; }
        .file { padding: 20px; background: #f1f5f9; border-left: 4px solid #0d9488; overflow-wrap: anywhere; }
        .muted { color: #64748b; font-size: 10px; }
    </style>
</head>
<body>
    <p class="label">Orbit Verify / Supporting Documents</p>
    <h1>Appendix {{ $number }}</h1>
    <p><strong>Candidate:</strong> {{ $case->candidate_name }}<br>
       <strong>Case:</strong> {{ $case->case_number }}<br>
       <strong>Check:</strong> {{ $document->caseCheck ? ucwords(str_replace('_', ' ', $document->caseCheck->check_type)) : 'General case document' }}<br>
       <strong>Uploaded by:</strong> {{ ucfirst($document->uploaded_by_role ?: 'Unknown') }}</p>
    <div class="file"><strong>{{ $document->original_name }}</strong></div>
    <p>The following pages contain this uploaded document.</p>
    <p class="muted">Supporting evidence is reproduced as submitted. Inclusion does not by itself confirm authenticity or verification status. Refer to the findings in the main report.</p>
    <p class="muted">Confidential - {{ $case->case_number }}</p>
</body>
</html>
