<x-layouts.guest title="Forgot Password">
    <div>
        <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-sm text-[#64748b] hover:text-[#0f2044] mb-6 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to login
        </a>

        <h2 class="text-2xl font-bold text-[#0f2044] mb-1">Forgot your password?</h2>
        <p class="text-[#64748b] text-sm mb-8">Enter your email and we'll send you a reset link.</p>

        @if(session('status'))
        <div class="alert-success mb-5">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf
            <div>
                <label class="form-label" for="email">Email address</label>
                <input id="email" name="email" type="email" required
                       value="{{ old('email') }}"
                       class="form-input @error('email') border-red-400 @enderror"
                       placeholder="you@company.com">
                @error('email') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn-primary w-full justify-center py-2.5">Send Reset Link</button>
        </form>
    </div>
</x-layouts.guest>
