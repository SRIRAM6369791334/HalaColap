<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>window.addEventListener('pageshow', event => { if (event.persisted) window.location.reload(); });</script>
    <title>@yield('title', 'Login') | MSR BROILERS</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" />
    @vite(['resources/css/app.css'])
</head>
<body class="relative flex min-h-screen items-center justify-center overflow-x-hidden bg-zinc-50 p-4 font-sans text-zinc-900 selection:bg-emerald-500/30 sm:p-6">
    <!-- Ambient background -->
    <div class="fixed inset-0 z-0 pointer-events-none">
        <div class="absolute -left-[10%] -top-[20%] h-[70%] w-[50%] rounded-full bg-emerald-200/50 blur-[100px]"></div>
        <div class="absolute -right-[10%] top-[40%] h-[60%] w-[40%] rounded-full bg-sky-200/50 blur-[100px]"></div>
    </div>

    <!-- Content Wrapper -->
    <div class="relative z-10 w-full max-w-md">
        @yield('content')
    </div>
</body>
</html>
