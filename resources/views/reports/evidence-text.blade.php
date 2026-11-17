<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 36px; }
        body { font-family: Helvetica, Arial, sans-serif; color: #1e293b; font-size: 10.5px; line-height: 1.55; }
        .hdr { border-bottom: 2px solid #0d9488; padding-bottom: 8px; margin-bottom: 16px; }
        .hdr .kicker { font-size: 9px; color: #0d9488; text-transform: uppercase; letter-spacing: 0.6px; }
        .hdr .title { font-size: 13px; font-weight: bold; color: #0f2044; margin-top: 2px; }
        p { margin: 0 0 8px; white-space: pre-wrap; word-wrap: break-word; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; font-size: 9.5px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; vertical-align: top; }
        tr:nth-child(even) td { background: #f8fafc; }
    </style>
</head>
<body>
    <div class="hdr">
        <div class="kicker">Document Content Reproduction</div>
        <div class="title">{{ $title }}</div>
    </div>

    @if(!empty($blocks))
        @foreach($blocks as $block)
            @if(($block['type'] ?? 'p') === 'table')
                <table>
                    @foreach($block['rows'] as $row)
                        <tr>
                            @foreach($row as $cell)
                                <td>{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </table>
            @else
                <p>{{ $block['text'] }}</p>
            @endif
        @endforeach
    @else
        <p style="color:#64748b;">(This document contained no extractable text content.)</p>
    @endif
</body>
</html>
