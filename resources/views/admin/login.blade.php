<!DOCTYPE html>
<html lang="en" class="h-full bg-[#090d16]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Executive Login — FanVault</title>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|jetbrains-mono:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-[#090d16] text-[#f8fafc] font-sans antialiased selection:bg-[#10b981] selection:text-[#090d16] flex items-center justify-center p-4">
    <div class="w-full max-w-md mx-auto">
        <!-- Auth Card -->
        <div class="relative rounded-2xl bg-[#0e1626] border border-[#1e293b] p-8 shadow-[0_20px_50px_rgba(0,0,0,0.5)] overflow-hidden">
            <!-- Subtle Emerald Glow Accents -->
            <div class="absolute -top-24 -left-24 w-48 h-48 bg-[#10b981]/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -right-24 w-48 h-48 bg-[#059669]/10 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Monogram & Header -->
            <div class="text-center mb-8 relative">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-[#10b981] text-[#090d16] flex items-center justify-center font-mono font-extrabold text-xl shadow-[0_0_25px_rgba(16,185,129,0.35)] mb-4">
                    FV
                </div>
                <h1 class="text-xl font-serif font-bold text-white tracking-tight">
                    FanVault Executive Console
                </h1>
                <p class="text-xs text-[#64748b] mt-1.5 font-mono">
                    Multi-Million Dollar Capsule Operations · Protocol v2.4
                </p>
            </div>

            <!-- Alert Messages -->
            @if(session('warning'))
                <div class="mb-5 p-3 rounded-xl bg-[#f59e0b]/10 border border-[#f59e0b]/20 text-[#fbbf24] text-xs">
                    {{ session('warning') }}
                </div>
            @endif

            @if(session('info'))
                <div class="mb-5 p-3 rounded-xl bg-[#3b82f6]/10 border border-[#3b82f6]/20 text-[#60a5fa] text-xs">
                    {{ session('info') }}
                </div>
            @endif

            @if($errors->has('passcode'))
                <div class="mb-5 p-3 rounded-xl bg-[#ef4444]/10 border border-[#ef4444]/20 text-[#f87171] text-xs flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0 text-[#ef4444]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ $errors->first('passcode') }}</span>
                </div>
            @endif

            <!-- Form -->
            <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-5" x-data="{ show: false }">
                @csrf
                
                <div>
                    <label for="passcode" class="block text-xs font-mono font-medium text-[#94a3b8] uppercase tracking-wider mb-2">
                        Master Executive Passcode
                    </label>
                    <div class="relative">
                        <input 
                            :type="show ? 'text' : 'password'" 
                            id="passcode" 
                            name="passcode" 
                            required 
                            autofocus
                            placeholder="Enter executive key..."
                            class="w-full px-4 py-3 rounded-xl bg-[#090d16] border border-[#1e293b] text-white font-mono text-sm placeholder-[#475569] focus:outline-none focus:border-[#10b981] focus:ring-1 focus:ring-[#10b981] transition-all pr-10"
                        />
                        <button 
                            type="button" 
                            @click="show = !show" 
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-[#64748b] hover:text-white transition-colors text-xs font-mono"
                        >
                            <span x-text="show ? 'HIDE' : 'SHOW'"></span>
                        </button>
                    </div>
                </div>

                <button 
                    type="submit" 
                    class="w-full py-3 px-4 rounded-xl bg-[#10b981] hover:bg-[#059669] text-[#090d16] font-mono font-bold text-sm tracking-tight shadow-[0_0_20px_rgba(16,185,129,0.25)] hover:shadow-[0_0_25px_rgba(16,185,129,0.4)] transition-all flex items-center justify-center gap-2 group cursor-pointer"
                >
                    <span>Unlock Executive Console</span>
                    <span class="group-hover:translate-x-0.5 transition-transform">➔</span>
                </button>
            </form>

            <!-- Bottom Badge -->
            <div class="mt-8 pt-6 border-t border-[#1e293b]/70 text-center">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#111827] text-[#64748b] text-[10px] font-mono border border-[#1e293b]">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#10b981]"></span>
                    Rate-Limited & Encrypted Session
                </span>
            </div>
        </div>

        <div class="text-center mt-6">
            <a href="{{ route('home') }}" class="text-xs font-mono text-[#64748b] hover:text-[#94a3b8] transition-colors">
                ← Return to FanVault Platform
            </a>
        </div>
    </div>
</body>
</html>
