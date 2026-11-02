@props([
    'schema' => [],  // schema array keyed by section
    'data' => [],    // form_data nested by section
])

{{-- Read-only, schema-aware rendering of submitted check data. --}}
@php
    $hasAny = collect($schema)->flatten(1)->contains(function ($field) use ($data) {
        foreach ($data as $section) {
            if (is_array($section) && !empty($section[$field['name']] ?? null)) return true;
        }
        return false;
    });
@endphp

@if($hasAny)
<div class="space-y-4">
    @foreach($schema as $section => $fields)
        @php
            $isSelf  = ($section === '_self');
            $values  = $data[$section] ?? [];
            $visible = collect($fields)->reject(fn ($f) => ($f['type'] ?? '') === 'file' || str_starts_with($f['name'], '_file'))
                                       ->filter(fn ($f) => !empty($values[$f['name']] ?? null));
        @endphp
        @if($visible->isNotEmpty())
        <div>
            @unless($isSelf)
                <p class="text-xs font-semibold text-[#0d9488] uppercase tracking-wide mb-2">{{ $section }}</p>
            @endunless
            <div class="bg-[#f8fafc] rounded-lg p-4 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
                @foreach($visible as $field)
                <div>
                    <div class="text-xs text-[#94a3b8] uppercase tracking-wide">{{ $field['label'] ?? $field['name'] }}</div>
                    <div class="text-sm font-medium text-[#1e293b] mt-0.5 break-words">{{ $values[$field['name']] }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    @endforeach
</div>
@else
    <p class="text-sm text-[#94a3b8] italic">No details submitted yet.</p>
@endif
