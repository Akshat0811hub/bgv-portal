<x-layouts.guest title="Client Login">
    <div>
        <h2 class="text-2xl font-bold text-[#0f2044] mb-1">Welcome back</h2>
        <p class="text-[#64748b] text-sm mb-8">Sign in to your client portal</p>

        @if(session('status'))
        <div class="alert-success mb-5">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
            @csrf

            <div>
                <label class="form-label" for="email">Email address</label>
                <input id="email" name="email" type="email" autocomplete="email"
                       value="{{ old('email') }}" required
                       class="form-input @error('email') border-red-400 @enderror"
                       placeholder="you@company.com">
                @error('email')
                <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="form-label mb-0" for="password">Password</label>
                    <a href="{{ route('password.request') }}" class="text-xs text-[#0d9488] hover:underline font-medium">Forgot password?</a>
                </div>
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

            <div class="flex items-center gap-2">
                <input id="remember" name="remember" type="checkbox"
                       class="w-4 h-4 text-[#0d9488] border-[#d1d5db] rounded focus:ring-[#0d9488]">
                <label for="remember" class="text-sm text-[#64748b]">Keep me signed in</label>
            </div>

            <button type="submit" class="btn-primary w-full justify-center py-2.5">
                Sign in to Portal
            </button>
        </form>

        <div class="mt-6 pt-5 border-t border-[#e2e8f0] text-center">
            <a href="{{ route('admin.login') }}" class="text-sm text-[#64748b] hover:text-[#0f2044] transition-colors">
                Admin? <span class="font-medium text-[#0f2044]">Sign in here →</span>
            </a>
        </div>
    </div>
</x-layouts.guest>
