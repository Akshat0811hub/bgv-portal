<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 48px; }
        body { font-family: "DejaVu Sans", sans-serif; color: #1e293b; font-size: 11px; line-height: 1.6; }
        .box { border: 1px solid #cbd5e1; background: #f8fafc; border-left: 4px solid #0d9488; padding: 20px; margin-top: 24px; }
        h2 { color: #0f2044; font-size: 15px; margin: 0 0 8px; }
        .btn { display: inline-block; margin-top: 14px; padding: 10px 18px; background: #0d9488; color: #ffffff; text-decoration: none; font-weight: bold; border-radius: 6px; font-size: 11px; }
        .muted { color: #64748b; font-size: 9.5px; margin-top: 12px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>{{ $document->original_name }}</h2>
        <p>This uploaded PDF uses advanced compression or encryption that cannot be embedded directly into the merged PDF stream.</p>
        <p>You can open and download the original uploaded file directly from the Orbit Verify portal using the secure link below:</p>
        <a href="{{ $viewUrl }}" class="btn">Open / Download Original Document &rarr;</a>
        <div class="muted">Secure Link: {{ $viewUrl }}</div>
    </div>
</body>
</html>
