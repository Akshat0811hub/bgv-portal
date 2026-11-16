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
                        <div class="sm:col-span-2" x-data="{
                            files: [],
                            dragging: false,
                            handleDrop(e) {
                                this.dragging = false;
                                const dt = e.dataTransfer;
                                if (dt && dt.files && dt.files.length) {
                                    this.$refs.fileInput.files = dt.files;
                                    this.files = [...dt.files];
                                }
                            }
                        }">
                            <label class="form-label" for="{{ $inputId }}">{{ $label }}</label>
                            <div class="border-2 border-dashed rounded-xl p-5 text-center transition-colors cursor-pointer"
                                 :class="dragging ? 'border-[#0d9488] bg-teal-50/50' : (files.length ? 'border-emerald-400 bg-emerald-50/20' : 'border-[#cbd5e1] hover:border-[#0d9488] bg-[#f8fafc]/50')"
                                 @click="$refs.fileInput.click()"
                                 @dragover.prevent="dragging = true"
                                 @dragleave.prevent="dragging = false"
                                 @drop.prevent="handleDrop($event)">
                                <input type="file" x-ref="fileInput" multiple accept=".pdf,.jpg,.jpeg,.png,.webp"
                                       id="{{ $inputId }}"
                                       name="check_files[{{ $slug }}][{{ $name }}][]"
                                       class="hidden"
                                       @click.stop
                                       @change="files = [...$event.target.files]">
                                <div class="pointer-events-none">
                                    <svg class="w-8 h-8 mx-auto mb-2 text-[#0d9488]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                    <span class="text-sm font-semibold text-[#0f2044]">Click to choose files or drag &amp; drop here</span>
                                    <span class="block text-xs text-[#64748b] mt-1">Supported: PDF, JPG, PNG, WebP (Max 10MB per file)</span>
                                </div>
                                <div class="mt-3 space-y-1.5" x-show="files.length > 0" style="display:none">
                                    <template x-for="(f, i) in files" :key="i">
                                        <div class="flex items-center justify-between text-xs font-medium text-emerald-800 bg-emerald-100/80 border border-emerald-300 rounded-lg px-3 py-1.5 text-left">
                                            <span class="truncate" x-text="'✓ ' + f.name"></span>
                                            <span class="text-[11px] text-emerald-700 ml-2 shrink-0" x-text="(f.size / 1024).toFixed(1) + ' KB'"></span>
                                        </div>
                                    </template>
                                </div>
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
