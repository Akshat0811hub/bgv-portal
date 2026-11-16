<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Orbit Verify' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-[#f8fafc] font-sans">

<div class="min-h-screen flex">
    {{-- Left panel: branding --}}
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-[#0f2044] via-[#1e3a5f] to-[#0d3a5f] flex-col justify-between p-12 relative overflow-hidden">
        {{-- Background decorative circles --}}
        <div class="absolute top-0 right-0 w-96 h-96 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-[#0d9488]/20 rounded-full translate-y-1/2 -translate-x-1/2"></div>
        <div class="absolute top-1/2 left-1/2 w-32 h-32 bg-[#f59e0b]/10 rounded-full -translate-x-1/2 -translate-y-1/2"></div>

        {{-- Logo --}}
        <div class="relative z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-[#0d9488] rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <span class="text-white text-xl font-bold tracking-tight">Orbit Verify</span>
            </div>
        </div>

        {{-- Center content --}}
        <div class="relative z-10">
            <h1 class="text-4xl font-bold text-white leading-tight mb-6">
                Background Verification<br>
                <span class="text-[#0d9488]">Made Effortless</span>
            </h1>
            <p class="text-slate-300 text-lg leading-relaxed mb-10">
                A unified platform for managing candidate background checks — from employment history to identity verification, all in one place.
            </p>

            {{-- Feature pills --}}
            <div class="flex flex-wrap gap-3">
                @foreach(['Identity Checks', 'Employment History', 'Education Verification', 'Court Records', 'Address Checks'] as $feature)
                <span class="px-3 py-1.5 bg-white/10 text-slate-200 text-sm rounded-full border border-white/15 backdrop-blur-sm">
                    {{ $feature }}
                </span>
                @endforeach
            </div>
        </div>

        {{-- Stats --}}
        <div class="relative z-10 grid grid-cols-3 gap-6">
            @foreach([['14+', 'Check Types'], ['100%', 'Secure'], ['Real-time', 'Tracking']] as $stat)
            <div>
                <div class="text-2xl font-bold text-white">{{ $stat[0] }}</div>
                <div class="text-slate-400 text-sm">{{ $stat[1] }}</div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Right panel: form --}}
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-10 relative">
        <div class="w-full max-w-md bg-white/85 backdrop-blur-xl rounded-2xl border border-white/90 ring-1 ring-slate-900/[0.05] shadow-[0_12px_36px_-8px_rgba(15,32,68,0.08)] p-7 sm:p-9">
            {{-- Mobile logo --}}
            <div class="flex lg:hidden items-center gap-2.5 mb-7">
                <div class="w-8 h-8 bg-gradient-to-br from-[#162d5c] to-[#0f2044] rounded-xl flex items-center justify-center shadow-xs">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <span class="text-[#0f2044] text-base font-bold tracking-tight">Orbit Verify</span>
            </div>

            {{ $slot }}
        </div>
    </div>
</div>

</body>
</html>
