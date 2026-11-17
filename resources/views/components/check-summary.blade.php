@props(['schema' => [], 'data' => [], 'includeUnmapped' => true])
@php
    $sections = $schema;
    if ($includeUnmapped) {
        foreach ($data as $section => $values) {
            $fields = $sections[$section] ?? [];
            $names = array_column($fields, 'name');
            foreach (array_keys(is_array($values) ? $values : []) as $name) {
                if (!in_array($name, $names, true)) {
                    $fields[] = ['name' => $name, 'label' => ucwords(str_replace('_', ' ', $name))];
                }
            }
            $sections[$section] = $fields;
        }
    }
    $hasValue = static fn ($value) => $value !== null && $value !== '' && $value !== [];
    $formatValue = static fn ($value) => is_array($value) ? implode(', ', array_map(static fn ($part) => is_scalar($part) ? (string) $part : json_encode($part), $value)) : (string) $value;
    $hasAny = false;
@endphp
<div class="space-y-4">
    @foreach($sections as $section => $fields)
        @php
            $values = $data[$section] ?? [];
            $sectionLabel = $fields[0]['section_label'] ?? $section;
            $visible = collect($fields)->reject(fn ($field) => ($field['type'] ?? (str_starts_with($field['name'], '_file') ? 'file' : 'text')) === 'file')
                ->filter(fn ($field) => $hasValue($values[$field['name']] ?? null));
        @endphp
        @if($visible->isNotEmpty())
        @php $hasAny = true; @endphp
        <div>
            @if($section !== '_self' || $sectionLabel !== '_self')
                <p class="text-xs font-semibold text-[#0d9488] uppercase tracking-wide mb-2">{{ $sectionLabel }}</p>
            @endif
            <div class="bg-[#f8fafc] rounded-lg p-4 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
                @foreach($visible as $field)
                @php
                    $value = $values[$field['name']];
                    if (($field['type'] ?? '') === 'checkbox') {
                        $value = in_array($value, [true, 1, '1', 'yes', 'on'], true) ? 'Yes' : 'No';
                    }
                @endphp
                <div>
                    <div class="text-xs text-[#94a3b8] uppercase tracking-wide">{{ $field['label'] ?? $field['name'] }}</div>
                    <div class="text-sm font-medium text-[#1e293b] mt-0.5 break-words whitespace-pre-wrap">{{ $formatValue($value) }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    @endforeach
    @unless($hasAny)<p class="text-sm text-[#94a3b8] italic">No details submitted yet.</p>@endunless
</div>
