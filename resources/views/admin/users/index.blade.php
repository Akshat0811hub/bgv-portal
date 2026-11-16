<x-layouts.app title="Client Users">

<div class="page-header">
    <div>
        <h1 class="page-title">Client Users</h1>
        <p class="page-subtitle">Manage client accounts and organizations</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Client User
    </a>
</div>

<div class="card">
    <div class="table-wrap border-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Organization</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-[#0f2044] flex items-center justify-center text-white text-xs font-bold shrink-0">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <span class="font-medium">{{ $user->name }}</span>
                        </div>
                    </td>
                    <td class="text-[#64748b] text-sm">{{ $user->email }}</td>
                    <td class="text-sm">{{ $user->clientOrganization->name ?? '—' }}</td>
                    <td><span class="badge bg-slate-100 text-slate-700">{{ ucfirst($user->role) }}</span></td>
                    <td>
                        @if($user->is_verified)
                        <span class="badge-clear">Active</span>
                        @else
                        <span class="badge-failed">Deactivated</span>
                        @endif
                    </td>
                    <td class="text-sm text-[#64748b] whitespace-nowrap">{{ $user->created_at->format('d M Y') }}</td>
                    <td class="text-right">
                        <div class="flex items-center justify-end gap-2">
                            <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn-outline btn-sm">
                                    {{ $user->is_verified ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                  onsubmit="return confirm('Delete this user account? Any cases they created will be preserved under their organization.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-16 text-[#64748b]">No client users yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="px-6 py-4 border-t border-[#e2e8f0]">{{ $users->links() }}</div>
    @endif
</div>

</x-layouts.app>
