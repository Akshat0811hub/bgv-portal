<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Documents — Orbit Verify</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f8fafc] font-sans">

{{-- Header --}}
<header class="bg-white border-b border-[#e2e8f0] px-4 py-4">
    <div class="max-w-3xl mx-auto flex items-center gap-3">
        <div class="w-9 h-9 bg-[#0f2044] rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        </div>
        <div>
            <div class="font-bold text-[#0f2044] text-base">Orbit Verify</div>
            <div class="text-xs text-[#64748b]">Secure Document Upload</div>
        </div>
    </div>
</header>

<main class="max-w-3xl mx-auto px-4 py-8">

    @if(session('success'))
    <div class="alert-success mb-6">
        <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Intro card --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-[#0d9488]/10 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-[#0d9488]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-[#0f2044]">Hello, {{ $uploadToken->case->candidate_name }}!</h1>
                    <p class="text-[#64748b] text-sm mt-1">
                        A background verification has been initiated for you. Please upload the required documents for each check listed below.
                        Your documents are securely encrypted and only accessible to authorized personnel.
                    </p>
                    @if($uploadToken->expires_at)
                    <div class="inline-flex items-center gap-1.5 mt-3 text-xs font-medium text-amber-700 bg-amber-50 px-3 py-1.5 rounded-full">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Link expires {{ $uploadToken->expires_at->format('d M Y, h:i A') }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Upload form --}}
    <form method="POST" action="{{ route('candidate.upload.store', $uploadToken->token) }}"
          enctype="multipart/form-data" class="space-y-4" x-data="{ uploading: false }"
          @submit="uploading = true">
        @csrf

        @if($errors->any())
        <div class="alert-error">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <div>Please review the highlighted fields. {{ $errors->first() }}</div>
        </div>
        @endif

        @foreach($uploadToken->case->checks as $check)
        @php
            $checkName = ucwords(str_replace('_', ' ', $check->check_type));
            $schema    = $schemas[$check->check_type] ?? [];
        @endphp

        <div class="card" x-data="{ open: true }">
            <button type="button" @click="open = !open" class="w-full card-header text-left">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-[#f1f5f9] flex items-center justify-center">
                        <svg class="w-4 h-4 text-[#64748b]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    </div>
                    <span class="font-semibold text-[#1e293b]">{{ $checkName }}</span>
                </div>
                <svg class="w-4 h-4 text-[#94a3b8] transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <div x-show="open" x-collapse>
                <div class="px-6 pb-6">
                    @if(!empty($schema))
                        <x-check-fields :slug="$check->check_type" :schema="$schema" :data="$check->form_data ?? []" />
                    @else
                        {{-- Fallback: schema unknown, allow generic file upload --}}
                        <label class="form-label">Upload Supporting Documents</label>
                        <input type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.webp"
                               name="check_files[{{ $check->check_type }}][_file][]" class="form-input">
                    @endif
                </div>
            </div>
        </div>
        @endforeach

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="btn-teal btn-lg" :disabled="uploading">
                <span x-show="!uploading">Upload All Documents</span>
                <span x-show="uploading" style="display:none">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    Uploading…
                </span>
            </button>
        </div>

        <div class="alert-info">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <span>Your documents are encrypted and stored securely. They will only be accessed by authorized verification personnel.</span>
        </div>
    </form>
</main>

</body>
</html>
