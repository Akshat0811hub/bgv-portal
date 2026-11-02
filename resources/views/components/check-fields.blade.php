@props([
    'slug',          // check slug — namespaces the field names
    'schema' => [],  // schema array: [ section => [ {name,label,type,options,placeholder}, ... ] ]
    'data' => [],    // existing form_data for prefill, nested: [ section => [ field => value ] ]
])

{{--
    Renders schema-driven input fields for a single check.
    Text/date/radio values post as:  check_data[{slug}][{section}][{field}]
    File fields post as:             check_files[{slug}][{field}][]   (multiple)
--}}
<div class="space-y-5">
    @foreach($schema as $section => $fields)
        @php $isSelf = ($section === '_self'); @endphp

        <div @class(['rounded-lg border border-[#e2e8f0] p-4' => !$isSelf])>
            @unless($isSelf)
                <h4 class="text-sm font-semibold text-[#0f2044] mb-3">{{ $section }}</h4>
            @endunless

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($fields as $field)
                    @php
                        $name    = $field['name'];
                        $label   = $field['label'] ?? \Illuminate\Support\Str::headline($name);
                        $type    = $field['type'] ?? 'text';
                        $isFile  = $type === 'file' || str_starts_with($name, '_file');
                        $value   = data_get($data, "$section.$name", '');
                        $inputId = \Illuminate\Support\Str::slug("$slug-$section-$name");
                    @endphp

                    @if($isFile)
                        {{-- File upload --}}
                        <div class="sm:col-span-2" x-data="{ files: [] }">
                            <label class="form-label" for="{{ $inputId }}">{{ $label }}</label>
                            <div class="border-2 border-dashed border-[#d1d5db] rounded-lg p-4 text-center hover:border-[#0d9488] transition-colors"
                                 @dragover.prevent @drop.prevent="files = [...$event.dataTransfer.files]">
                                <input type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.webp"
                                       id="{{ $inputId }}"
                                       name="check_files[{{ $slug }}][{{ $name }}][]"
                                       class="hidden"
                                       @change="files = [...$event.target.files]">
                                <label for="{{ $inputId }}" class="cursor-pointer block">
                                    <span class="text-sm font-medium text-[#1e293b]">Click to upload or drag &amp; drop</span>
                                    <span class="block text-xs text-[#64748b] mt-1">PDF, JPG, PNG, WebP — max 10MB each</span>
                                </label>
                                <template x-for="(f, i) in files" :key="i">
                                    <div class="mt-2 text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded px-2 py-1 text-left truncate" x-text="f.name"></div>
                                </template>
                            </div>
                        </div>

                    @elseif($type === 'radio')
                        {{-- Radio group --}}
                        <div class="sm:col-span-2">
                            <span class="form-label">{{ $label }}</span>
                            <div class="flex flex-wrap gap-x-5 gap-y-2 mt-1">
                                @foreach(($field['options'] ?? []) as $opt)
                                <label class="inline-flex items-center gap-2 text-sm text-[#374151] cursor-pointer">
                                    <input type="radio"
                                           name="check_data[{{ $slug }}][{{ $section }}][{{ $name }}]"
                                           value="{{ $opt }}"
                                           class="text-[#0d9488] border-[#d1d5db] focus:ring-[#0d9488]"
                                           @checked((string) $value === (string) $opt)>
                                    {{ $opt }}
                                </label>
                                @endforeach
                            </div>
                        </div>

                    @else
                        {{-- Text / date --}}
                        <div @class(['sm:col-span-2' => in_array($name, ['address', 'reason', 'handles'])])>
                            <label class="form-label" for="{{ $inputId }}">{{ $label }}</label>
                            <input type="{{ $type === 'date' ? 'date' : 'text' }}"
                                   id="{{ $inputId }}"
                                   name="check_data[{{ $slug }}][{{ $section }}][{{ $name }}]"
                                   value="{{ $value }}"
                                   placeholder="{{ $field['placeholder'] ?? '' }}"
                                   class="form-input">
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach
</div>
