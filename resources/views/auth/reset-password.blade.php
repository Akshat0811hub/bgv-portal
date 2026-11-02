<x-layouts.guest title="Reset Password">
    <div>
        <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-sm text-[#64748b] hover:text-[#0f2044] mb-6 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to login
        </a>

        <h2 class="text-2xl font-bold text-[#0f2044] mb-1">Set a new password</h2>
        <p class="text-[#64748b] text-sm mb-8">Choose a strong password with at least 8 characters, including letters and numbers.</p>

        @if($errors->any())
        <div class="alert-error mb-5">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-5" x-data="{ show: false }">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label class="form-label" for="email">Email address</label>
                <input id="email" name="email" type="email" required
                       value="{{ old('email', request('email')) }}"
                       class="form-input @error('email') border-red-400 @enderror"
                       placeholder="you@company.com">
                @error('email') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label" for="password">New password</label>
                <div class="relative">
                    <input id="password" name="password" :type="show ? 'text' : 'password'" required
                           class="form-input pr-10 @error('password') border-red-400 @enderror"
                           placeholder="••••••••">
                    <button type="button" @click="show = !show"
                            class="absolute inset-y-0 right-0 px-3 flex items-center text-[#94a3b8] hover:text-[#64748b]">
                        <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="show" style="display:none" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                </div>
                @error('password') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label" for="password_confirmation">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" :type="show ? 'text' : 'password'" required
                       class="form-input" placeholder="••••••••">
            </div>

            <button type="submit" class="btn-primary w-full justify-center py-2.5">Reset Password</button>
        </form>
    </div>
</x-layouts.guest>
