@php
    $sections = $schema;
    // Keep historical/custom values visible even after a template changes.
    foreach (is_array($check->custom_schema) ? [] : array_unique(array_merge(array_keys($check->form_data ?? []), array_keys($check->verified_data ?? []))) as $section) {
        $fields = $sections[$section] ?? [];
        $known = array_column($fields, 'name');
        foreach (array_unique(array_merge(array_keys($check->form_data[$section] ?? []), array_keys($check->verified_data[$section] ?? []))) as $key) {
            if (!in_array($key, $known, true) && !str_starts_with($key, '_file')) {
                $fields[] = ['name' => $key, 'label' => ucwords(str_replace('_', ' ', $key)), 'type' => 'text'];
            }
        }
        $sections[$section] = $fields;
    }
    $formatValue = static fn ($value) => is_array($value) ? implode(', ', array_map(static fn ($v) => is_scalar($v) ? (string) $v : json_encode($v), $value)) : (string) $value;
    $hasValue = static fn ($value) => $value !== null && $value !== '' && $value !== [];
@endphp
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">@include('reports.styles')</head><body>
<div class="brand">ORBIT <span>VERIFY</span><small>{{ $case->case_number }} / {{ $case->candidate_name }}</small></div>
<div class="check-top"><div class="eyebrow">CHECK {{ str_pad((string) $number, 2, '0', STR_PAD_LEFT) }}</div><h1>{{ $check->display_name }}</h1><span class="status">{{ $check->status }}</span> <span class="muted"> / {{ $check->completed_at ? 'Completed '.$check->completed_at->format('d M Y, h:i A') : 'Verification in progress' }}</span></div>
@php $rendered = false; @endphp
@foreach($sections as $section => $fields)
    @php
        $stated = $check->form_data[$section] ?? [];
        $verified = $check->verified_data[$section] ?? [];
        $visible = collect($fields)->reject(fn ($f) => ($f['type'] ?? (str_starts_with($f['name'], '_file') ? 'file' : 'text')) === 'file')
            ->filter(fn ($f) => $hasValue($stated[$f['name']] ?? null) || $hasValue($verified[$f['name']] ?? null));
    @endphp
    @if($visible->isNotEmpty())
    @php $rendered = true; @endphp
    <h3>{{ $fields[0]['section_label'] ?? ($section === '_self' ? 'Verification details' : $section) }}</h3>
    <table class="comparison"><thead><tr><th style="width:28%">Parameter</th><th style="width:36%">Candidate stated details</th><th style="width:36%">Agency verified findings</th></tr></thead><tbody>
    @foreach($visible as $field)
        @php
            $s = $stated[$field['name']] ?? null; $v = $verified[$field['name']] ?? null;
            $mismatch = in_array($check->status, ['Discrepancy Found', 'Failed']) && $hasValue($s) && $hasValue($v) && mb_strtolower(trim($formatValue($s))) !== mb_strtolower(trim($formatValue($v)));
        @endphp
        <tr><td class="parameter">{{ $field['label'] ?? $field['name'] }}</td><td>{{ $hasValue($s) ? (($field['type'] ?? '') === 'checkbox' ? ($s ? 'Yes' : 'No') : $formatValue($s)) : 'Not provided' }}</td><td class="{{ $mismatch ? 'mismatch' : 'verified' }}">{{ $hasValue($v) ? (($field['type'] ?? '') === 'checkbox' ? ($v ? 'Yes' : 'No') : $formatValue($v)) : 'Not recorded' }}</td></tr>
    @endforeach
    </tbody></table>
    @endif
@endforeach
@unless($rendered)<p class="empty">No structured details have been recorded for this check.</p>@endunless
<h2>Verifier remarks</h2><div class="remarks">{{ $check->comments ?: 'No remarks recorded.' }}</div>
<h2>Supporting evidence / {{ $check->documents->count() }} files</h2>
@if($check->documents->isNotEmpty())
<p class="muted">Available previews follow this check. Files without a preview are listed with links to their originals. Sign in to Orbit Verify with access to this case to open original files.</p>
@include('reports.document-list', ['documents' => $check->documents])
@else<p class="empty">No supporting files uploaded for this check.</p>@endif
</body></html>
