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
<body class="min-h-screen font-sans">

{{-- Header --}}
<header class="sticky top-0 z-30 bg-white/75 backdrop-blur-xl border-b border-slate-200/70 shadow-[0_1px_3px_rgba(15,32,68,0.03)] px-4 py-3">
    <div class="max-w-3xl mx-auto flex items-center justify-between">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 bg-gradient-to-br from-[#162d5c] to-[#0f2044] rounded-xl flex items-center justify-center shadow-xs">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div>
                <div class="font-bold text-[#0f2044] text-sm tracking-tight leading-tight">Orbit Verify</div>
                <div class="text-[11px] text-slate-500">Secure Candidate Portal</div>
            </div>
        </div>
        <span class="badge bg-teal-500/10 text-teal-800 ring-1 ring-inset ring-teal-500/20">
            <svg class="w-3 h-3 text-[#0d9488]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            256-Bit Encrypted
        </span>
    </div>
</header>

<main class="max-w-3xl mx-auto px-4 py-7">

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
    <form method="POST" action="/upload/{{ $uploadToken->token }}"
          enctype="multipart/form-data" class="space-y-4" x-data="{
              uploading: false,
              clientError: '',
              validateBeforeSubmit(e) {
                  this.clientError = '';
                  const form = e.target;
                  let hasFile = false;
                  form.querySelectorAll('input[type=file]').forEach(inp => {
                      if (inp.files && inp.files.length > 0) hasFile = true;
                  });
                  let hasTextChanged = false;
                  form.querySelectorAll('input[type=text], input[type=date], input[type=radio]:checked').forEach(inp => {
                      const def = inp.defaultValue ?? '';
                      if (inp.type === 'radio') {
                          if (!inp.defaultChecked && inp.checked) hasTextChanged = true;
                      } else if (inp.value.trim() !== '' && inp.value.trim() !== def.trim()) {
                          hasTextChanged = true;
                      } else if (inp.value.trim() !== '' && def.trim() === '') {
                          hasTextChanged = true;
                      }
                  });
                  let missingFileCheck = '';
                  form.querySelectorAll('[data-check-card]').forEach(card => {
                      if (card.getAttribute('data-requires-file') === '1' && !missingFileCheck) {
                          let cardHasFile = false;
                          card.querySelectorAll('input[type=file]').forEach(inp => {
                              if (inp.files && inp.files.length > 0) cardHasFile = true;
                          });
                          if (!cardHasFile) {
                              missingFileCheck = card.getAttribute('data-check-card');
                          }
                      }
                  });
                  if (missingFileCheck) {
                      e.preventDefault();
                      this.clientError = 'Please attach the required supporting document(s) for ' + missingFileCheck + ' before submitting.';
                      window.scrollTo({ top: 0, behavior: 'smooth' });
                      return false;
                  }
                  if (!hasFile && !hasTextChanged) {
                      e.preventDefault();
                      this.clientError = 'Please attach at least one document or fill in your details above before clicking Submit.';
                      window.scrollTo({ top: 0, behavior: 'smooth' });
                      return false;
                  }
                  this.uploading = true;
              }
          }"
          @submit="validateBeforeSubmit($event)">
        @csrf
        <input type="hidden" name="_case_snapshot" value="{{ \App\Models\UploadToken::encodeSnapshot($uploadToken->case) }}">

        <div x-show="clientError" style="display:none" class="alert-error">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <div x-text="clientError"></div>
        </div>

        @if($errors->any())
        <div class="alert-error">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <div>{{ $errors->first() }}</div>
        </div>
        @endif

        @foreach($uploadToken->case->checks as $check)
        @php
            $checkName = ucwords(str_replace('_', ' ', $check->check_type));
            $schema    = $check->effectiveSchema($schemas[$check->check_type] ?? []);
            $isInsuff  = ($check->status === 'Insufficiency');
            $requiresFile = $isInsuff;
        @endphp

        <div class="card {{ $isInsuff ? 'ring-2 ring-amber-400 border-amber-300' : '' }}" x-data="{ open: true }" data-check-card="{{ $checkName }}" data-requires-file="{{ $requiresFile ? '1' : '0' }}">
            <button type="button" @click="open = !open" class="w-full card-header text-left hover:bg-[#f8fafc] transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl {{ $isInsuff ? 'bg-amber-100 text-amber-700' : 'bg-[#f1f5f9] text-[#0f2044]' }} flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    </div>
                    <div>
                        <span class="font-semibold text-[#0f2044]">{{ $checkName }}</span>
                        @if($isInsuff)
                            <span class="ml-2 badge-insufficiency">Action Required</span>
                        @endif
                    </div>
                </div>
                <svg class="w-4 h-4 text-[#94a3b8] transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <div x-show="open">
                <div class="p-6">
                    @if($isInsuff && $check->comments)
                    <div class="alert-warning mb-4">
                        <svg class="w-5 h-5 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <div>
                            <div class="font-semibold text-xs uppercase tracking-wider mb-0.5">Additional Information Requested</div>
                            <div>{{ $check->comments }}</div>
                        </div>
                    </div>
                    @endif

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

        <div class="flex items-center justify-between gap-4 pt-2">
            <button type="submit" class="btn-teal !px-5 !py-2 !text-xs" :disabled="uploading">
                <svg x-show="!uploading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span x-show="!uploading">Submit Verification Documents</span>
                <span x-show="uploading" style="display:none" class="inline-flex items-center gap-1.5">
                    <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
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
