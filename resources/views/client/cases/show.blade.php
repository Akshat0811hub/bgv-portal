<x-layouts.app :title="'Case — ' . $case->case_number">

<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('client.cases.index') }}" class="p-2 rounded-lg border border-[#e2e8f0] bg-white text-[#64748b] hover:text-[#0f2044] hover:bg-[#f8fafc] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="page-title font-mono">{{ $case->case_number }}</h1>
                <x-status-badge :status="$case->status" />
            </div>
            <p class="page-subtitle">{{ $case->candidate_name }}{{ $case->candidate_designation ? ' · ' . $case->candidate_designation : '' }}</p>
        </div>
    </div>
    <a href="{{ route('client.cases.report', $case) }}" class="btn-teal">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Download PDF Report
    </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

    {{-- Checks --}}
    <div class="xl:col-span-2 space-y-4">
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="font-semibold text-[#0f2044]">Verification Progress</h2>
                    @php
                        $total = $case->checks->count();
                        $done  = $case->checks->whereIn('status', ['Clear','Completed','Failed','Discrepancy Found','Amber'])->count();
                        $pct   = $total > 0 ? round($done / $total * 100) : 0;
                    @endphp
                    <p class="text-xs text-[#64748b] mt-0.5">{{ $done }} of {{ $total }} checks completed ({{ $pct }}%)</p>
                </div>
                <span class="badge bg-[#0f2044]/10 text-[#0f2044]">{{ $done }}/{{ $total }} Verified</span>
            </div>
            {{-- Progress bar --}}
            <div class="px-6 pb-4 pt-3 bg-[#f8fafc]/50 rounded-b-xl">
                <div class="w-full bg-[#e2e8f0] rounded-full h-2 overflow-hidden">
                    <div class="bg-[#0d9488] h-2 rounded-full transition-all duration-500"
                         style="width: {{ $pct }}%"></div>
                </div>
            </div>
        </div>

        @foreach($case->checks as $check)
        <div class="card overflow-hidden">
            <div class="card-header bg-[#f8fafc]/60">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl
                        @if($check->status === 'Clear' || $check->status === 'Completed') bg-emerald-100 text-emerald-600
                        @elseif($check->status === 'Discrepancy Found' || $check->status === 'Failed') bg-red-100 text-red-600
                        @elseif($check->status === 'Amber') bg-orange-100 text-orange-600
                        @elseif($check->status === 'Insufficiency') bg-purple-100 text-purple-600
                        @elseif($check->status === 'In Progress') bg-blue-100 text-blue-600
                        @else bg-[#f1f5f9] text-[#64748b] @endif
                        flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <span class="font-semibold text-[#0f2044]">{{ ucwords(str_replace('_', ' ', $check->check_type)) }}</span>
                        @if($check->completed_at)
                            <div class="text-xs text-[#64748b]">Verified on {{ $check->completed_at->format('d M Y') }}</div>
                        @endif
                    </div>
                </div>
                <x-status-badge :status="$check->status" />
            </div>

            <div class="p-6 space-y-5">
                @php
                    $schema = $schemas[$check->check_type] ?? [];
                    $hasVerified = !empty($check->verified_data);
                @endphp

                @if($check->form_data || $hasVerified)
                <div class="grid grid-cols-1 {{ $hasVerified ? 'lg:grid-cols-2' : '' }} gap-4">
                    <div>
                        <p class="text-xs font-semibold text-[#64748b] uppercase tracking-wider mb-2">Candidate Stated Details</p>
                        <x-check-summary :schema="$schema" :data="$check->form_data ?? []" />
                    </div>
                    @if($hasVerified)
                    <div>
                        <p class="text-xs font-semibold text-[#0d9488] uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Agency Verified Findings
                        </p>
                        <div class="ring-1 ring-[#0d9488]/25 rounded-lg overflow-hidden">
                            <x-check-summary :schema="$schema" :data="$check->verified_data ?? []" />
                        </div>
                    </div>
                    @endif
                </div>
                @else
                <p class="text-sm text-[#94a3b8] italic">No details submitted for this check yet.</p>
                @endif

                @if($check->comments)
                <div>
                    <p class="text-xs font-semibold text-[#64748b] uppercase tracking-wider mb-1.5">Verifier Remarks / Notes</p>
                    <div class="text-sm text-[#334155] bg-amber-50/70 border border-amber-200/70 rounded-xl p-3.5">{{ $check->comments }}</div>
                </div>
                @endif

                @if($check->documents->isNotEmpty())
                <div>
                    <p class="text-xs font-semibold text-[#64748b] uppercase tracking-wider mb-2">Attached Documents ({{ $check->documents->count() }})</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        @foreach($check->documents as $doc)
                        <div class="flex items-center justify-between bg-[#f8fafc] border border-[#e2e8f0] rounded-xl px-3.5 py-2.5">
                            <div class="min-w-0 pr-2">
                                <div class="text-sm font-medium text-[#1e293b] truncate" title="{{ $doc->original_name }}">{{ $doc->original_name }}</div>
                                <div class="text-xs text-[#64748b]">
                                    {{ $doc->formatted_size }}
                                    @if($doc->uploaded_by_role === 'admin')
                                        <span class="text-teal-700 font-semibold">· Verified Proof</span>
                                    @elseif($doc->uploaded_by_role === 'client')
                                        <span>· Uploaded by HR</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <a href="{{ route('documents.view', $doc) }}" target="_blank" class="btn-outline btn-sm">View</a>
                                @if($doc->uploaded_by_role === 'client' && !$case->isCompletedStatus())
                                <form method="POST" action="{{ route('documents.destroy', $doc) }}" onsubmit="return confirm('Remove this document?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 transition-colors" title="Remove document">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Allow HR to upload additional documents for Pending / Insufficiency / In Progress checks --}}
                @if(!$case->isCompletedStatus())
                <div x-data="{ openUpload: {{ $check->status === 'Insufficiency' ? 'true' : 'false' }} }" class="border-t border-[#e2e8f0] pt-3">
                    <button type="button" @click="openUpload = !openUpload" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#0d9488] hover:text-[#0f766e]">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Upload Supporting Document on Candidate's Behalf
                    </button>
                    <form x-show="openUpload" x-collapse style="display:none" method="POST" action="{{ route('client.cases.checks.documents', [$case, $check]) }}" enctype="multipart/form-data" class="mt-2.5 flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                        @csrf
                        <input type="file" name="files[]" multiple required accept=".pdf,.jpg,.jpeg,.png,.webp" class="form-input !py-1.5 !text-xs flex-1">
                        <button type="submit" class="btn-teal btn-sm shrink-0">Upload File(s)</button>
                    </form>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- Right panel --}}
    <div class="space-y-4">
        <div class="card" x-data="{ editingCandidate: false }">
            <div class="card-header">
                <h3 class="font-semibold text-[#0f2044]">Candidate Profile</h3>
                @if(!$case->isCompletedStatus())
                <button type="button" @click="editingCandidate = !editingCandidate" class="text-xs font-semibold text-[#0d9488] hover:underline">
                    <span x-show="!editingCandidate">Edit</span>
                    <span x-show="editingCandidate" style="display:none">Cancel</span>
                </button>
                @endif
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

                @if(!$case->isCompletedStatus())
                <form x-show="editingCandidate" style="display:none" method="POST" action="{{ route('client.cases.candidate.update', $case) }}" class="space-y-3">
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
                    <button type="submit" class="btn-teal btn-sm w-full justify-center">Save Details</button>
                </form>
                @endif
            </div>
        </div>

        <div class="card" x-data="{ copied: false, url: '{{ $case->uploadToken ? route('candidate.upload.show', $case->uploadToken->token) : '' }}' }">
            <div class="card-header">
                <h3 class="font-semibold text-[#0f2044]">Candidate Upload Link</h3>
                @if($case->uploadToken)
                    @if(!$case->uploadToken->isValid())
                        <span class="badge-failed !py-0.5 !px-2 !text-[10px]">Expired / Used</span>
                    @else
                        <span class="badge-clear !py-0.5 !px-2 !text-[10px]">Active</span>
                    @endif
                @endif
            </div>
            <div class="card-body space-y-3">
                @if($case->uploadToken)
                <div>
                    <p class="text-xs text-[#64748b] mb-2">Share this secure link with the candidate to upload documents:</p>
                    <div class="flex items-center gap-1.5 bg-[#f8fafc] border border-[#e2e8f0] rounded-lg p-2.5">
                        <div class="text-xs font-mono text-[#0d9488] truncate flex-1">{{ route('candidate.upload.show', $case->uploadToken->token) }}</div>
                        <button type="button" @click="navigator.clipboard.writeText(url); copied = true; setTimeout(() => copied = false, 2000)"
                                class="btn-outline btn-sm !px-2.5 !py-1 shrink-0">
                            <span x-show="!copied">Copy</span>
                            <span x-show="copied" class="text-emerald-600" style="display:none">Copied!</span>
                        </button>
                    </div>
                    @if($case->uploadToken->isValid() && $case->uploadToken->expires_at)
                    <div class="text-xs text-[#64748b] mt-1.5">Expires {{ $case->uploadToken->expires_at->format('d M Y, h:i A') }}</div>
                    @endif
                </div>
                @endif

                @if(!$case->isCompletedStatus())
                <form method="POST" action="{{ route('client.cases.resend-link', $case) }}">
                    @csrf
                    <button type="submit" class="btn-outline btn-sm w-full justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        {{ $case->uploadToken ? 'Resend Upload Link to Candidate' : 'Generate & Send Upload Link' }}
                    </button>
                </form>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-[#0f2044]">Case Timeline &amp; SLA</h3></div>
            <div class="card-body space-y-3">
                @php
                    $tat = ($case->case_start_date && $case->report_closed_date)
                        ? $case->case_start_date->diffInDays($case->report_closed_date)
                        : null;
                @endphp
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <div class="text-xs text-[#94a3b8] uppercase tracking-wide">Initiated By</div>
                        <div class="text-sm font-medium text-[#1e293b] mt-0.5">{{ $case->createdBy->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-[#94a3b8] uppercase tracking-wide">Start Date</div>
                        <div class="text-sm font-medium text-[#1e293b] mt-0.5">{{ optional($case->case_start_date)->format('d M Y') ?? $case->created_at->format('d M Y') }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-[#94a3b8] uppercase tracking-wide">Report Closed</div>
                        <div class="text-sm font-medium text-[#1e293b] mt-0.5">{{ optional($case->report_closed_date)->format('d M Y') ?? 'In Progress' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-[#94a3b8] uppercase tracking-wide">TAT (Days)</div>
                        <div class="text-sm font-bold text-[#0d9488] mt-0.5">{{ $tat !== null ? $tat . ' Days' : '—' }}</div>
                    </div>
                </div>
                @if($case->admin_notes)
                <div class="pt-3 border-t border-[#e2e8f0]">
                    <div class="text-xs text-[#94a3b8] uppercase tracking-wide mb-1">Agency Executive Summary</div>
                    <p class="text-sm text-[#334155] bg-[#f8fafc] rounded-lg p-3">{{ $case->admin_notes }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

</x-layouts.app>
