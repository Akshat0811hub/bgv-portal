@props(['check', 'schema' => []])
<div class="space-y-4 mb-4">
@foreach($schema as $section => $fields)
    @php $sectionLabel = $fields[0]['section_label'] ?? ($section === '_self' ? null : $section); @endphp
    <div class="rounded-xl border border-slate-200 bg-slate-50/40 p-4">
        @if($sectionLabel)<h4 class="text-xs font-bold uppercase tracking-wider text-[#0f2044] mb-3">{{ $sectionLabel }}</h4>@endif
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @foreach($fields as $field)
            @php
                $type = $field['type'] ?? (str_starts_with($field['name'], '_file') ? 'file' : 'text');
                $stated = $check->form_data[$section][$field['name']] ?? '';
                $value = $check->verified_data[$section][$field['name']] ?? '';
                $stated = is_array($stated) ? implode(', ', $stated) : (string) $stated;
                $value = is_array($value) ? implode(', ', $value) : (string) $value;
                $name = "verified_data[$section][{$field['name']}]";
                $id = 'verified-'.$check->id.'-'.Illuminate\Support\Str::slug($section.'-'.$field['name']);
            @endphp
            @continue($type === 'file')
            <div @class(['sm:col-span-2' => $type === 'textarea'])>
                <label class="form-label" for="{{ $id }}">{{ $field['label'] ?? $field['name'] }}</label>
                @if($stated !== '')<p class="text-xs text-slate-500 mb-1.5 break-words">Stated: {{ $type === 'checkbox' ? (in_array($stated, ['1', 'true', 'yes'], true) ? 'Yes' : 'No') : $stated }}</p>@endif
                @if($type === 'textarea')
                    <textarea id="{{ $id }}" name="{{ $name }}" rows="3" data-stated-val="{{ $stated }}" placeholder="{{ $field['placeholder'] ?? 'Enter verified finding' }}" class="form-textarea">{{ $value }}</textarea>
                @elseif(in_array($type, ['radio', 'select'], true))
                    <select id="{{ $id }}" name="{{ $name }}" data-stated-val="{{ $stated }}" class="form-select">
                        <option value="">Select a finding</option>
                        @if($value !== '' && !in_array($value, $field['options'] ?? [], true))<option value="{{ $value }}" selected>{{ $value }}</option>@endif
                        @foreach($field['options'] ?? [] as $option)<option value="{{ $option }}" @selected($value === $option)>{{ $option }}</option>@endforeach
                    </select>
                @elseif($type === 'checkbox')
                    <input type="hidden" name="{{ $name }}" value="0">
                    <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="1" data-stated-val="{{ $stated }}" @checked(in_array($value, ['1', 'true', 'yes'], true)) class="rounded text-teal-600">
                @else
                    <input type="{{ in_array($type, ['number','email','tel','date'], true) ? $type : 'text' }}" @if($type === 'number') step="any" @endif id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" data-stated-val="{{ $stated }}" placeholder="{{ $field['placeholder'] ?? 'Enter verified finding' }}" class="form-input">
                @endif
                @if(!empty($field['help']))<p class="text-xs text-slate-500 mt-1.5">{{ $field['help'] }}</p>@endif
            </div>
        @endforeach
        </div>
    </div>
@endforeach
</div>
