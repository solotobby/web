<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ $title ?? 'Creator Studio — FanVault' }}</title>
    <meta name="description" content="Manage your community time vault, celebrate milestones, and view fan letters in FanVault Studio.">
    
    <link rel="icon" href="{{ asset('assets/favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@500;600;700&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400;1,9..40,600&family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,500;1,9..144,600&family=IBM+Plex+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-[#faf9f5] text-[#0f172a] min-h-screen flex flex-col font-sans antialiased selection:bg-[#047857]/20 pb-[env(safe-area-inset-bottom,0)]">
    <!-- Ambient subtle background glow -->
    <div class="fixed inset-0 pointer-events-none -z-10 bg-[radial-gradient(circle_at_4%_4%,rgba(4,120,87,0.05)_0%,transparent_35%),radial-gradient(circle_at_96%_8%,rgba(202,138,4,0.04)_0%,transparent_30%),radial-gradient(circle_at_50%_98%,rgba(6,78,59,0.03)_0%,transparent_40%)]" aria-hidden="true"></div>

    <main id="main" class="flex-1 w-full min-h-screen flex flex-col">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
