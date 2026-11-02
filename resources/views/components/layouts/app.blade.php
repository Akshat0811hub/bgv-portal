<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Orbit Verify' }} — {{ auth()->user()->isAdmin() ? 'Admin' : 'Client Portal' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="h-full bg-[#f8fafc] font-sans" x-data="{ sidebarOpen: false }">

{{-- Mobile sidebar overlay --}}
<div x-show="sidebarOpen"
     x-transition:enter="transition-opacity ease-linear duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-40 bg-black/50 lg:hidden"
     @click="sidebarOpen = false"
     style="display:none">
</div>

{{-- Sidebar --}}
<aside class="fixed top-0 left-0 h-full w-64 bg-gradient-to-b from-[#0f2044] to-[#0d3a5f] z-50 flex flex-col transform transition-transform duration-300 ease-in-out
              -translate-x-full lg:translate-x-0"
       :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full lg:translate-x-0': !sidebarOpen }">

    {{-- Logo --}}
    <div class="flex items-center gap-3 px-5 py-5 border-b border-white/10">
        <div class="w-9 h-9 bg-[#0d9488] rounded-xl flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>
        <div>
            <div class="text-white font-bold text-base leading-tight">Orbit Verify</div>
            <div class="text-slate-400 text-xs">{{ auth()->user()->isAdmin() ? 'Admin Portal' : 'Client Portal' }}</div>
        </div>
    </div>

    {{-- Nav --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        @if(auth()->user()->isAdmin())
        <p class="section-label px-3 pt-2">Main</p>
        <a href="{{ route('admin.dashboard') }}"
           class="{{ request()->routeIs('admin.dashboard') ? 'nav-item-active' : 'nav-item-inactive' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Dashboard
        </a>
        <a href="{{ route('admin.cases.index') }}"
           class="{{ request()->routeIs('admin.cases.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            All Cases
        </a>

        <p class="section-label px-3 pt-4">Management</p>
        <a href="{{ route('admin.users.index') }}"
           class="{{ request()->routeIs('admin.users.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Client Users
        </a>

        <p class="section-label px-3 pt-4">Reports</p>
        <a href="{{ route('admin.reports.mis') }}"
           class="{{ request()->routeIs('admin.reports.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            MIS Report
        </a>

        @else
        <p class="section-label px-3 pt-2">Main</p>
        <a href="{{ route('client.dashboard') }}"
           class="{{ request()->routeIs('client.dashboard') ? 'nav-item-active' : 'nav-item-inactive' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Dashboard
        </a>
        <a href="{{ route('client.cases.index') }}"
           class="{{ request()->routeIs('client.cases.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            My Cases
        </a>
        <a href="{{ route('client.cases.create') }}"
           class="{{ request()->routeIs('client.cases.create') ? 'nav-item-active' : 'nav-item-inactive' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4"/></svg>
            New Case
        </a>

        <p class="section-label px-3 pt-4">Reports</p>
        <a href="{{ route('client.reports.mis') }}"
           class="{{ request()->routeIs('client.reports.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            MIS Report
        </a>

        <p class="section-label px-3 pt-4">Account</p>
        <a href="{{ route('client.profile.edit') }}"
           class="{{ request()->routeIs('client.profile.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Company Profile
        </a>
        @endif
    </nav>

    {{-- User profile --}}
    <div class="border-t border-white/10 p-4">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-[#0d9488] flex items-center justify-center text-white font-semibold text-sm shrink-0">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-white text-sm font-medium truncate">{{ auth()->user()->name }}</div>
                <div class="text-slate-400 text-xs truncate">{{ auth()->user()->email }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-slate-400 hover:text-white transition-colors" title="Logout">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </button>
            </form>
        </div>
    </div>
</aside>

{{-- Main content area --}}
<div class="lg:pl-64 min-h-screen flex flex-col">

    {{-- Top bar --}}
    <header class="sticky top-0 z-30 bg-white border-b border-[#e2e8f0] px-4 sm:px-6 py-3.5 flex items-center gap-4">
        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-1.5 rounded-lg hover:bg-[#f8fafc] text-[#64748b]">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <div class="flex-1">
            <h1 class="text-base font-semibold text-[#0f2044]">{{ $title ?? 'Dashboard' }}</h1>
        </div>
        <div class="flex items-center gap-3 text-sm text-[#64748b]">
            <span class="hidden sm:block">{{ auth()->user()->name }}</span>
            <div class="w-8 h-8 rounded-full bg-[#0f2044] flex items-center justify-center text-white text-xs font-bold">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
        </div>
    </header>

    {{-- Flash messages --}}
    <div class="px-4 sm:px-6 pt-4">
        @if(session('success'))
        <div class="alert-success mb-4">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="alert-error mb-4">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            {{ session('error') }}
        </div>
        @endif
        @if(session('warning'))
        <div class="alert-warning mb-4">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            {{ session('warning') }}
        </div>
        @endif
    </div>

    {{-- Page content --}}
    <main class="flex-1 px-4 sm:px-6 py-4 pb-8">
        {{ $slot }}
    </main>
</div>

</body>
</html>
