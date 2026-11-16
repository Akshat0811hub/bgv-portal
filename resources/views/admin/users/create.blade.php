<x-layouts.app title="Add Client User">

<div class="max-w-2xl">
    <div class="page-header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.users.index') }}" class="text-[#64748b] hover:text-[#0f2044] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="page-title">Add Client User</h1>
                <p class="page-subtitle">Create a new client account and send credentials via email</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="form-label">Full Name</label>
                        <input name="name" type="text" class="form-input @error('name') border-red-400 @enderror"
                               value="{{ old('name') }}" placeholder="John Doe" required>
                        @error('name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Email Address</label>
                        <input name="email" type="email" class="form-input @error('email') border-red-400 @enderror"
                               value="{{ old('email') }}" placeholder="john@company.com" required>
                        @error('email') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="form-label">Temporary Password</label>
                        <div class="relative">
                            <input id="temp-password" name="password" type="text"
                                   class="form-input pr-11 @error('password') border-red-400 @enderror"
                                   value="{{ old('password') }}" placeholder="Min. 8 characters" required minlength="8">
                            <button type="button" id="generate-password-btn"
                                    title="Generate a strong password"
                                    class="absolute inset-y-0 right-0 px-3 flex items-center text-[#64748b] hover:text-[#0d9488] transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                            </button>
                        </div>
                        <p class="form-hint">Click the key icon to auto-generate. User will be required to change this on first login.</p>
                        @error('password') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Account Type</label>
                        <select name="role" class="form-select" required>
                            <option value="organization" @selected(old('role') === 'organization')>Organization</option>
                            <option value="individual" @selected(old('role') === 'individual')>Individual</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="form-label">Company / Organization Name</label>
                    <input name="client_org_name" type="text" list="existing-orgs"
                           class="form-input @error('client_org_name') border-red-400 @enderror"
                           value="{{ old('client_org_name') }}" placeholder="Select an existing company or type a new name…" required>
                    @if(isset($organizations) && $organizations->isNotEmpty())
                    <datalist id="existing-orgs">
                        @foreach($organizations as $org)
                            <option value="{{ $org->name }}"></option>
                        @endforeach
                    </datalist>
                    @endif
                    <p class="form-hint">Pick an existing organization from the dropdown list to share cases across HR users, or type a new name to create a new organization.</p>
                    @error('client_org_name') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="divider"></div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="btn-primary">
                        Create Account & Send Credentials
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function orbitGeneratePassword() {
        var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        var pw = '';
        for (var i = 0; i < 12; i++) {
            pw += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        var input = document.getElementById('temp-password');
        if (input) {
            input.value = pw;
            // Notify any listeners (e.g. Alpine/validation) that the value changed.
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.getElementById('generate-password-btn');
        if (btn) {
            btn.addEventListener('click', orbitGeneratePassword);
        }
    });
</script>

</x-layouts.app>
