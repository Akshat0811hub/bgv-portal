<x-layouts.app title="MIS Report">

<div class="page-header">
    <div>
        <h2 class="page-title">MIS Report</h2>
        <p class="page-subtitle">Management Information System — case tracking and TAT overview</p>
    </div>
    <a href="{{ request()->fullUrlWithQuery(['export' => 1]) }}" class="btn-teal">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        Export CSV
    </a>
</div>

{{-- Filter bar --}}
<div class="card mb-5">
    <div class="card-body py-4">
        <form method="GET" action="{{ route('admin.reports.mis') }}"
              class="flex flex-col sm:flex-row gap-3 items-end">
            <div class="flex-1">
                <label class="form-label">Filter by Client Organization</label>
                <select name="org_id" class="form-select">
                    <option value="">All Organizations</option>
                    @foreach($orgs as $org)
                        <option value="{{ $org->id }}" {{ $orgId == $org->id ? 'selected' : '' }}>
                            {{ $org->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 shrink-0">
                <button type="submit" class="btn-primary">Apply Filter</button>
                @if($orgId)
                    <a href="{{ route('admin.reports.mis') }}" class="btn-outline">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Summary stats --}}
@php
    $total      = $summaryStats['total'] ?? $cases->total();
    $completed  = $summaryStats['completed'] ?? 0;
    $inProgress = $summaryStats['active'] ?? 0;
    $avgTat     = $summaryStats['avg_tat'] ?? null;
@endphp

<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
    <div class="card p-4">
        <p class="text-xs text-[#64748b] font-medium uppercase tracking-wider mb-1">Total Cases</p>
        <p class="text-2xl font-bold text-[#0f2044]">{{ $total }}</p>
    </div>
    <div class="card p-4">
        <p class="text-xs text-[#64748b] font-medium uppercase tracking-wider mb-1">Completed</p>
        <p class="text-2xl font-bold text-emerald-600">{{ $completed }}</p>
    </div>
    <div class="card p-4">
        <p class="text-xs text-[#64748b] font-medium uppercase tracking-wider mb-1">Active / Open</p>
        <p class="text-2xl font-bold text-blue-600">{{ $inProgress }}</p>
    </div>
    <div class="card p-4">
        <p class="text-xs text-[#64748b] font-medium uppercase tracking-wider mb-1">Avg TAT (Days)</p>
        <p class="text-2xl font-bold text-[#0d9488]">{{ $avgTat !== null ? $avgTat : '—' }}</p>
    </div>
</div>

{{-- Per-page selector --}}
<div class="flex items-center justify-between mb-3">
    <p class="text-sm text-[#64748b]">
        Showing {{ $cases->firstItem() ?? 0 }}–{{ $cases->lastItem() ?? 0 }} of {{ $cases->total() }} cases
    </p>
    <div class="flex items-center gap-2">
        <span class="text-sm text-[#64748b]">Rows per page:</span>
        @foreach([10, 25, 50] as $pp)
        <a href="{{ request()->fullUrlWithQuery(['per_page' => $pp, 'page' => 1]) }}"
           class="px-2.5 py-1 rounded text-sm font-medium transition-colors
                  {{ ($perPage ?? 15) === $pp ? 'bg-[#0f2044] text-white' : 'bg-white border border-[#e2e8f0] text-[#374151] hover:bg-[#f8fafc]' }}">
            {{ $pp }}
        </a>
        @endforeach
    </div>
</div>

{{-- Table --}}
<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>Client Name</th>
                <th>Candidate Name</th>
                <th>Case No.</th>
                <th>Case Create Date</th>
                <th>Case Start Date <span class="text-[#94a3b8] font-normal normal-case text-[10px]">(editable)</span></th>
                <th>Report Closed Date</th>
                <th>Status</th>
                <th class="text-right">TAT (Days)</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($cases as $case)
            @php
                $startVal     = $case->case_start_date?->format('Y-m-d') ?? '';
                $startDisplay = $case->case_start_date?->format('d M Y') ?? '—';
                $closedDisplay= $case->report_closed_date?->format('d M Y') ?? '—';
                $tat = ($case->case_start_date && $case->report_closed_date)
                    ? $case->case_start_date->diffInDays($case->report_closed_date)
                    : null;
            @endphp
            <tr>
                <td class="font-medium text-[#0f2044]">{{ $case->clientOrganization?->name ?? '—' }}</td>
                <td>{{ $case->candidate_name }}</td>
                <td class="text-xs font-mono text-[#64748b]">{{ $case->case_number }}</td>
                <td class="text-[#64748b] text-xs whitespace-nowrap">{{ $case->created_at->format('d M Y') }}</td>

                {{-- Inline editable case start date --}}
                <td x-data="{
                        caseId: {{ $case->id }},
                        editing: false,
                        saving: false,
                        val: '{{ $startVal }}',
                        display: '{{ $startDisplay }}',
                        async save() {
                            if (!this.val) return;
                            this.saving = true;
                            try {
                                const r = await fetch(`/admin/reports/cases/${this.caseId}/start-date`, {
                                    method: 'PATCH',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                        'Accept': 'application/json',
                                    },
                                    body: JSON.stringify({ case_start_date: this.val }),
                                });
                                if (!r.ok) throw new Error();
                                const d = new Date(this.val + 'T12:00:00');
                                this.display = d.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });
                                this.editing = false;
                            } catch {
                                alert('Failed to save date. Please try again.');
                            } finally {
                                this.saving = false;
                            }
                        }
                    }">
                    <div x-show="!editing" class="flex items-center gap-1.5 group cursor-default">
                        <span class="text-xs whitespace-nowrap" x-text="display"></span>
                        <button @click="editing = true"
                                class="opacity-0 group-hover:opacity-100 transition-opacity p-0.5 rounded hover:bg-[#f1f5f9] text-[#94a3b8] hover:text-[#0d9488]"
                                title="Edit case start date">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                        </button>
                    </div>
                    <div x-show="editing" style="display:none" class="flex items-center gap-1.5">
                        <input type="date" x-model="val"
                               class="form-input !py-1 !text-xs w-36"
                               @keydown.escape="editing = false"
                               @keydown.enter.prevent="save()">
                        <button @click="save()" :disabled="saving"
                                class="btn-teal btn-sm !px-2 !py-1">
                            <span x-show="!saving">✓</span>
                            <svg x-show="saving" style="display:none" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </button>
                        <button @click="editing = false" class="btn-outline btn-sm !px-2 !py-1">✕</button>
                    </div>
                </td>

                <td class="text-[#64748b] text-xs whitespace-nowrap">{{ $closedDisplay }}</td>
                <td><x-status-badge :status="$case->status" /></td>
                <td class="text-right font-semibold text-sm">
                    @if($tat !== null)
                        <span class="@if($tat > 10) text-red-600 @elseif($tat > 5) text-amber-600 @else text-emerald-600 @endif">
                            {{ $tat }}
                        </span>
                    @else
                        <span class="text-[#94a3b8] font-normal">—</span>
                    @endif
                </td>
                <td class="text-right">
                    <a href="{{ route('admin.cases.show', $case) }}" class="btn-outline btn-sm">Review</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center py-12 text-[#64748b]">
                    <svg class="w-10 h-10 mx-auto mb-3 text-[#cbd5e1]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    No cases found{{ $orgId ? ' for the selected organization' : '' }}.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($cases->hasPages())
<div class="mt-4">
    {{ $cases->links() }}
</div>
@endif

</x-layouts.app>
