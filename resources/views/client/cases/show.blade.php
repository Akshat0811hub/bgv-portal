<x-layouts.app :title="'Case — ' . $case->case_number">

<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('client.cases.index') }}" class="text-[#64748b] hover:text-[#0f2044]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <div class="flex items-center gap-3">
                <h1 class="page-title">{{ $case->case_number }}</h1>
                <x-status-badge :status="$case->status" />
            </div>
            <p class="page-subtitle">{{ $case->candidate_name }} · {{ $case->candidate_designation }}</p>
        </div>
    </div>
    <a href="{{ route('client.cases.report', $case) }}" class="btn-outline">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Download Report
    </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

    {{-- Checks --}}
    <div class="xl:col-span-2 space-y-4">
        <div class="card">
            <div class="card-header">
                <h2 class="font-semibold text-[#0f2044]">Verification Progress</h2>
                @php
                    $total = $case->checks->count();
                    $done  = $case->checks->whereIn('status', ['Clear','Completed','Failed','Discrepancy Found','Amber'])->count();
                @endphp
                <span class="text-sm text-[#64748b]">{{ $done }}/{{ $total }} completed</span>
            </div>
            {{-- Progress bar --}}
            <div class="px-6 pb-4">
                <div class="w-full bg-[#f1f5f9] rounded-full h-2 mt-1">
                    <div class="bg-[#0d9488] h-2 rounded-full transition-all duration-500"
                         style="width: {{ $total > 0 ? round($done / $total * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>

        @foreach($case->checks as $check)
        <div class="card">
            <div class="card-header">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg
                        @if($check->status === 'Clear' || $check->status === 'Completed') bg-emerald-100 text-emerald-600
                        @elseif($check->status === 'Discrepancy Found' || $check->status === 'Failed') bg-red-100 text-red-600
                        @elseif($check->status === 'Amber') bg-orange-100 text-orange-600
                        @elseif($check->status === 'In Progress') bg-blue-100 text-blue-600
                        @else bg-[#f1f5f9] text-[#64748b] @endif
                        flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <span class="font-semibold text-[#1e293b]">{{ ucwords(str_replace('_', ' ', $check->check_type)) }}</span>
                </div>
                <x-status-badge :status="$check->status" />
            </div>
            @php $schema = $schemas[$check->check_type] ?? []; @endphp
            @if($check->form_data)
            <div class="px-6 pb-4">
                <p class="text-xs text-[#94a3b8] uppercase tracking-wide mb-2">Submitted Details</p>
                <x-check-summary :schema="$schema" :data="$check->form_data ?? []" />
            </div>
            @endif
            @if($check->comments)
            <div class="px-6 pb-4">
                <p class="text-xs text-[#94a3b8] uppercase tracking-wide mb-1">Verification Notes</p>
                <p class="text-sm text-[#374151] bg-[#f8fafc] rounded-lg p-3">{{ $check->comments }}</p>
            </div>
            @endif
            @if($check->documents->isNotEmpty())
            <div class="px-6 pb-4">
                <p class="text-xs text-[#94a3b8] uppercase tracking-wide mb-2">Documents ({{ $check->documents->count() }})</p>
                <div class="space-y-1.5">
                    @foreach($check->documents as $doc)
                    <div class="flex items-center justify-between bg-[#f8fafc] rounded-lg px-3 py-2">
                        <span class="text-sm text-[#374151] truncate">{{ $doc->original_name }}</span>
                        <div class="flex items-center gap-2 ml-2 shrink-0">
                            <span class="text-xs text-[#94a3b8]">{{ $doc->formatted_size }}</span>
                            <a href="{{ route('documents.view', $doc) }}" target="_blank" class="btn-outline btn-sm">View</a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Right panel --}}
    <div class="space-y-4">
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-[#0f2044]">Candidate Details</h3></div>
            <div class="card-body space-y-3">
                @foreach([['Name', $case->candidate_name], ['Email', $case->candidate_email], ['Phone', $case->candidate_phone], ['Designation', $case->candidate_designation]] as [$label, $value])
                @if($value)
                <div>
                    <div class="text-xs text-[#94a3b8] uppercase tracking-wide">{{ $label }}</div>
                    <div class="text-sm font-medium text-[#1e293b] mt-0.5">{{ $value }}</div>
                </div>
                @endif
                @endforeach
            </div>
        </div>

        @if($case->uploadToken)
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-[#0f2044]">Upload Link</h3></div>
            <div class="card-body">
                <p class="text-xs text-[#64748b] mb-2">Share this link with the candidate to upload documents:</p>
                <div class="bg-[#f8fafc] rounded-lg p-3 text-xs font-mono text-[#0d9488] break-all">
                    {{ route('candidate.upload.show', $case->uploadToken->token) }}
                </div>
                @if(!$case->uploadToken->isValid())
                <span class="badge-failed mt-2">Link Expired</span>
                @else
                <div class="text-xs text-[#64748b] mt-2">Expires {{ $case->uploadToken->expires_at?->format('d M Y') }}</div>
                @endif
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-[#0f2044]">Case Timeline</h3></div>
            <div class="card-body space-y-2">
                <div>
                    <div class="text-xs text-[#94a3b8] uppercase tracking-wide">Initiated</div>
                    <div class="text-sm font-medium text-[#1e293b]">{{ $case->created_at->format('d M Y, h:i A') }}</div>
                </div>
                @if($case->report_closed_date)
                <div>
                    <div class="text-xs text-[#94a3b8] uppercase tracking-wide">Closed</div>
                    <div class="text-sm font-medium text-[#1e293b]">{{ $case->report_closed_date->format('d M Y') }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

</x-layouts.app>
