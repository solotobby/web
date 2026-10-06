<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ $title ?? 'FanVault — The Creator Time Capsule · Milestones, Memories & Predictions' }}</title>
    <meta name="description" content="{{ $description ?? 'The community time capsule platform for creator milestones. Leave messages, memories, and predictions today; creators unseal and reveal them live when the moment arrives.' }}">
    <meta name="keywords" content="{{ $keywords ?? 'creator time capsule, community time capsule, milestone capsule, youtube milestone stream, twitch milestone celebration, stream unsealing ceremony' }}">
    <meta name="author" content="FanVault">
    <meta name="robots" content="{{ $robots ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1' }}">
    <link rel="canonical" href="{{ $canonicalUrl ?? url()->current() }}">
    
    <!-- OpenGraph & Social Sharing -->
    <meta property="og:site_name" content="FanVault">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:title" content="{{ $ogTitle ?? ($title ?? 'FanVault — The Creator Time Capsule') }}">
    <meta property="og:description" content="{{ $ogDescription ?? ($description ?? 'The community time capsule platform for creator milestones. Sealed today, unsealed live when the moment arrives.') }}">
    <meta property="og:url" content="{{ $ogUrl ?? url()->current() }}">
    <meta property="og:image" content="{{ $ogImage ?? route('og.cover') }}">
    <meta property="og:image:alt" content="{{ $ogTitle ?? ($title ?? 'FanVault — The Creator Time Capsule') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="en_US">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@fanvault">
    <meta name="twitter:creator" content="@fanvault">
    <meta name="twitter:title" content="{{ $ogTitle ?? ($title ?? 'FanVault — The Creator Time Capsule') }}">
    <meta name="twitter:description" content="{{ $ogDescription ?? ($description ?? 'The community time capsule platform for creator milestones. Sealed today, unsealed live when the moment arrives.') }}">
    <meta name="twitter:image" content="{{ $ogImage ?? route('og.cover') }}">
    <meta name="twitter:image:alt" content="{{ $ogTitle ?? ($title ?? 'FanVault — The Creator Time Capsule') }}">
    <meta name="theme-color" content="#047857">

    <link rel="icon" href="{{ asset('assets/favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('assets/favicon.svg') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@500;600;700&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400;1,9..40,600&family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,500;1,9..144,600&family=IBM+Plex+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    
    <!-- Structured Data (JSON-LD) -->
    {!! $schemaJson ?? \App\Support\Seo::toJson(\App\Support\Seo::websiteSchema()) !!}
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-[#faf9f5] text-[#0f172a] min-h-screen flex flex-col font-sans antialiased selection:bg-[#047857]/20 pb-[env(safe-area-inset-bottom,0)]">
    <!-- Ambient botanical and gilded gold ambient gradient -->
    <div class="fixed inset-0 pointer-events-none -z-10 bg-[radial-gradient(circle_at_12%_12%,rgba(4,120,87,0.06)_0%,transparent_45%),radial-gradient(circle_at_88%_16%,rgba(202,138,4,0.06)_0%,transparent_40%),radial-gradient(circle_at_50%_90%,rgba(6,78,59,0.05)_0%,transparent_50%)]" aria-hidden="true"></div>

    <a class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 z-50 bg-[#047857] text-white px-4 py-2 rounded-full font-bold shadow-lg" href="#main">
        Skip to content
    </a>

    <!-- Sticky Elegant Navigation Header -->
    <header x-data="{ open: false }" class="sticky top-2 sm:top-4 z-50 w-[calc(100%-1.25rem)] sm:w-[calc(100%-2rem)] max-w-5xl mx-auto my-1.5 sm:my-2">
        <!-- Main Floating Navigation Bar -->
        <div class="relative px-3 sm:px-5 py-2 sm:py-2.5 bg-white/95 backdrop-blur-md border border-[#e7e5df] rounded-full shadow-[0_2px_12px_rgba(0,0,0,0.03)] flex items-center justify-between gap-2 sm:gap-4 transition-all">
            <!-- Brand Logo -->
            <a class="flex items-center gap-2.5 font-bold text-base sm:text-lg text-[#0f172a] shrink-0 group" href="{{ route('home') }}">
                <span class="inline-flex items-center justify-center w-8 sm:w-8.5 h-8 sm:h-8.5 bg-[#064e3b] text-white rounded-xl text-xs font-mono font-bold tracking-tight transition-transform group-hover:scale-105 shadow-xs" aria-hidden="true">
                    FV
                </span>
                <span class="tracking-tight font-serif text-lg font-bold flex items-center">
                    FanVault
                    <span class="font-sans font-medium text-[10px] uppercase tracking-wider text-[#047857] hidden xs:inline ml-2 bg-[#ecfdf5] px-2 py-0.5 rounded-full border border-[#a7f3d0]/70">Creator Edition</span>
                </span>
            </a>

            <!-- Desktop Links (md: screens and larger) -->
            <nav class="hidden md:flex items-center gap-1">
                <a href="{{ route('creators') }}" @class([
                    'px-3.5 py-1.5 rounded-full text-xs sm:text-sm font-medium transition-all',
                    'text-[#064e3b] bg-[#ecfdf5] font-semibold' => request()->routeIs('creators') && !request()->routeIs('creators.join') && !request()->routeIs('creators.studio'),
                    'text-[#475569] hover:text-[#0f172a] hover:bg-[#f7f6f0]' => !request()->routeIs('creators'),
                ])>
                    Time Capsules
                </a>
                <a href="{{ route('explore') }}" @class([
                    'px-3.5 py-1.5 rounded-full text-xs sm:text-sm font-medium transition-all',
                    'text-[#064e3b] bg-[#ecfdf5] font-semibold' => request()->routeIs('explore'),
                    'text-[#475569] hover:text-[#0f172a] hover:bg-[#f7f6f0]' => !request()->routeIs('explore'),
                ])>
                    Community Archive
                </a>
                <a href="{{ route('nominate') }}" @class([
                    'px-3.5 py-1.5 rounded-full text-xs sm:text-sm font-medium transition-all',
                    'text-[#064e3b] bg-[#ecfdf5] font-semibold' => request()->routeIs('nominate'),
                    'text-[#475569] hover:text-[#0f172a] hover:bg-[#f7f6f0]' => !request()->routeIs('nominate'),
                ])>
                    Nominate
                </a>
            </nav>

            <!-- Actions: Creator CTA + Mobile Menu Button -->
            <div class="flex items-center gap-1.5 sm:gap-2">
                @if(session('creator_id'))
                    <a href="{{ route('creators.studio') }}" class="inline-flex items-center gap-1.5 bg-white hover:bg-[#f7f6f0] border border-[#e7e5df] text-[#0f172a] font-semibold text-xs sm:text-sm px-3.5 sm:px-4 py-1.5 sm:py-2 rounded-full transition-all shadow-xs">
                        <span>Studio</span>
                    </a>
                @else
                    <a href="{{ route('creators.join') }}" class="inline-flex items-center gap-1.5 bg-[#064e3b] hover:bg-[#047857] text-white font-medium text-xs sm:text-sm px-4 sm:px-4.5 py-1.5 sm:py-2 rounded-full shadow-[0_2px_10px_rgba(6,78,59,0.2)] hover:shadow-[0_4px_14px_rgba(6,78,59,0.3)] transition-all shrink-0">
                        <span class="hidden sm:inline">Create Time Capsule</span>
                        <span class="sm:hidden">Create Capsule</span>
                    </a>
                @endif

                <!-- Mobile Hamburger Toggle Button -->
                <button 
                    type="button" 
                    @click="open = !open" 
                    class="md:hidden inline-flex items-center justify-center w-8.5 h-8.5 rounded-full bg-[#faf9f5] hover:bg-[#f5f4ee] border border-[#e7e5df] text-[#0f172a] transition-all focus:outline-none focus:ring-2 focus:ring-[#047857]/20"
                    :aria-expanded="open"
                    aria-label="Toggle navigation menu"
                >
                    <!-- 3 Bars Hamburger Icon -->
                    <svg x-show="!open" class="w-4 h-4 text-[#334155]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <!-- Close (X) Icon -->
                    <svg x-show="open" class="w-4 h-4 text-[#334155]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:none;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Dropdown Navigation Menu -->
        <div 
            x-show="open" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2 scale-98"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 -translate-y-2 scale-98"
            @click.outside="open = false"
            @keydown.escape.window="open = false"
            class="md:hidden mt-2 p-3 bg-white/98 backdrop-blur-xl border border-[#e7e5df] rounded-2xl shadow-xl space-y-1"
            style="display:none;"
        >
            <div class="grid grid-cols-1 gap-1">
                <a href="{{ route('creators') }}" @click="open = false" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-[#f5f4ee] transition-colors {{ request()->routeIs('creators') ? 'bg-[#ecfdf5] text-[#064e3b]' : 'text-[#0f172a]' }}">
                    <div>
                        <div class="font-semibold text-sm">Time Capsules</div>
                        <div class="text-xs text-[#64748b]">Explore active creator time capsules</div>
                    </div>
                </a>

                <a href="{{ route('explore') }}" @click="open = false" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-[#f5f4ee] transition-colors {{ request()->routeIs('explore') ? 'bg-[#ecfdf5] text-[#064e3b]' : 'text-[#0f172a]' }}">
                    <div>
                        <div class="font-semibold text-sm">Community Archive</div>
                        <div class="text-xs text-[#64748b]">Read teasers sealed for future milestones</div>
                    </div>
                </a>

                <a href="{{ route('nominate') }}" @click="open = false" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-[#f5f4ee] transition-colors {{ request()->routeIs('nominate') ? 'bg-[#ecfdf5] text-[#064e3b]' : 'text-[#0f172a]' }}">
                    <div>
                        <div class="font-semibold text-sm">Nominate a Creator</div>
                        <div class="text-xs text-[#64748b]">Suggest a creator who deserves a time capsule</div>
                    </div>
                </a>

                <a href="{{ route('creators.join') }}" @click="open = false" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-[#f5f4ee] transition-colors {{ request()->routeIs('creators.join') ? 'bg-[#ecfdf5] text-[#064e3b]' : 'text-[#0f172a]' }}">
                    <div>
                        <div class="font-semibold text-sm">Create Your Capsule</div>
                        <div class="text-xs text-[#64748b]">Launch your creator time capsule in 60s</div>
                    </div>
                </a>
            </div>

            <div class="pt-2 border-t border-[#e7e5df]">
                <a href="{{ route('creators.join') }}" @click="open = false" class="w-full flex items-center justify-center p-2.5 rounded-xl bg-[#064e3b] text-white font-semibold text-sm shadow-sm transition-all">
                    Create Time Capsule
                </a>
            </div>
        </div>
    </header>

    <main id="main" class="flex-1 w-full">
        {{ $slot }}
    </main>

    <!-- Architectural Minimalist Footer -->
    <footer class="mt-auto border-t border-[#e7e5df] bg-[#fbfaf6] text-[#0f172a] pt-14 pb-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto space-y-12">
            <!-- Masthead Protocol Status Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-8 border-b border-[#e7e5df]">
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-xl bg-[#064e3b] text-white flex items-center justify-center text-xs font-mono font-bold tracking-tight shadow-2xs">
                        FV
                    </span>
                    <div>
                        <span class="tracking-tight font-serif text-xl font-bold text-[#0f172a] block leading-none">FanVault</span>
                        <span class="text-[11px] text-[#64748b] font-medium block mt-1">The Creator Time Capsule · Milestones, Memories & Predictions</span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 text-xs">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0] font-mono text-[11px] font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#047857] animate-pulse"></span>
                        <span>Encrypted Time Capsule Protocol</span>
                    </span>
                    <span class="hidden sm:inline text-[#cbd5e1]">/</span>
                    <a href="https://getfanvault.com" class="font-mono text-xs text-[#64748b] hover:text-[#047857] transition-colors">
                        getfanvault.com
                    </a>
                </div>
            </div>

            <!-- 4-Column Directory Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10 lg:gap-8">
                <!-- Column 1: Brand & Economics (2 cols on lg) -->
                <div class="lg:col-span-2 space-y-4">
                    <p class="text-sm text-[#475569] leading-relaxed max-w-sm">
                        Where creators preserve the moments, memories, predictions and messages from their community — sealed today, opened when the moment arrives.
                    </p>
                    <div class="pt-1 flex flex-col gap-2">
                        <div class="inline-flex items-center gap-2 text-xs text-[#064e3b] font-semibold bg-[#ecfdf5] px-3 py-1.5 rounded-full border border-[#a7f3d0] w-fit shadow-2xs">
                            <svg class="w-3.5 h-3.5 text-[#047857]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                            <span>Creator-Set Pricing · Direct Stripe Payouts</span>
                        </div>
                        <p class="text-[11px] text-[#64748b] font-mono">
                            Zero inventory · Zero physical mail sorting · Permanent digital community archive
                        </p>
                    </div>
                </div>

                <!-- Column 2: Platform -->
                <div class="space-y-3">
                    <h4 class="text-xs font-mono font-bold uppercase tracking-wider text-[#94a3b8]">Platform</h4>
                    <ul class="space-y-2.5 text-sm text-[#475569]">
                        <li><a href="{{ route('creators') }}" class="hover:text-[#047857] transition-colors">Time Capsules</a></li>
                        <li><a href="{{ route('explore') }}" class="hover:text-[#047857] transition-colors">Community Archive</a></li>
                        <li><a href="{{ route('nominate') }}" class="hover:text-[#047857] transition-colors">Nominate a Creator</a></li>
                        <li><a href="{{ route('timeline') }}" class="hover:text-[#047857] transition-colors">Milestone Timeline</a></li>
                        <li><a href="{{ route('seal') }}" class="hover:text-[#047857] transition-colors">Leave Something</a></li>
                    </ul>
                </div>

                <!-- Column 3: For Creators -->
                <div class="space-y-3">
                    <h4 class="text-xs font-mono font-bold uppercase tracking-wider text-[#94a3b8]">For Creators</h4>
                    <ul class="space-y-2.5 text-sm text-[#475569]">
                        <li><a href="{{ route('creators.join') }}" class="hover:text-[#047857] transition-colors">Create Your Capsule</a></li>
                        <li><a href="{{ route('creators.access') }}" class="hover:text-[#047857] transition-colors">Creator Studio & Login</a></li>
                        <li><a href="{{ route('creators') }}" class="hover:text-[#047857] transition-colors">Replaces Your PO Box</a></li>
                        <li><a href="{{ route('creators.studio') }}" class="hover:text-[#047857] transition-colors">OBS Stream Mode</a></li>
                    </ul>
                </div>

                <!-- Column 4: Legal & Security -->
                <div class="space-y-3">
                    <h4 class="text-xs font-mono font-bold uppercase tracking-wider text-[#94a3b8]">Trust & Legal</h4>
                    <ul class="space-y-2.5 text-xs text-[#475569]">
                        <li><a href="{{ route('privacy') }}" class="hover:text-[#047857] transition-colors flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-[#047857]"></span> Privacy Policy</a></li>
                        <li><a href="{{ route('terms') }}" class="hover:text-[#047857] transition-colors flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-[#047857]"></span> Terms of Service</a></li>
                        <li><a href="{{ route('data-retention') }}" class="hover:text-[#047857] transition-colors flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-[#047857]"></span> Data Retention</a></li>
                        <li><a href="{{ route('data-deletion') }}" class="hover:text-[#047857] transition-colors flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-[#047857]"></span> Deletion Procedure</a></li>
                        <li><a href="{{ route('security') }}" class="hover:text-[#047857] transition-colors flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-[#047857]"></span> Security Disclosure</a></li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Legal Bar -->
            <div class="pt-8 border-t border-[#e7e5df] flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-[#64748b]">
                <p>© {{ date('Y') }} <strong>FanVault</strong> (Operated by Solotob Technologies LTD) · Permanent Community Time Capsules for Creators · Sealed until Milestone Streams</p>
                <div class="flex flex-wrap items-center justify-center gap-3 text-xs">
                    <a href="{{ route('privacy') }}" class="hover:text-[#047857] transition-colors">Privacy</a>
                    <span>·</span>
                    <a href="{{ route('terms') }}" class="hover:text-[#047857] transition-colors">Terms</a>
                    <span>·</span>
                    <a href="{{ route('data-retention') }}" class="hover:text-[#047857] transition-colors">Retention</a>
                    <span>·</span>
                    <a href="{{ route('data-deletion') }}" class="hover:text-[#047857] transition-colors">Deletion</a>
                    <span>·</span>
                    <a href="{{ route('security') }}" class="hover:text-[#047857] transition-colors">Security</a>
                </div>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
