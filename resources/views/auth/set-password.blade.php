<x-layouts.guest title="Set Your Password">
    <div>
        <div class="w-12 h-12 bg-[#0d9488]/10 rounded-xl flex items-center justify-center mb-5">
            <svg class="w-6 h-6 text-[#0d9488]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
        </div>
        <h2 class="text-2xl font-bold text-[#0f2044] mb-1">Set your password</h2>
        <p class="text-[#64748b] text-sm mb-8">Welcome! Please set a new password to continue to your portal.</p>

        @if(session('warning'))
        <div class="alert-warning mb-5">{{ session('warning') }}</div>
        @endif

        <form method="POST" action="{{ route('password.set.post') }}" class="space-y-5">
            @csrf

            <div>
                <label class="form-label" for="password">New Password</label>
                <input id="password" name="password" type="password" required minlength="8"
                       class="form-input @error('password') border-red-400 @enderror"
                       placeholder="Minimum 8 characters">
                @error('password') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label" for="password_confirmation">Confirm Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       class="form-input" placeholder="Repeat your password">
            </div>

            <button type="submit" class="btn-teal w-full justify-center py-2.5">
                Set Password & Continue
            </button>
        </form>
    </div>
</x-layouts.guest>
