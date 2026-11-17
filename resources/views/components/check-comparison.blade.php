@props(['schema' => [], 'stated' => [], 'verified' => [], 'includeUnmapped' => true])
@php
    $displayValue = fn ($value) => is_array($value) ? implode(', ', array_map(fn ($item) => is_scalar($item) ? (string) $item : json_encode($item), $value)) : (string) $value;
    $sections = collect(array_keys($schema));
    if ($includeUnmapped) {
        $sections = $sections->merge(array_keys($stated))->merge(array_keys($verified))->unique();
    }
    $hasRows = false;
@endphp
<div class="check-comparison">
    <div class="check-comparison-heading">
        <div>
            <h3>Verification details</h3>
            <p>Candidate information and agency findings, side by side.</p>
        </div>
        <span class="check-comparison-legend"><span></span> Agency findings</span>
    </div>
    @foreach($sections as $section)
        @php
            $fields = collect($schema[$section] ?? [])->keyBy('name');
            foreach ($includeUnmapped ? array_unique(array_merge(array_keys($stated[$section] ?? []), array_keys($verified[$section] ?? []))) : [] as $name) {
                if (!$fields->has($name)) {
                    $fields->put($name, ['name' => $name, 'label' => ucwords(str_replace('_', ' ', $name))]);
                }
            }
            $sectionLabel = $fields->first()['section_label'] ?? $section;
            $visible = $fields->reject(fn ($field) => ($field['type'] ?? (str_starts_with($field['name'], '_file') ? 'file' : 'text')) === 'file')
                ->filter(fn ($field) => (($stated[$section][$field['name']] ?? null) !== null && ($stated[$section][$field['name']] ?? '') !== '') || (($verified[$section][$field['name']] ?? null) !== null && ($verified[$section][$field['name']] ?? '') !== ''));
        @endphp
        @if($visible->isNotEmpty())
            @php $hasRows = true; @endphp
            <div class="check-comparison-section">
                @if($section !== '_self' || $sectionLabel !== '_self')<h4>{{ $sectionLabel }}</h4>@endif
                <div class="check-comparison-labels" aria-hidden="true"><span>Parameter</span><span>Candidate stated</span><span>Agency verified</span></div>
                @foreach($visible as $field)
                    @php
                        $statedValue = $stated[$section][$field['name']] ?? null;
                        $verifiedValue = $verified[$section][$field['name']] ?? null;
                        if (($field['type'] ?? '') === 'checkbox') {
                            $statedValue = $statedValue === null || $statedValue === '' ? $statedValue : (in_array($statedValue, [true, 1, '1', 'yes', 'on'], true) ? 'Yes' : 'No');
                            $verifiedValue = $verifiedValue === null || $verifiedValue === '' ? $verifiedValue : (in_array($verifiedValue, [true, 1, '1', 'yes', 'on'], true) ? 'Yes' : 'No');
                        }
                    @endphp
                    <div class="check-comparison-row">
                        <div class="check-comparison-field">{{ $field['label'] ?? $field['name'] }}</div>
                        <div class="check-comparison-value"><span class="check-comparison-mobile-label">Candidate stated</span>{{ $statedValue !== null && $statedValue !== '' ? $displayValue($statedValue) : 'Not provided' }}</div>
                        <div class="check-comparison-value check-comparison-verified"><span class="check-comparison-mobile-label">Agency verified</span>{{ $verifiedValue !== null && $verifiedValue !== '' ? $displayValue($verifiedValue) : 'Awaiting finding' }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    @endforeach
    @unless($hasRows)<p class="check-comparison-empty">Details will appear here when candidate information or verified findings are added.</p>@endunless
</div>
