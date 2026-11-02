<x-layouts.guest title="Admin Login">
    <div>
        <div class="inline-flex items-center gap-2 bg-[#0f2044]/8 text-[#0f2044] text-xs font-semibold px-3 py-1.5 rounded-full mb-5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            Admin Access Only
        </div>
        <h2 class="text-2xl font-bold text-[#0f2044] mb-1">Admin Portal</h2>
        <p class="text-[#64748b] text-sm mb-8">Sign in with your administrator credentials</p>

        <form method="POST" action="{{ route('admin.login.post') }}" class="space-y-5">
            @csrf

            <div>
                <label class="form-label" for="email">Email address</label>
                <input id="email" name="email" type="email" autocomplete="email"
                       value="{{ old('email') }}" required
                       class="form-input @error('email') border-red-400 @enderror"
                       placeholder="admin@orbitverify.com">
                @error('email')
                <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="form-label" for="password">Password</label>
                <div class="relative" x-data="{ show: false }">
                    <input id="password" name="password" :type="show ? 'text' : 'password'" required
                           class="form-input pr-10 @error('password') border-red-400 @enderror"
                           placeholder="••••••••">
                    <button type="button" @click="show = !show"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-[#94a3b8] hover:text-[#64748b]">
                        <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-primary w-full justify-center py-2.5 bg-[#0f2044] hover:bg-[#1e3a5f]">
                Sign in as Admin
            </button>
        </form>

        <div class="mt-6 pt-5 border-t border-[#e2e8f0] text-center">
            <a href="{{ route('login') }}" class="text-sm text-[#64748b] hover:text-[#0f2044] transition-colors">
                ← Back to client login
            </a>
        </div>
    </div>
</x-layouts.guest>
