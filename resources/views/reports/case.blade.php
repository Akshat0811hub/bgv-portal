<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    @php
        // [text color, background color] per status
        $badge = function (?string $s) {
            $s = (string) $s;
            return match (true) {
                str_contains($s, 'Clear'), $s === 'Completed'        => ['#065f46', '#d1fae5'],
                str_contains($s, 'Discrepancy'), $s === 'Failed'     => ['#991b1b', '#fee2e2'],
                $s === 'Amber'                                        => ['#9a3412', '#ffedd5'],
                $s === 'Insufficiency'                               => ['#5b21b6', '#ede9fe'],
                $s === 'In Progress'                                => ['#1e40af', '#dbeafe'],
                $s === 'On Hold'                                    => ['#334155', '#e2e8f0'],
                default                                              => ['#b45309', '#fef3c7'], // Pending
            };
        };
        $statuses = $case->checks->pluck('status');
        $cleared  = $statuses->filter(fn ($s) => str_contains((string) $s, 'Clear') || $s === 'Completed')->count();
    @endphp
    <style>
        @page { margin: 38px 34px 58px 34px; }
        * { font-family: "DejaVu Sans", sans-serif; }
        body { margin: 0; color: #1e293b; font-size: 11px; line-height: 1.45; }

        /* repeating footer on every page */
        footer { position: fixed; bottom: -40px; left: 0; right: 0; text-align: center;
                 font-size: 8.5px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 6px; }

        .band { background-color: #0f2044; color: #ffffff; padding: 18px 22px; border-radius: 8px; }
        .band h1 { margin: 0; font-size: 19px; font-weight: bold; letter-spacing: .2px; }
        .band .sub { color: #9fb3d1; font-size: 11px; margin-top: 3px; }
        .band .tag { color: #cfe9e3; font-size: 10px; margin-top: 8px; }

        h2 { font-size: 12.5px; color: #0f2044; margin: 20px 0 8px; padding-bottom: 5px;
             border-bottom: 2px solid #0d9488; }

        table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 4px 6px; vertical-align: top; font-size: 11px; }
        .meta .label { color: #64748b; width: 120px; }
        .meta .val { color: #0f2044; font-weight: bold; }

        /* summary table */
        .summary { border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; }
        .summary th { background-color: #f1f5f9; color: #334155; text-align: left;
                      padding: 7px 10px; font-size: 10px; text-transform: uppercase; letter-spacing: .3px; }
        .summary td { padding: 7px 10px; border-top: 1px solid #eef2f6; font-size: 11px; }

        .badge { display: inline-block; padding: 2px 9px; border-radius: 9px; font-size: 9px; font-weight: bold; }

        /* check card */
        .check { border: 1px solid #e2e8f0; border-radius: 7px; margin-bottom: 12px; page-break-inside: avoid; }
        .check-head { background-color: #f8fafc; padding: 9px 12px; border-bottom: 1px solid #e2e8f0;
                      border-radius: 7px 7px 0 0; }
        .check-head .name { font-weight: bold; font-size: 12px; color: #0f2044; }
        .check-body { padding: 4px 12px 10px; }

        .section-title { font-weight: bold; color: #0d9488; font-size: 10.5px;
                         margin: 9px 0 3px; text-transform: uppercase; letter-spacing: .3px; }
        .kv td { padding: 3px 6px; vertical-align: top; }
        .kv .k { color: #64748b; font-size: 9.5px; text-transform: uppercase; letter-spacing: .2px; width: 42%; }
        .kv .v { color: #1e293b; font-size: 11px; font-weight: bold; }

        .docs { margin-top: 8px; }
        .docs .h { color: #64748b; font-size: 9.5px; text-transform: uppercase; letter-spacing: .2px; margin-bottom: 3px; }
        .doc { padding: 4px 8px; background-color: #f8fafc; border: 1px solid #eef2f6;
               border-radius: 4px; margin-bottom: 3px; font-size: 10.5px; }
        .doc a { color: #0d9488; text-decoration: none; font-weight: bold; }
        .doc .meta2 { color: #94a3b8; font-size: 9px; }

        .notes { background-color: #fffbeb; border: 1px solid #fde68a; padding: 7px 9px;
                 border-radius: 5px; font-size: 10.5px; margin-top: 8px; color: #713f12; }
        .empty { color: #94a3b8; font-style: italic; font-size: 10.5px; }
    </style>
</head>
<body>

<footer>
    Confidential — Orbit Verify · {{ $case->case_number }} · Generated {{ now()->format('d M Y, h:i A') }}
</footer>

{{-- Header band --}}
<div class="band">
    <h1>Background Verification Report</h1>
    <div class="sub">Orbit Verify &nbsp;·&nbsp; {{ $case->case_number }}</div>
    @php [$ot, $ob] = $badge($case->status); @endphp
    <div class="tag">
        Overall status:
        <span class="badge" style="color:{{ $ot }}; background-color:{{ $ob }};">{{ $case->status }}</span>
        &nbsp;·&nbsp; {{ $cleared }}/{{ $case->checks->count() }} checks cleared
    </div>
</div>

{{-- Candidate & case --}}
<h2>Candidate &amp; Case Details</h2>
<table class="meta">
    <tr>
        <td class="label">Candidate</td><td class="val">{{ $case->candidate_name }}</td>
        <td class="label">Case Number</td><td class="val">{{ $case->case_number }}</td>
    </tr>
    <tr>
        <td class="label">Father's Name</td><td class="val">{{ $case->candidate_father_name ?: '—' }}</td>
        <td class="label">Status</td><td class="val">{{ $case->status }}</td>
    </tr>
    <tr>
        <td class="label">Email</td><td class="val">{{ $case->candidate_email ?: '—' }}</td>
        <td class="label">Organization</td><td class="val">{{ $case->clientOrganization->name ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Phone</td><td class="val">{{ $case->candidate_phone ?: '—' }}</td>
        <td class="label">Initiated</td><td class="val">{{ $case->created_at->format('d M Y') }}</td>
    </tr>
    <tr>
        <td class="label">Designation</td><td class="val">{{ $case->candidate_designation ?: '—' }}</td>
        <td class="label">Report Closed</td><td class="val">{{ optional($case->report_closed_date)->format('d M Y') ?: '—' }}</td>
    </tr>
</table>

{{-- Summary --}}
<h2>Verification Summary</h2>
<table class="summary">
    <tr><th style="width:55%">Check</th><th style="width:30%">Status</th><th style="width:15%">Documents</th></tr>
    @foreach($case->checks as $check)
    @php [$t, $b] = $badge($check->status); @endphp
    <tr>
        <td>{{ ucwords(str_replace('_', ' ', $check->check_type)) }}</td>
        <td><span class="badge" style="color:{{ $t }}; background-color:{{ $b }};">{{ $check->status }}</span></td>
        <td>{{ $check->documents->count() }}</td>
    </tr>
    @endforeach
</table>

{{-- Detailed checks --}}
<h2>Check Details</h2>
@foreach($case->checks as $check)
@php
    $schema = $schemas[$check->check_type] ?? [];
    [$t, $b] = $badge($check->status);
@endphp
<div class="check">
    <div class="check-head">
        <table><tr>
            <td class="name">{{ ucwords(str_replace('_', ' ', $check->check_type)) }}</td>
            <td style="text-align:right;">
                <span class="badge" style="color:{{ $t }}; background-color:{{ $b }};">{{ $check->status }}</span>
            </td>
        </tr></table>
    </div>
    <div class="check-body">
        @php $rendered = false; @endphp
        @foreach($schema as $section => $fields)
            @php
                $values  = $check->form_data[$section] ?? [];
                $visible = collect($fields)
                    ->reject(fn ($f) => ($f['type'] ?? '') === 'file' || str_starts_with($f['name'], '_file'))
                    ->filter(fn ($f) => ($values[$f['name']] ?? '') !== '' && ($values[$f['name']] ?? null) !== null);
            @endphp
            @if($visible->isNotEmpty())
                @php $rendered = true; @endphp
                @if($section !== '_self')<div class="section-title">{{ $section }}</div>@endif
                <table class="kv">
                    @foreach($visible as $field)
                    <tr>
                        <td class="k">{{ $field['label'] ?? $field['name'] }}</td>
                        <td class="v">{{ $values[$field['name']] }}</td>
                    </tr>
                    @endforeach
                </table>
            @endif
        @endforeach
        @unless($rendered)<div class="empty">No details submitted for this check.</div>@endunless

        {{-- Document links --}}
        @if($check->documents->isNotEmpty())
        <div class="docs">
            <div class="h">Uploaded Documents ({{ $check->documents->count() }})</div>
            @foreach($check->documents as $doc)
            <div class="doc">
                <a href="{{ route('documents.view', $doc) }}">{{ $doc->original_name }}</a>
                <span class="meta2">
                    &nbsp;· {{ $doc->formatted_size }}{{ $doc->uploaded_by_role === 'admin' ? ' · Verified' : '' }}
                </span>
            </div>
            @endforeach
        </div>
        @endif

        @if($check->comments)
        <div class="notes"><strong>Verification notes:</strong> {{ $check->comments }}</div>
        @endif
    </div>
</div>
@endforeach

</body>
</html>
