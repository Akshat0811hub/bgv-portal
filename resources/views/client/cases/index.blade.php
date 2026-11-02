<x-layouts.app title="My Cases">

<div class="page-header">
    <div>
        <h1 class="page-title">My Cases</h1>
        <p class="page-subtitle">Track all your background verification cases</p>
    </div>
    <a href="{{ route('client.cases.create') }}" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Case
    </a>
</div>

<div class="card mb-5">
    <div class="card-body py-4">
        <form method="GET" class="flex flex-col sm:flex-row gap-3">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search by name or case number…" class="form-input flex-1">
            <select name="status" class="form-select sm:w-52">
                <option value="">All Statuses</option>
                @foreach(['Pending', 'In Progress', 'Insufficiency', 'Amber', 'On Hold', 'Completed - Green (Clear)', 'Completed - Discrepancy Found'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-primary">Filter</button>
            @if(request()->hasAny(['search', 'status']))
            <a href="{{ route('client.cases.index') }}" class="btn-outline">Clear</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="table-wrap border-0">
        <table class="data-table">
            <thead>
                <tr><th>Case No.</th><th>Candidate</th><th>Checks</th><th>Status</th><th>Created</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($cases as $case)
                <tr>
                    <td><span class="font-mono text-xs font-semibold text-[#0f2044]">{{ $case->case_number }}</span></td>
                    <td>
                        <div class="font-medium">{{ $case->candidate_name }}</div>
                        <div class="text-xs text-[#64748b]">{{ $case->candidate_email }}</div>
                    </td>
                    <td><span class="text-sm text-[#64748b]">{{ $case->checks->count() }}</span></td>
                    <td><x-status-badge :status="$case->status" /></td>
                    <td class="text-sm text-[#64748b] whitespace-nowrap">{{ $case->created_at->format('d M Y') }}</td>
                    <td><a href="{{ route('client.cases.show', $case) }}" class="btn-outline btn-sm">View</a></td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-16 text-[#64748b]">
                        <svg class="w-12 h-12 mx-auto text-[#cbd5e1] mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        No cases yet. <a href="{{ route('client.cases.create') }}" class="text-[#0d9488] underline">Create your first case</a>.
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
