<x-layouts.app title="Dashboard">

{{-- Welcome banner --}}
<div class="bg-gradient-to-r from-[#0f2044] to-[#1e3a5f] rounded-xl p-6 mb-6 text-white relative overflow-hidden">
    <div class="absolute right-0 top-0 w-48 h-48 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/4"></div>
    <div class="absolute right-12 bottom-0 w-24 h-24 bg-[#0d9488]/30 rounded-full translate-y-1/2"></div>
    <div class="relative z-10">
        <h1 class="text-xl font-bold mb-1">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ auth()->user()->name }}!</h1>
        <p class="text-slate-300 text-sm">{{ auth()->user()->clientOrganization->name ?? '' }} · Background Verification Portal</p>
    </div>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Total Cases',  $stats['total'],      'bg-slate-100 text-slate-600', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
        ['Pending',      $stats['pending'],    'bg-amber-100 text-amber-600',  'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['In Progress',  $stats['in_progress'],'bg-blue-100 text-blue-600',    'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
        ['Completed',    $stats['completed'],  'bg-emerald-100 text-emerald-600','M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
    ] as [$label, $value, $color, $icon])
    <div class="card p-4 flex items-start gap-3">
        <div class="w-10 h-10 rounded-xl {{ $color }} flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}"/></svg>
        </div>
        <div>
            <div class="text-2xl font-bold text-[#0f2044]">{{ $value }}</div>
            <div class="text-xs text-[#64748b] font-medium">{{ $label }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- Recent cases + quick action --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    <div class="lg:col-span-2 card">
        <div class="card-header">
            <h2 class="font-semibold text-[#0f2044]">Recent Cases</h2>
            <a href="{{ route('client.cases.index') }}" class="btn-outline btn-sm">View All</a>
        </div>
        <div class="table-wrap border-0 rounded-t-none">
            <table class="data-table">
                <thead>
                    <tr><th>Case No.</th><th>Candidate</th><th>Status</th><th>Created</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($cases as $case)
                    <tr>
                        <td><span class="font-mono text-xs font-semibold text-[#0f2044]">{{ $case->case_number }}</span></td>
                        <td>
                            <div class="font-medium text-sm">{{ $case->candidate_name }}</div>
                            <div class="text-xs text-[#64748b]">{{ $case->candidate_designation }}</div>
                        </td>
                        <td><x-status-badge :status="$case->status" /></td>
                        <td class="text-xs text-[#64748b]">{{ $case->created_at->format('d M Y') }}</td>
                        <td><a href="{{ route('client.cases.show', $case) }}" class="btn-outline btn-sm">View</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-10 text-[#64748b] text-sm">No cases created yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Quick action card --}}
    <div class="card p-6 flex flex-col items-center justify-center text-center gap-4 border-2 border-dashed border-[#e2e8f0] bg-[#f8fafc]">
        <div class="w-14 h-14 rounded-2xl bg-[#0f2044] flex items-center justify-center">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4"/></svg>
        </div>
        <div>
            <h3 class="font-bold text-[#0f2044] mb-1">Start a New Case</h3>
            <p class="text-sm text-[#64748b]">Initiate background verification for a new candidate</p>
        </div>
        <a href="{{ route('client.cases.create') }}" class="btn-primary">Create Case</a>
    </div>
</div>

</x-layouts.app>
