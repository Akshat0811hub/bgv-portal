<x-layouts.app title="Customize Check Fields">

<div class="page-header">
    <div>
        <h1 class="page-title">Check Fields Manager</h1>
        <p class="page-subtitle">Control which fields appear on each verification check globally (remove Aadhaar/Passport, rename labels, or add custom fields).</p>
    </div>
</div>

<div class="space-y-4">
    @foreach($checks as $idx => $check)
    @php $schema = $check->schema ?? []; @endphp
    <div class="card overflow-hidden" x-data="{ open: {{ $idx === 0 ? 'true' : 'false' }}, newFields: [] }">
        <div class="card-header cursor-pointer hover:bg-slate-50/70 transition-colors" @click="open = !open">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-[#0f2044]/5 text-[#0f2044] flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                </div>
                <div>
                    <div class="font-semibold text-[#0f2044]">{{ $check->name }}</div>
                    <div class="text-xs text-[#64748b]">
                        {{ collect($schema)->flatten(1)->count() }} active fields · Slug: <span class="font-mono">{{ $check->slug }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="{{ $check->active ? 'badge-clear' : 'badge-onhold' }}">{{ $check->active ? 'Active' : 'Disabled' }}</span>
                <svg class="w-4 h-4 text-[#94a3b8] transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>

        <div x-show="open" @if($idx !== 0) style="display:none" @endif>
            <div class="card-body border-t border-slate-100 space-y-4">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-[#64748b]">Uncheck any field to remove it from future cases &amp; candidate upload forms, or click <strong>+ Add Custom Field</strong>.</p>
                    <form method="POST" action="{{ route('admin.checks.update', $check) }}">
                        @csrf @method('PUT')
                        <input type="hidden" name="reset_default" value="1">
                        <button type="submit" class="text-xs font-medium text-rose-600 hover:underline">Reset to System Default</button>
                    </form>
                </div>

                <form method="POST" action="{{ route('admin.checks.update', $check) }}" class="space-y-3">
                    @csrf @method('PUT')
                    <input type="hidden" name="active" value="1">
                    @php $fieldIdx = 0; @endphp
                    <div class="space-y-2">
                        @foreach($schema as $secName => $secFields)
                            @foreach($secFields as $fld)
                                @php
                                    $fType = $fld['type'] ?? (str_starts_with($fld['name'] ?? '', '_file') ? 'file' : 'text');
                                    $fOpts = isset($fld['options']) && is_array($fld['options']) ? implode(', ', $fld['options']) : '';
                                @endphp
                                <div class="flex flex-wrap items-center gap-2.5 bg-slate-50/70 border border-slate-200 rounded-lg px-3 py-2">
                                    <input type="checkbox" name="existing_fields[{{ $fieldIdx }}][enabled]" value="1" checked
                                           class="rounded text-[#0d9488] focus:ring-[#0d9488]">
                                    <input type="hidden" name="existing_fields[{{ $fieldIdx }}][section]" value="{{ $secName }}">
                                    <input type="hidden" name="existing_fields[{{ $fieldIdx }}][name]" value="{{ $fld['name'] }}">
                                    <input type="hidden" name="existing_fields[{{ $fieldIdx }}][type]" value="{{ $fType }}">
                                    <input type="hidden" name="existing_fields[{{ $fieldIdx }}][options]" value="{{ $fOpts }}">
                                    <input type="text" name="existing_fields[{{ $fieldIdx }}][label]" value="{{ $fld['label'] ?? $fld['name'] }}"
                                           class="form-input !py-1.5 !text-xs flex-1 min-w-[180px]" required>
                                    <span class="text-[10px] font-mono uppercase px-2 py-0.5 rounded bg-white border border-slate-200 text-slate-600">{{ $fType }}</span>
                                    @if($secName !== '_self')
                                        <span class="text-[10px] px-2 py-0.5 rounded bg-blue-50 text-blue-700">{{ $secName }}</span>
                                    @endif
                                </div>
                                @php $fieldIdx++; @endphp
                            @endforeach
                        @endforeach
                    </div>

                    <template x-for="(nf, nIdx) in newFields" :key="nIdx">
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 bg-teal-50/40 border border-teal-300 rounded-lg p-2.5 items-center">
                            <div class="sm:col-span-4">
                                <input type="text" :name="'new_fields[' + nIdx + '][label]'" x-model="nf.label" placeholder="Custom field label (e.g. UAN Number)"
                                       class="form-input !py-1.5 !text-xs w-full" required>
                            </div>
                            <div class="sm:col-span-3">
                                <select :name="'new_fields[' + nIdx + '][type]'" x-model="nf.type" class="form-select !py-1.5 !text-xs w-full">
                                    <option value="text">Text Input</option>
                                    <option value="date">Date Picker</option>
                                    <option value="file">File Upload</option>
                                    <option value="radio">Radio Options</option>
                                </select>
                            </div>
                            <div class="sm:col-span-4">
                                <input type="text" :name="'new_fields[' + nIdx + '][section]'" x-model="nf.section" placeholder="Section (_self or name)"
                                       class="form-input !py-1.5 !text-xs w-full" x-show="nf.type !== 'radio'">
                                <input type="text" :name="'new_fields[' + nIdx + '][options]'" x-model="nf.options" placeholder="Options (comma separated)"
                                       class="form-input !py-1.5 !text-xs w-full" x-show="nf.type === 'radio'">
                            </div>
                            <div class="sm:col-span-1 text-right">
                                <button type="button" @click="newFields.splice(nIdx, 1)" class="text-rose-500 hover:text-rose-700 text-xs font-bold px-2 py-1">✕</button>
                            </div>
                        </div>
                    </template>

                    <div class="flex flex-wrap items-center justify-between gap-2 pt-2">
                        <button type="button" @click="newFields.push({ label: '', type: 'text', section: '_self', options: '' })"
                                class="btn-outline btn-sm">
                            + Add Custom Field
                        </button>
                        <button type="submit" class="btn-teal btn-sm">
                            Save Default Fields
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>

</x-layouts.app>
