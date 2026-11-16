<x-layouts.app title="Dashboard">

{{-- Stats row — clickable status filters --}}
@php
$totalAll = array_sum($stats);
$statCards = [
    ['label' => 'Pending',       'key' => 'Pending',                         'value' => $stats['pending'],       'dot' => 'bg-amber-500',   'bg' => 'bg-amber-50 text-amber-700'],
    ['label' => 'In Progress',   'key' => 'In Progress',                     'value' => $stats['in_progress'] ?? 0, 'dot' => 'bg-blue-500 animate-pulse', 'bg' => 'bg-blue-50 text-blue-700'],
    ['label' => 'Insufficiency', 'key' => 'Insufficiency',                   'value' => $stats['insufficiency'], 'dot' => 'bg-purple-500',  'bg' => 'bg-purple-50 text-purple-700'],
    ['label' => 'Amber',         'key' => 'Amber',                           'value' => $stats['amber'],         'dot' => 'bg-orange-500',  'bg' => 'bg-orange-50 text-orange-700'],
    ['label' => 'Discrepancy',   'key' => 'Completed - Discrepancy Found',   'value' => $stats['discrepancy'],   'dot' => 'bg-red-500',     'bg' => 'bg-red-50 text-red-700'],
    ['label' => 'Green (Clear)', 'key' => 'Completed - Green (Clear)',       'value' => $stats['green'],         'dot' => 'bg-emerald-500', 'bg' => 'bg-emerald-50 text-emerald-700'],
    ['label' => 'On Hold',       'key' => 'On Hold',                         'value' => $stats['on_hold'],       'dot' => 'bg-slate-400',   'bg' => 'bg-slate-100 text-slate-700'],
];
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Verification Operations Center</h1>
        <p class="page-subtitle">Real-time overview of all candidate background verification cases</p>
    </div>
    <div class="flex items-center gap-2.5">
        <a href="{{ route('admin.reports.mis') }}" class="btn-outline">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            MIS Report
        </a>
        <a href="{{ route('admin.cases.create') }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create Case
        </a>
    </div>
</div>

<div class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-7 gap-3.5 mb-6">
    @foreach($statCards as $card)
    @php $isActive = request('status') === $card['key']; @endphp
    <a href="{{ $isActive ? route('admin.dashboard', request()->except('status')) : route('admin.dashboard', array_merge(request()->query(), ['status' => $card['key']])) }}"
       class="card p-4 transition-all duration-200 hover:-translate-y-0.5 group {{ $isActive ? 'ring-2 ring-[#0d9488] bg-teal-50/40' : '' }}">
        <div class="flex items-center justify-between mb-1.5">
            <span class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider group-hover:text-[#0f2044] transition-colors">{{ $card['label'] }}</span>
            <span class="w-2 h-2 rounded-full {{ $card['dot'] }}"></span>
        </div>
        <div class="text-2xl font-bold text-[#0f2044] tracking-tight">{{ $card['value'] }}</div>
    </a>
    @endforeach
</div>

{{-- Cases with search + filters --}}
<div class="card">
    <div class="card-header flex-col sm:flex-row items-stretch sm:items-center gap-3">
        <h2 class="font-semibold text-[#0f2044] shrink-0">Cases</h2>

        <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-col sm:flex-row gap-2 flex-1 sm:justify-end">
            <div class="relative flex-1 sm:max-w-xs">
                <svg class="w-4 h-4 text-[#94a3b8] absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search candidate / client / email / phone"
                       class="form-input pl-9">
            </div>
            <select name="org" class="form-select sm:w-48" onchange="this.form.submit()">
                <option value="">All Organizations</option>
                @foreach($organizations as $org)
                <option value="{{ $org->id }}" @selected(request('org') == $org->id)>{{ $org->name }}</option>
                @endforeach
            </select>
            @if(request('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <button type="submit" class="btn-primary">Search</button>
            @if(request()->hasAny(['search', 'org', 'status']))
            <a href="{{ route('admin.dashboard') }}" class="btn-outline">Clear</a>
            @endif
        </form>
    </div>

    @if(request('status'))
    <div class="px-6 pt-3">
        <span class="badge bg-[#0d9488]/10 text-[#0d9488]">Filtered by status: {{ request('status') }}</span>
    </div>
    @endif

    <div class="table-wrap rounded-t-none border-t-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Case No.</th>
                    <th>Candidate</th>
                    <th>Organization</th>
                    <th>Status</th>
                    <th>Checks</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($cases as $case)
                <tr>
                    <td><span class="font-mono text-xs font-semibold text-[#0f2044]">{{ $case->case_number }}</span></td>
                    <td>
                        <div class="font-medium text-[#1e293b]">{{ $case->candidate_name }}</div>
                        <div class="text-xs text-[#64748b]">{{ $case->candidate_email }}</div>
                    </td>
                    <td class="text-[#64748b] text-sm">{{ $case->clientOrganization->name ?? '—' }}</td>
                    <td><x-status-badge :status="$case->status" /></td>
                    <td><span class="text-sm text-[#64748b]">{{ $case->checks_count }} checks</span></td>
                    <td class="text-[#64748b] text-sm whitespace-nowrap">{{ $case->created_at->format('d M Y') }}</td>
                    <td class="text-right">
                        <div class="flex items-center justify-end gap-2">
                            @if($case->status === 'Pending')
                            <form method="POST" action="{{ route('admin.cases.resend-link', $case) }}">
                                @csrf
                                <button type="submit" class="btn-outline btn-sm">Resend Link</button>
                            </form>
                            @endif
                            <a href="{{ route('admin.cases.show', $case) }}" class="btn-primary btn-sm">View / Manage</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-12 text-[#64748b]">
                        <svg class="w-12 h-12 mx-auto text-[#cbd5e1] mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        {{ request()->hasAny(['search', 'org', 'status']) ? 'No cases match your filters' : 'No cases yet' }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($cases->hasPages())
    <div class="px-6 py-4 border-t border-[#e2e8f0]">{{ $cases->links() }}</div>
    @endif
</div>

</x-layouts.app>
