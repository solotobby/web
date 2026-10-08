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
    {{ $slot }}

    @livewireScripts
</body>
</html>
