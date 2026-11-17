<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px; }
        body { margin: 0; font-family: Helvetica, Arial, sans-serif; color: #1e293b; }
        .doc-bar {
            border-bottom: 2px solid #0d9488;
            padding-bottom: 6px;
            margin-bottom: 14px;
            text-align: left;
        }
        .doc-check {
            font-size: 9px;
            color: #0d9488;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: bold;
        }
        .doc-name {
            font-size: 13px;
            font-weight: bold;
            color: #0f2044;
            margin-top: 2px;
            word-wrap: break-word;
        }
        .img-wrap {
            text-align: center;
        }
        img {
            max-width: 720px;
            max-height: 940px;
            width: auto;
            height: auto;
            border: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="doc-bar">
        <div class="doc-check">{{ $checkName }}</div>
        <div class="doc-name">{{ $documentName }}</div>
    </div>
    <div class="img-wrap">
        <img src="{{ $imageUri }}" alt="{{ $documentName }}">
    </div>
</body>
</html>
