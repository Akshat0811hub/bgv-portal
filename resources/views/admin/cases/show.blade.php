<x-layouts.app :title="'Case — ' . $case->case_number">

{{-- Header --}}
<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.cases.index') }}" class="text-[#64748b] hover:text-[#0f2044] transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <div class="flex items-center gap-3">
                <h1 class="page-title">{{ $case->case_number }}</h1>
                <x-status-badge :status="$case->status" />
            </div>
            <p class="page-subtitle">{{ $case->candidate_name }} · {{ $case->clientOrganization->name ?? '—' }}</p>
        </div>
    </div>
    <a href="{{ route('admin.cases.report', $case) }}" class="btn-outline">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Download Report
    </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

    {{-- Checks column --}}
    <div class="xl:col-span-2 space-y-4">

        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-[#0f2044]">Verification Checks</h2>
                <span class="text-sm text-[#64748b]">{{ $case->checks->count() }} checks</span>
            </div>
        </div>

        @forelse($case->checks as $check)
        <div class="card" x-data="{ open: false }">
            <button @click="open = !open" class="w-full card-header text-left">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-[#f1f5f9] flex items-center justify-center">
                        <svg class="w-4 h-4 text-[#64748b]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <div class="font-semibold text-[#1e293b]">{{ ucwords(str_replace('_', ' ', $check->check_type)) }}</div>
                        @if($check->completed_at)
                        <div class="text-xs text-[#94a3b8]">Completed {{ $check->completed_at->format('d M Y') }}</div>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <x-status-badge :status="$check->status" />
                    <svg class="w-4 h-4 text-[#94a3b8] transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>

            @php $schema = $schemas[$check->check_type] ?? []; @endphp
            <div x-show="open" x-collapse>
                <div class="px-6 pb-6 space-y-5">

                    {{-- Candidate-submitted data (schema-aware) --}}
                    <div>
                        <p class="section-label">Report Stated Details</p>
                        <x-check-summary :schema="$schema" :data="$check->form_data ?? []" />
                    </div>

                    {{-- Documents --}}
                    @if($check->documents->isNotEmpty())
                    <div>
                        <p class="section-label">Uploaded Documents ({{ $check->documents->count() }})</p>
                        <div class="space-y-2">
                            @foreach($check->documents as $doc)
                            <div class="flex items-center justify-between bg-[#f8fafc] rounded-lg px-4 py-2.5">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    @if($doc->uploaded_by_role === 'admin')
                                    <span class="badge-completed shrink-0">Verified</span>
                                    @endif
                                    <svg class="w-5 h-5 text-[#64748b] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                    <span class="text-sm text-[#1e293b] truncate">{{ $doc->original_name }}</span>
                                    <span class="text-xs text-[#94a3b8] shrink-0">{{ $doc->formatted_size }}</span>
                                </div>
                                <a href="{{ route('documents.view', $doc) }}" target="_blank" class="btn-outline btn-sm shrink-0">View</a>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Update form: verified details + status --}}
                    <div>
                        <p class="section-label">Verified Details</p>
                        <form method="POST" action="{{ route('admin.cases.checks.update', [$case, $check]) }}" enctype="multipart/form-data">
                            @csrf @method('PATCH')

                            {{-- Verified values mirroring the schema (admin's confirmed findings) --}}
                            @if(!empty($schema))
                            <div class="space-y-4 mb-4">
                                @foreach($schema as $section => $fields)
                                    @php $isSelf = ($section === '_self'); @endphp
                                    <div @class(['rounded-lg border border-[#e2e8f0] p-4' => !$isSelf])>
                                        @unless($isSelf)
                                            <h4 class="text-sm font-semibold text-[#0f2044] mb-3">{{ $section }}</h4>
                                        @endunless
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            @foreach($fields as $field)
                                                @php $isFile = ($field['type'] ?? '') === 'file' || str_starts_with($field['name'], '_file'); @endphp
                                                @continue($isFile)
                                                <div>
                                                    <label class="form-label">{{ $field['label'] ?? $field['name'] }}</label>
                                                    <input type="text"
                                                           name="verified_data[{{ $section }}][{{ $field['name'] }}]"
                                                           value="{{ data_get($check->verified_data, "$section.{$field['name']}") }}"
                                                           placeholder="Verified value"
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
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select" required>
                                        @foreach(['Pending', 'In Progress', 'Insufficiency', 'Amber', 'Discrepancy Found', 'Clear', 'Failed', 'Completed'] as $s)
                                        <option value="{{ $s }}" @selected($check->status === $s)>{{ $s }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Attach Verified Document(s)</label>
                                    <input type="file" name="verified_files[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp" class="form-input">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Comments / Notes</label>
                                <textarea name="comments" rows="3" class="form-textarea" placeholder="Add verification notes…">{{ $check->comments }}</textarea>
                            </div>
                            <button type="submit" class="btn-teal mt-3">Save Check</button>
                        </form>
                    </div>

                    {{-- Raise insufficiency: notify candidate to re-upload --}}
                    <div x-data="{ open: false }" class="border-t border-[#e2e8f0] pt-4">
                        <button type="button" @click="open = !open" class="text-sm font-medium text-amber-700 hover:text-amber-800">
                            Raise insufficiency &amp; request re-upload
                        </button>
                        <form method="POST" action="{{ route('admin.cases.checks.insufficiency', [$case, $check]) }}"
                              x-show="open" style="display:none" class="mt-3">
                            @csrf
                            <label class="form-label">Reason (sent to candidate)</label>
                            <textarea name="reason" rows="2" class="form-textarea" placeholder="e.g. Uploaded document is unclear, please re-upload a legible copy."></textarea>
                            <button type="submit" class="btn-outline btn-sm mt-2">Mark Insufficient &amp; Notify</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="card p-8 text-center text-[#64748b]">No checks assigned to this case.</div>
        @endforelse
    </div>

    {{-- Right panel: case info --}}
    <div class="space-y-4">

        {{-- Candidate info --}}
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-[#0f2044]">Candidate Info</h3></div>
            <div class="card-body space-y-3">
                @foreach([
                    ['Name', $case->candidate_name],
                    ["Father's Name", $case->candidate_father_name],
                    ['Email', $case->candidate_email],
                    ['Phone', $case->candidate_phone],
                    ['Designation', $case->candidate_designation],
                ] as [$label, $value])
                @if($value)
                <div>
                    <div class="text-xs text-[#94a3b8] uppercase tracking-wide">{{ $label }}</div>
                    <div class="text-sm font-medium text-[#1e293b] mt-0.5">{{ $value }}</div>
                </div>
                @endif
                @endforeach
            </div>
        </div>

        {{-- Case info --}}
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-[#0f2044]">Case Info</h3></div>
            <div class="card-body space-y-3">
                <div>
                    <div class="text-xs text-[#94a3b8] uppercase tracking-wide">Organization</div>
                    <div class="text-sm font-medium text-[#1e293b] mt-0.5">{{ $case->clientOrganization->name ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-[#94a3b8] uppercase tracking-wide">Created By</div>
                    <div class="text-sm font-medium text-[#1e293b] mt-0.5">{{ $case->createdBy->name ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-[#94a3b8] uppercase tracking-wide">Started</div>
                    <div class="text-sm font-medium text-[#1e293b] mt-0.5">{{ $case->created_at->format('d M Y') }}</div>
                </div>
                @if($case->uploadToken)
                <div>
                    <div class="text-xs text-[#94a3b8] uppercase tracking-wide">Candidate Upload Link</div>
                    <div class="text-xs font-mono text-[#0d9488] mt-0.5 break-all">
                        {{ route('candidate.upload.show', $case->uploadToken->token) }}
                    </div>
                    @if(!$case->uploadToken->isValid())
                    <span class="badge-failed mt-1">Expired / Used</span>
                    @endif
                </div>
                @endif
                <form method="POST" action="{{ route('admin.cases.resend-link', $case) }}">
                    @csrf
                    <button type="submit" class="btn-outline btn-sm w-full justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        {{ $case->uploadToken ? 'Resend Upload Link' : 'Send Upload Link' }}
                    </button>
                </form>
            </div>
        </div>

        {{-- Admin notes --}}
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-[#0f2044]">Admin Notes</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.cases.status', $case) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="{{ $case->status }}">
                    <textarea name="admin_notes" rows="4" class="form-textarea w-full" placeholder="Internal notes…">{{ $case->admin_notes }}</textarea>
                    <button type="submit" class="btn-outline btn-sm mt-2">Save Notes</button>
                </form>
            </div>
        </div>
    </div>
</div>

</x-layouts.app>
