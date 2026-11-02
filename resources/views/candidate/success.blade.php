<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents Submitted — Orbit Verify</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f8fafc] font-sans flex items-center justify-center p-4">
    <div class="card p-10 max-w-md w-full text-center">
        <div class="w-16 h-16 bg-emerald-100 rounded-2xl flex items-center justify-center mx-auto mb-5">
            <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h1 class="text-xl font-bold text-[#0f2044] mb-2">Documents Submitted</h1>
        <p class="text-[#64748b] text-sm">
            Thank you{{ isset($case) && $case->candidate_name ? ', '.$case->candidate_name : '' }}! Your documents and details have been submitted successfully and securely.
            The verification team will review them — you don't need to do anything further.
        </p>
        <div class="mt-6 pt-5 border-t border-[#e2e8f0]">
            <div class="flex items-center justify-center gap-2">
                <div class="w-7 h-7 bg-[#0f2044] rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <span class="font-semibold text-[#0f2044]">Orbit Verify</span>
            </div>
        </div>
    </div>
</body>
</html>
