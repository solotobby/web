<!DOCTYPE html>
<html lang="en" class="h-full bg-[#090d16]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'Executive Console — FanVault' }}</title>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|jetbrains-mono:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full bg-[#090d16] text-[#f8fafc] font-sans antialiased selection:bg-[#10b981] selection:text-[#090d16]">
    <div class="min-h-screen flex flex-col">
        <!-- Top Executive Command Bar -->
        <header class="sticky top-0 z-50 border-b border-[#1e293b] bg-[#0c121e]/90 backdrop-blur-xl">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Brand & Protocol Status -->
                    <div class="flex items-center gap-4">
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 group">
                            <span class="w-9 h-9 rounded-xl bg-[#10b981] text-[#090d16] flex items-center justify-center font-mono font-bold text-sm tracking-tight shadow-[0_0_15px_rgba(16,185,129,0.3)] transition-transform group-hover:scale-105">
                                FV
                            </span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-serif font-bold text-base text-white tracking-tight">FanVault</span>
                                    <span class="px-1.5 py-0.5 rounded-md bg-[#1e293b] text-[#94a3b8] font-mono text-[10px] uppercase font-semibold">
                                        Executive
                                    </span>
                                </div>
                                <div class="text-[10px] text-[#64748b] font-mono flex items-center gap-1.5">
                                    <span>Operating System v2.4</span>
                                    <span>·</span>
                                    <span class="inline-flex items-center gap-1 text-[#34d399]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#10b981] animate-pulse"></span>
                                        Live
                                    </span>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Right Controls -->
                    <div class="flex items-center gap-3">
                        <a href="{{ route('home') }}" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-[#1e293b] bg-[#111827] text-xs font-medium text-[#94a3b8] hover:text-white hover:border-[#334155] transition-all">
                            <span>Public Site</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>

                        <form method="POST" action="{{ route('admin.logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#ef4444]/10 hover:bg-[#ef4444]/20 border border-[#ef4444]/20 text-[#f87171] hover:text-[#fca5a5] text-xs font-mono font-medium transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <span>Sign Out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Global Flash Alerts -->
        @if(session('success'))
            <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 mt-4">
                <div class="p-3.5 rounded-xl bg-[#064e3b]/30 border border-[#059669]/40 text-[#34d399] text-xs flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#10b981]"></span>
                        {{ session('success') }}
                    </span>
                    <button type="button" onclick="this.parentElement.remove()" class="text-[#34d399]/70 hover:text-[#34d399]">✕</button>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 mt-4">
                <div class="p-3.5 rounded-xl bg-[#7f1d1d]/30 border border-[#dc2626]/40 text-[#fca5a5] text-xs flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#ef4444]"></span>
                        {{ session('error') }}
                    </span>
                    <button type="button" onclick="this.parentElement.remove()" class="text-[#fca5a5]/70 hover:text-[#fca5a5]">✕</button>
                </div>
            </div>
        @endif

        <!-- Main Workspace -->
        <main class="flex-1 w-full py-6">
            {{ $slot }}
        </main>

        <!-- Executive Footer -->
        <footer class="mt-auto border-t border-[#1e293b] bg-[#0c121e] py-6 px-4 sm:px-6 lg:px-8 text-xs text-[#64748b]">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-2 font-mono text-[11px]">
                    <span class="text-[#34d399] font-semibold">FanVault Core</span>
                    <span>·</span>
                    <span>Multi-Million Dollar Capsule Infrastructure</span>
                    <span>·</span>
                    <span>Encrypted Stripe Ledger</span>
                </div>
                <div class="font-mono text-[11px] text-[#475569]">
                    Confidential · Authorized Executive Access Only
                </div>
            </div>
        </footer>
    </div>

    @livewireScripts
</body>
</html>
