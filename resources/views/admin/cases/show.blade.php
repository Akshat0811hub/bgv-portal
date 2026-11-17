<x-layouts.app :title="'Case — ' . $case->case_number">

{{-- Header --}}
<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.cases.index') }}" class="p-2 rounded-lg border border-[#e2e8f0] bg-white text-[#64748b] hover:text-[#0f2044] hover:bg-[#f8fafc] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="page-title font-mono">{{ $case->case_number }}</h1>
                <x-status-badge :status="$case->status" />
            </div>
            <p class="page-subtitle">{{ $case->candidate_name }} · {{ $case->clientOrganization->name ?? '—' }}</p>
        </div>
    </div>
    <div class="flex items-center gap-2.5">
        <a href="{{ route('admin.cases.report', $case) }}" class="btn-teal">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Download Complete Report
        </a>
    </div>
</div>

<div class="case-workspace grid grid-cols-1 xl:grid-cols-3 gap-5" x-data="{ expandAll: true }">

    {{-- Checks column --}}
    <div class="xl:col-span-2 space-y-4">

        <x-case-progress :case="$case" />

        @forelse($case->checks as $idx => $check)
        @php
            $schema = $check->effectiveSchema($schemas[$check->check_type] ?? []);
            $statusStyle = match($check->status) {
                'Clear', 'Completed' => 'bg-emerald-50 text-emerald-700 border-emerald-300 focus:ring-emerald-400',
                'Failed', 'Discrepancy Found' => 'bg-rose-50 text-rose-700 border-rose-300 focus:ring-rose-400',
                'Pending' => 'bg-amber-50 text-amber-700 border-amber-300 focus:ring-amber-400',
                'Amber' => 'bg-orange-50 text-orange-700 border-orange-300 focus:ring-orange-400',
                'Insufficiency' => 'bg-purple-50 text-purple-700 border-purple-300 focus:ring-purple-400',
                'In Progress' => 'bg-blue-50 text-blue-700 border-blue-300 focus:ring-blue-400',
                default => 'bg-amber-50 text-amber-700 border-amber-300 focus:ring-amber-400',
            };
        @endphp
        <div class="card overflow-hidden" x-data="{ open: {{ $idx === 0 || in_array($check->status, ['In Progress', 'Pending', 'Insufficiency']) ? 'true' : 'false' }}, customizeOpen: false, newFields: [] }">
            <div class="w-full card-header hover:bg-[#f8fafc]/80 transition-colors flex flex-wrap items-center justify-between gap-3 cursor-pointer" @click="open = !open">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl
                        @if(in_array($check->status, ['Clear', 'Completed'])) bg-emerald-100 text-emerald-600
                        @elseif(in_array($check->status, ['Discrepancy Found', 'Failed'])) bg-red-100 text-red-600
                        @elseif($check->status === 'Amber') bg-orange-100 text-orange-600
                        @elseif($check->status === 'Insufficiency') bg-purple-100 text-purple-600
                        @elseif($check->status === 'In Progress') bg-blue-100 text-blue-600
                        @else bg-[#f1f5f9] text-[#64748b] @endif
                        flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="font-semibold text-[#0f2044] truncate">{{ ucwords(str_replace('_', ' ', $check->check_type)) }}</div>
                        <div class="text-xs text-[#64748b] flex items-center gap-2 mt-0.5">
                            @if($check->completed_at)
                                <span>Verified {{ $check->completed_at->format('d M Y, h:i A') }}</span>
                            @else
                                <span>Awaiting verification</span>
                            @endif
                            @if($check->documents->count() > 0)
                                <span>· {{ $check->documents->count() }} {{ $check->documents->count() === 1 ? 'file' : 'files' }}</span>
                            @endif
                            @if(is_array($check->custom_schema))
                                <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-teal-50 text-teal-700 border border-teal-200">Custom Fields</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2.5 shrink-0" @click.stop>
                    {{-- Quick Color-Coded Status Dropdown (Green=Passed, Orange=Pending, Red=Failed) --}}
                    <form method="POST" action="{{ route('admin.cases.checks.quick-status', [$case, $check]) }}" class="inline-flex items-center">
                        @csrf @method('PATCH')
                        <select name="status" onchange="this.form.submit()" title="Quick Status (Green: Passed, Orange: Pending, Red: Failed)"
                                class="text-xs font-semibold rounded-full border px-3 py-1.5 pr-7 cursor-pointer focus:outline-none focus:ring-2 transition-all {{ $statusStyle }}">
                            <option value="Clear" @selected($check->status === 'Clear') class="bg-white text-emerald-700 font-semibold">🟢 Passed (Green)</option>
                            <option value="Completed" @selected($check->status === 'Completed') class="bg-white text-emerald-700">Completed</option>
                            <option value="Pending" @selected($check->status === 'Pending') class="bg-white text-amber-700 font-semibold">🟠 Pending (Orange)</option>
                            <option value="Failed" @selected($check->status === 'Failed') class="bg-white text-rose-700 font-semibold">🔴 Failed (Red)</option>
                            <option disabled class="text-slate-300">──────────</option>
                            <option value="In Progress" @selected($check->status === 'In Progress') class="bg-white text-blue-700">🔵 In Progress</option>
                            <option value="Insufficiency" @selected($check->status === 'Insufficiency') class="bg-white text-purple-700">🟣 Insufficiency</option>
                            <option value="Amber" @selected($check->status === 'Amber') class="bg-white text-orange-700">🟠 Amber</option>
                            <option value="Discrepancy Found" @selected($check->status === 'Discrepancy Found') class="bg-white text-red-700">🔴 Discrepancy Found</option>
                        </select>
                    </form>
                    <button type="button" @click="open = !open" class="p-1 rounded-lg hover:bg-slate-100 text-[#94a3b8]">
                        <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                </div>
            </div>

            <div x-show="open">
                <div class="p-4 sm:p-6 space-y-6 bg-white">

                    {{-- Customize Check Fields Toolbar & Inline Editor --}}
                    <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-[#f1f5f9]">
                        <p class="section-label !mb-0">Check Configuration</p>
                        <button type="button" @click="customizeOpen = !customizeOpen"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg border transition-colors"
                                :class="customizeOpen ? 'bg-[#0f2044] text-white border-[#0f2044]' : 'bg-white text-[#0f2044] border-[#cbd5e1] hover:border-[#0d9488] hover:text-[#0d9488]'">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                            <span x-text="customizeOpen ? 'Close Field Editor' : 'Customize Fields (Add / Remove)'"></span>
                        </button>
                    </div>

                    <div x-show="customizeOpen" style="display:none" class="rounded-xl border border-teal-200 bg-teal-50/40 p-4 space-y-4">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <h4 class="text-sm font-bold text-[#0f2044]">Customize Fields for {{ ucwords(str_replace('_', ' ', $check->check_type)) }}</h4>
                                <p class="text-xs text-[#64748b]">Uncheck any field (like Aadhaar/Passport) to remove it for this case, or add your own custom fields below.</p>
                            </div>
                            @if(is_array($check->custom_schema))
                            <form method="POST" action="{{ route('admin.cases.checks.schema', [$case, $check]) }}">
                                @csrf @method('PUT')
                                <input type="hidden" name="reset_default" value="1">
                                <button type="submit" class="text-xs font-medium text-rose-600 hover:underline">Reset to Default</button>
                            </form>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('admin.cases.checks.schema', [$case, $check]) }}" class="space-y-3">
                            @csrf @method('PUT')
                            @php $fieldIdx = 0; @endphp
                            <div class="space-y-2">
                                @foreach($schema as $secName => $secFields)
                                    @foreach($secFields as $fld)
                                        @php
                                            $fType = $fld['type'] ?? (str_starts_with($fld['name'] ?? '', '_file') ? 'file' : 'text');
                                            $fOpts = isset($fld['options']) && is_array($fld['options']) ? implode(', ', $fld['options']) : '';
                                        @endphp
                                        <div class="flex flex-wrap items-center gap-2 bg-white border border-slate-200 rounded-lg px-3 py-2">
                                            <input type="checkbox" name="existing_fields[{{ $fieldIdx }}][enabled]" value="1" checked
                                                   class="rounded text-[#0d9488] focus:ring-[#0d9488]">
                                            <input type="hidden" name="existing_fields[{{ $fieldIdx }}][section]" value="{{ $secName }}">
                                            <input type="hidden" name="existing_fields[{{ $fieldIdx }}][name]" value="{{ $fld['name'] }}">
                                            <input type="hidden" name="existing_fields[{{ $fieldIdx }}][type]" value="{{ $fType }}">
                                            <input type="hidden" name="existing_fields[{{ $fieldIdx }}][options]" value="{{ $fOpts }}">
                                            <input type="text" name="existing_fields[{{ $fieldIdx }}][label]" value="{{ $fld['label'] ?? $fld['name'] }}"
                                                   class="form-input !py-1 !text-xs flex-1 min-w-[160px]" required>
                                            <span class="text-[10px] font-mono uppercase px-2 py-0.5 rounded bg-slate-100 text-slate-600">{{ $fType }}</span>
                                            @if($secName !== '_self')
                                                <span class="text-[10px] px-2 py-0.5 rounded bg-blue-50 text-blue-700">{{ $secName }}</span>
                                            @endif
                                        </div>
                                        @php $fieldIdx++; @endphp
                                    @endforeach
                                @endforeach
                            </div>

                            {{-- New Custom Fields --}}
                            <template x-for="(nf, nIdx) in newFields" :key="nIdx">
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 bg-white border border-teal-300 rounded-lg p-2.5 items-center">
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
                                        <input type="text" :name="'new_fields[' + nIdx + '][options]'" x-model="nf.options" placeholder="Options (comma separated: Yes, No)"
                                               class="form-input !py-1.5 !text-xs w-full" x-show="nf.type === 'radio'">
                                    </div>
                                    <div class="sm:col-span-1 text-right">
                                        <button type="button" @click="newFields.splice(nIdx, 1)" class="text-rose-500 hover:text-rose-700 text-xs font-bold px-2 py-1">✕</button>
                                    </div>
                                </div>
                            </template>

                            <div class="flex flex-wrap items-center justify-between gap-2 pt-2">
                                <button type="button" @click="newFields.push({ label: '', type: 'text', section: '_self', options: '' })"
                                        class="btn-outline btn-sm !text-xs">
                                    + Add Custom Field
                                </button>
                                <button type="submit" class="btn-teal btn-sm !text-xs">
                                    Save Custom Fields
                                </button>
                            </div>
                        </form>
                    </div>

                    <x-check-comparison :schema="$schema" :stated="$check->form_data ?? []" :verified="$check->verified_data ?? []" />

                    <x-check-documents :check="$check" :case="$case" :schema="$schema" />

                    {{-- Update form: verified details + status --}}
                    <div class="border-t border-[#e2e8f0] pt-5" x-data="{
                        copyFromStated() {
                            const form = $el.querySelector('form');
                            if (!form) return;
                            form.querySelectorAll('[data-stated-val]').forEach(input => {
                                const stated = input.getAttribute('data-stated-val');
                                if (stated && !input.value) {
                                    input.value = stated;
                                }
                            });
                        }
                    }">
                        <div class="flex items-center justify-between mb-3">
                            <p class="section-label !mb-0">Verifier Findings &amp; Outcome</p>
                            @if(!empty($check->form_data))
                            <button type="button" @click="copyFromStated()"
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#0d9488] hover:text-[#0f766e] bg-teal-50 hover:bg-teal-100/80 px-2.5 py-1 rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                Copy Stated Values to Verified
                            </button>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('admin.cases.checks.update', [$case, $check]) }}" enctype="multipart/form-data">
                            @csrf @method('PATCH')

                            {{-- Verified values mirroring the schema (admin's confirmed findings) --}}
                            @if(!empty($schema))
                            <div class="space-y-4 mb-4">
                                @foreach($schema as $section => $fields)
                                    @php $isSelf = ($section === '_self'); @endphp
                                    <div @class(['rounded-xl border border-[#e2e8f0] bg-[#f8fafc]/40 p-4' => !$isSelf])>
                                        @unless($isSelf)
                                            <h4 class="text-xs font-bold uppercase tracking-wider text-[#0f2044] mb-3">{{ $section }}</h4>
                                        @endunless
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            @foreach($fields as $field)
                                                @php
                                                    $isFile = ($field['type'] ?? '') === 'file' || str_starts_with($field['name'], '_file');
                                                    $statedVal = data_get($check->form_data, "$section.{$field['name']}", '');
                                                    $verifiedVal = data_get($check->verified_data, "$section.{$field['name']}", '');
                                                @endphp
                                                @continue($isFile)
                                                <div>
                                                    <label class="form-label flex items-center justify-between">
                                                        <span>{{ $field['label'] ?? $field['name'] }}</span>
                                                        @if($statedVal !== '' && $statedVal !== null)
                                                            <span class="text-[11px] font-normal text-[#64748b] truncate max-w-[160px]" title="Stated: {{ $statedVal }}">Stated: {{ $statedVal }}</span>
                                                        @endif
                                                    </label>
                                                    <input type="text"
                                                           name="verified_data[{{ $section }}][{{ $field['name'] }}]"
                                                           value="{{ $verifiedVal }}"
                                                           data-stated-val="{{ $statedVal }}"
                                                           placeholder="{{ $statedVal !== '' ? 'Confirm: ' . $statedVal : 'Enter verified finding…' }}"
                                                           class="form-input">
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @endif

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">Check Verification Status</label>
                                    <select name="status" class="form-select" required>
                                        @foreach(['Pending', 'In Progress', 'Insufficiency', 'Amber', 'Discrepancy Found', 'Clear', 'Failed', 'Completed'] as $s)
                                        <option value="{{ $s }}" @selected($check->status === $s)>{{ $s }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Attach Verified Proof / Evidence</label>
                                    <input type="file" name="verified_files[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.odt,.rtf,.txt,.csv,.xls,.xlsx,.ods,.ppt,.pptx,.odp,.odg,.odf,.fodt,.fods,.fodp" class="form-input !py-2">
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="form-label">Verifier Remarks / Notes (Printed on Report)</label>
                                <textarea name="comments" rows="2" class="form-textarea" placeholder="Add official verification remarks, reference details, or discrepancy explanation…">{{ $check->comments }}</textarea>
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <button type="submit" class="btn-teal">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Save Verified Findings
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Raise insufficiency: notify candidate to re-upload --}}
                    <div x-data="{ openInsuff: false }" class="border-t border-[#e2e8f0] pt-4 flex flex-wrap items-center justify-between gap-3">
                        <button type="button" @click="openInsuff = !openInsuff" class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-700 hover:text-amber-800 bg-amber-50 hover:bg-amber-100 px-3 py-1.5 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            Raise Insufficiency &amp; Request Re-upload from Candidate
                        </button>

                        @if($case->checks->count() > 1)
                        <form method="POST" action="{{ route('admin.cases.checks.destroy', [$case, $check]) }}"
                              onsubmit="return confirm('Remove this check and its attached documents from this case?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-1 text-xs font-medium text-red-600 hover:text-red-700 px-2.5 py-1.5 rounded-lg hover:bg-red-50 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Remove Check
                            </button>
                        </form>
                        @endif

                        <form method="POST" action="{{ route('admin.cases.checks.insufficiency', [$case, $check]) }}"
                              x-show="openInsuff" x-collapse style="display:none" class="w-full mt-2 bg-amber-50/50 border border-amber-200/80 rounded-xl p-4">
                            @csrf
                            <label class="form-label text-amber-900">Reason for Insufficiency (emailed to candidate)</label>
                            <textarea name="reason" rows="2" class="form-textarea" required placeholder="e.g. Uploaded Aadhaar card is blurry. Please upload a clear, legible copy."></textarea>
                            <div class="mt-2.5 flex items-center gap-2">
                                <button type="submit" class="btn-primary btn-sm !bg-amber-700 hover:!bg-amber-800">Mark Insufficient &amp; Send Email</button>
                                <button type="button" @click="openInsuff = false" class="btn-outline btn-sm">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="card p-8 text-center text-[#64748b]">No checks assigned to this case.</div>
        @endforelse

        {{-- Add another check to this case --}}
        @if(isset($allChecks))
        @php
            $existingSlugs = $case->checks->pluck('check_type')->toArray();
            $availableChecks = $allChecks->reject(fn ($c) => in_array($c->slug, $existingSlugs));
        @endphp
        @if($availableChecks->isNotEmpty())
        <div class="card p-4 bg-[#f8fafc]/60">
            <form method="POST" action="{{ route('admin.cases.checks.store', $case) }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                @csrf
                <div class="flex-1">
                    <select name="check_type" class="form-select" required>
                        <option value="">+ Add another verification check to this case…</option>
                        @foreach($availableChecks as $ac)
                            <option value="{{ $ac->slug }}">{{ $ac->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn-outline shrink-0">Add Check</button>
            </form>
        </div>
        @endif
        @endif
    </div>

    {{-- Right panel: case info --}}
    <div class="space-y-4">

        {{-- Candidate info (with inline Edit toggle) --}}
        <div class="card" x-data="{ editingCandidate: false }">
            <div class="card-header">
                <h3 class="font-semibold text-[#0f2044]">Candidate Profile</h3>
                <button type="button" @click="editingCandidate = !editingCandidate" class="text-xs font-semibold text-[#0d9488] hover:underline">
                    <span x-show="!editingCandidate">Edit</span>
                    <span x-show="editingCandidate" style="display:none">Cancel</span>
                </button>
            </div>
            <div class="card-body">
                <div x-show="!editingCandidate" class="space-y-3">
                    @foreach([
                        ['Full Name', $case->candidate_name],
                        ["Father's Name", $case->candidate_father_name],
                        ['Email Address', $case->candidate_email],
                        ['Phone Number', $case->candidate_phone],
                        ['Designation', $case->candidate_designation],
                    ] as [$label, $value])
                    @if($value)
                    <div>
                        <div class="text-xs text-[#94a3b8] uppercase tracking-wide">{{ $label }}</div>
                        <div class="text-sm font-medium text-[#1e293b] mt-0.5 break-all">{{ $value }}</div>
                    </div>
                    @endif
                    @endforeach
                </div>

                <form x-show="editingCandidate" style="display:none" method="POST" action="{{ route('admin.cases.candidate.update', $case) }}" class="space-y-3">
                    @csrf @method('PATCH')
                    <div>
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="candidate_name" value="{{ $case->candidate_name }}" required class="form-input !py-2">
                    </div>
                    <div>
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="candidate_email" value="{{ $case->candidate_email }}" required class="form-input !py-2">
                    </div>
                    <div>
                        <label class="form-label">Father's Name</label>
                        <input type="text" name="candidate_father_name" value="{{ $case->candidate_father_name }}" class="form-input !py-2">
                    </div>
                    <div>
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="candidate_phone" value="{{ $case->candidate_phone }}" class="form-input !py-2">
                    </div>
                    <div>
                        <label class="form-label">Designation</label>
                        <input type="text" name="candidate_designation" value="{{ $case->candidate_designation }}" class="form-input !py-2">
                    </div>
                    <button type="submit" class="btn-teal btn-sm w-full justify-center">Save Candidate Details</button>
                </form>
            </div>
        </div>

        {{-- Case info --}}
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-[#0f2044]">Case Metadata &amp; Link</h3></div>
            <div class="card-body space-y-3.5">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <div class="text-xs text-[#94a3b8] uppercase tracking-wide">Organization</div>
                        <div class="text-sm font-semibold text-[#0f2044] mt-0.5">{{ $case->clientOrganization->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-[#94a3b8] uppercase tracking-wide">Initiated By</div>
                        <div class="text-sm font-medium text-[#1e293b] mt-0.5">{{ $case->createdBy->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-[#94a3b8] uppercase tracking-wide">Case Start Date</div>
                        <div class="text-sm font-medium text-[#1e293b] mt-0.5">{{ optional($case->case_start_date)->format('d M Y') ?? $case->created_at->format('d M Y') }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-[#94a3b8] uppercase tracking-wide">Report Closed</div>
                        <div class="text-sm font-medium text-[#1e293b] mt-0.5">{{ optional($case->report_closed_date)->format('d M Y') ?? 'Open' }}</div>
                    </div>
                </div>

                @if($case->uploadToken)
                @php
                    $candidateUploadUrl = request()->getSchemeAndHttpHost() . '/upload/' . $case->uploadToken->token . '?ctx=' . \App\Models\UploadToken::encodeSnapshot($case);
                @endphp
                <div class="pt-2 border-t border-[#e2e8f0]" x-data="{ copied: false, url: '{{ $candidateUploadUrl }}' }">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs text-[#94a3b8] uppercase tracking-wide">Candidate Upload Link</span>
                        @if(!$case->uploadToken->isValid())
                            <span class="badge-failed !py-0.5 !px-2 !text-[10px]">Expired / Used</span>
                        @else
                            <span class="badge-clear !py-0.5 !px-2 !text-[10px]">Active</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-1.5 bg-[#f8fafc] border border-[#e2e8f0] rounded-lg p-2">
                        <div class="text-xs font-mono text-[#0d9488] truncate flex-1">{{ $candidateUploadUrl }}</div>
                        <a href="{{ $candidateUploadUrl }}" target="_blank"
                           class="btn-teal btn-sm !px-2.5 !py-1 shrink-0 text-xs inline-flex items-center gap-1">
                            <span>Open</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                        <button type="button" @click="navigator.clipboard.writeText(url); copied = true; setTimeout(() => copied = false, 2000)"
                                class="btn-outline btn-sm !px-2.5 !py-1 shrink-0">
                            <span x-show="!copied">Copy</span>
                            <span x-show="copied" class="text-emerald-600" style="display:none">Copied!</span>
                        </button>
                    </div>
                </div>
                @endif

                <form method="POST" action="{{ route('admin.cases.resend-link', $case) }}">
                    @csrf
                    <button type="submit" class="btn-outline btn-sm w-full justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        {{ $case->uploadToken ? 'Resend Candidate Upload Link' : 'Generate & Send Upload Link' }}
                    </button>
                </form>
            </div>
        </div>

        {{-- Overall Case Status & Executive Notes --}}
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-[#0f2044]">Case Status &amp; Executive Notes</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.cases.status', $case) }}" class="space-y-4">
                    @csrf @method('PATCH')
                    <div>
                        <label class="form-label">Override Overall Case Status</label>
                        <select name="status" class="form-select">
                            @foreach(['Pending', 'In Progress', 'Insufficiency', 'Amber', 'Discrepancy Found', 'On Hold', 'Completed - Green (Clear)', 'Completed - Discrepancy Found'] as $s)
                                <option value="{{ $s }}" @selected($case->status === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                        <p class="form-hint">Marking as Completed automatically sets the Report Closed Date for TAT calculation.</p>
                    </div>
                    <div>
                        <label class="form-label">Executive Summary / Notes</label>
                        <textarea name="admin_notes" rows="4" class="form-textarea w-full" placeholder="Overall case remarks or executive summary…">{{ $case->admin_notes }}</textarea>
                    </div>
                    <button type="submit" class="btn-primary btn-sm w-full justify-center">Update Status &amp; Notes</button>
                </form>
            </div>
        </div>
    </div>
</div>

</x-layouts.app>
