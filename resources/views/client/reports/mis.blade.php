<x-layouts.app title="MIS Report">

<div class="page-header">
    <div>
        <h2 class="page-title">MIS Report</h2>
        <p class="page-subtitle">Case tracking, status overview, and TAT summary</p>
    </div>
    <a href="{{ request()->fullUrlWithQuery(['export' => 1, 'page' => null]) }}" class="btn-teal">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        Export CSV
    </a>
</div>

{{-- Summary stats --}}
@php
    $all       = $cases->getCollection();
    $completed = $all->filter(fn($c) => str_starts_with($c->status, 'Completed'))->count();
    $active    = $all->filter(fn($c) => in_array($c->status, ['Pending', 'In Progress']))->count();
    $withTat   = $all->filter(fn($c) => $c->case_start_date && $c->report_closed_date);
    $avgTat    = $withTat->count()
        ? round($withTat->avg(fn($c) => $c->case_start_date->diffInDays($c->report_closed_date)), 1)
        : null;
@endphp

<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
    <div class="card p-4">
        <p class="text-xs text-[#64748b] font-medium uppercase tracking-wider mb-1">Total Cases</p>
        <p class="text-2xl font-bold text-[#0f2044]">{{ $cases->total() }}</p>
    </div>
    <div class="card p-4">
        <p class="text-xs text-[#64748b] font-medium uppercase tracking-wider mb-1">Completed</p>
        <p class="text-2xl font-bold text-emerald-600">{{ $completed }}</p>
    </div>
    <div class="card p-4">
        <p class="text-xs text-[#64748b] font-medium uppercase tracking-wider mb-1">Active</p>
        <p class="text-2xl font-bold text-blue-600">{{ $active }}</p>
    </div>
    <div class="card p-4">
        <p class="text-xs text-[#64748b] font-medium uppercase tracking-wider mb-1">Avg TAT (Days)</p>
        <p class="text-2xl font-bold text-[#0d9488]">{{ $avgTat ?? '—' }}</p>
    </div>
</div>

{{-- Per-page selector --}}
<div class="flex items-center justify-between mb-3">
    <p class="text-sm text-[#64748b]">
        Showing {{ $cases->firstItem() ?? 0 }}–{{ $cases->lastItem() ?? 0 }} of {{ $cases->total() }} cases
    </p>
    <div class="flex items-center gap-2">
        <span class="text-sm text-[#64748b]">Rows per page:</span>
        @foreach([5, 10, 25] as $pp)
        <a href="{{ request()->fullUrlWithQuery(['per_page' => $pp, 'page' => 1]) }}"
           class="px-2.5 py-1 rounded text-sm font-medium transition-colors
                  {{ $perPage === $pp ? 'bg-[#0f2044] text-white' : 'bg-white border border-[#e2e8f0] text-[#374151] hover:bg-[#f8fafc]' }}">
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
                <th>Candidate Name</th>
                <th>Case No.</th>
                <th>Case Create Date</th>
                <th>Case Start Date</th>
                <th>Report Closed Date</th>
                <th>Status</th>
                <th class="text-right">TAT (Days)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cases as $case)
            @php
                $tat = ($case->case_start_date && $case->report_closed_date)
                    ? $case->case_start_date->diffInDays($case->report_closed_date)
                    : null;
            @endphp
            <tr>
                <td class="font-medium text-[#0f2044]">{{ $case->candidate_name }}</td>
                <td class="text-xs font-mono text-[#64748b]">{{ $case->case_number }}</td>
                <td class="text-[#64748b] text-xs whitespace-nowrap">{{ $case->created_at->format('d M Y') }}</td>
                <td class="text-[#64748b] text-xs whitespace-nowrap">{{ $case->case_start_date?->format('d M Y') ?? '—' }}</td>
                <td class="text-[#64748b] text-xs whitespace-nowrap">{{ $case->report_closed_date?->format('d M Y') ?? '—' }}</td>
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
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center py-12 text-[#64748b]">
                    <svg class="w-10 h-10 mx-auto mb-3 text-[#cbd5e1]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    No cases found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
@if($cases->hasPages())
<div class="mt-4 flex items-center justify-between">
    <div class="text-sm text-[#64748b]">
        Page {{ $cases->currentPage() }} of {{ $cases->lastPage() }}
    </div>
    <div class="flex items-center gap-1">
        @if($cases->onFirstPage())
            <span class="px-3 py-1.5 rounded-lg text-sm border border-[#e2e8f0] text-[#94a3b8] bg-[#f8fafc] cursor-not-allowed">← Prev</span>
        @else
            <a href="{{ $cases->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg text-sm border border-[#e2e8f0] text-[#374151] bg-white hover:bg-[#f8fafc]">← Prev</a>
        @endif

        @foreach($cases->getUrlRange(max(1, $cases->currentPage()-2), min($cases->lastPage(), $cases->currentPage()+2)) as $page => $url)
            <a href="{{ $url }}"
               class="px-3 py-1.5 rounded-lg text-sm border {{ $page === $cases->currentPage() ? 'border-[#0f2044] bg-[#0f2044] text-white' : 'border-[#e2e8f0] bg-white text-[#374151] hover:bg-[#f8fafc]' }}">
                {{ $page }}
            </a>
        @endforeach

        @if($cases->hasMorePages())
            <a href="{{ $cases->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg text-sm border border-[#e2e8f0] text-[#374151] bg-white hover:bg-[#f8fafc]">Next →</a>
        @else
            <span class="px-3 py-1.5 rounded-lg text-sm border border-[#e2e8f0] text-[#94a3b8] bg-[#f8fafc] cursor-not-allowed">Next →</span>
        @endif
    </div>
</div>
@endif

</x-layouts.app>
