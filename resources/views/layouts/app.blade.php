<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>window.addEventListener('pageshow', event => { if (event.persisted) window.location.reload(); });</script>
    <script>
        (() => {
            let theme = 'light';
            try { theme = localStorage.getItem('poultrypro-theme') === 'dark' ? 'dark' : 'light'; } catch (_) {}
            document.documentElement.classList.toggle('dark', theme === 'dark');
            document.documentElement.dataset.theme = theme;
        })();
    </script>
    @php
        $activeTenant = app(\App\Services\Tenancy\TenantContext::class)->getTenant();
        $tenantAppName = $activeTenant ? $activeTenant->name : config('app.name', 'MSR BROILERS');
    @endphp
    <title>{{ trim($__env->yieldContent('title')) ? trim($__env->yieldContent('title')) . ' | ' . $tenantAppName : $tenantAppName . ' | Management System' }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" />
    @if(request()->routeIs('profit.*', 'reports.*'))
        <script defer src="https://unpkg.com/@phosphor-icons/web"></script>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>
        .swal2-container {
            z-index: 100000 !important;
        }
    </style>
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-800 dark:bg-zinc-950 dark:text-zinc-50 transition-colors duration-300 font-sans antialiased selection:bg-emerald-500/30 selection:text-emerald-900 dark:selection:bg-emerald-500/30 dark:selection:text-emerald-100 flex flex-col">

<a href="#main-content" class="fixed left-4 top-3 z-[1000] -translate-y-20 rounded-lg bg-emerald-600 px-4 py-2 font-semibold text-white shadow-lg transition-transform focus:translate-y-0">Skip to content</a>

<div x-data="{ sidebarOpen: false }" class="relative flex h-screen overflow-hidden">
    <!-- Overlay with Alpine Transition -->
    <div x-show="sidebarOpen" 
         x-cloak
         x-transition:enter="transition-opacity ease-linear duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="transition-opacity ease-linear duration-300" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         class="fixed inset-0 z-40 bg-zinc-900/40 backdrop-blur-sm lg:hidden"
         @click="sidebarOpen = false"></div>

    @include('partials.sidebar')

    <div class="flex min-w-0 flex-1 flex-col overflow-hidden bg-zinc-50 transition-colors duration-300 dark:bg-zinc-950">
        @include('partials.topbar')

        <main id="main-content" tabindex="-1" class="app-content relative flex-1 overflow-y-auto bg-gradient-to-br from-emerald-50/50 via-zinc-50/80 to-cyan-50/50 px-4 py-5 dark:from-emerald-950 dark:via-zinc-950 dark:to-cyan-950 sm:px-6 sm:py-6 lg:px-8">
            <!-- Glassmorphism Background Blobs -->
            <div class="fixed inset-0 overflow-hidden pointer-events-none z-0 flex justify-center items-center">
                <div class="absolute -left-4 top-0 h-[500px] w-[500px] rounded-full bg-emerald-300/20 opacity-70 mix-blend-multiply blur-3xl dark:bg-emerald-800/15 dark:mix-blend-screen"></div>
                <div class="absolute -right-4 top-0 h-[500px] w-[500px] rounded-full bg-cyan-300/20 opacity-70 mix-blend-multiply blur-3xl dark:bg-cyan-800/15 dark:mix-blend-screen"></div>
            </div>
            
            <div class="mx-auto w-full max-w-[1500px] relative z-10">
                @yield('content')
            </div>
        </main>
    </div>
</div>

<x-toast />
@stack('scripts')
<script>
    // Global Helper to reset submit buttons if form submission is cancelled
    window.resetSubmitButtons = function(form) {
        if (!form) return;
        const submitBtns = form.querySelectorAll('button[type="submit"], input[type="submit"]');
        submitBtns.forEach(btn => {
            btn.disabled = false;
            btn.classList.remove('opacity-75', 'cursor-not-allowed');
            if (btn.hasAttribute('data-original-content')) {
                btn.innerHTML = btn.getAttribute('data-original-content');
                btn.removeAttribute('data-original-content');
            }
        });
    };

    // Global Double-Submit Prevention for all forms
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('form').forEach(form => {
            // Only apply to POST/PUT/DELETE forms, skip GET (search/filter forms)
            const method = (form.getAttribute('method') || 'GET').toUpperCase();
            if (method === 'GET') return;
            
            form.addEventListener('submit', function(e) {
                if (e.defaultPrevented) return; // Skip if another listener cancelled submit (e.g. SweetAlert dialog)
                if (!form.checkValidity()) return; // Let browser HTML5 validation happen
                
                const submitBtns = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                submitBtns.forEach(btn => {
                    if (btn.disabled) return;
                    
                    // Use setTimeout so the form data is collected before disabling
                    setTimeout(() => {
                        btn.disabled = true;
                        btn.classList.add('opacity-75', 'cursor-not-allowed');
                        
                        // If it's a button, add spinner text
                        if (btn.tagName === 'BUTTON' && !btn.hasAttribute('data-custom-loading')) {
                            const originalContent = btn.innerHTML;
                            btn.setAttribute('data-original-content', originalContent);
                            btn.innerHTML = '<span class="material-symbols-rounded animate-spin text-[18px]">sync</span> Processing...';
                        }
                    }, 10);
                });
            });
        });

        // Prevent mouse wheel scroll from accidentally changing number input values
        document.addEventListener('wheel', function(e) {
            if (document.activeElement && document.activeElement.type === 'number') {
                document.activeElement.blur();
            }
        }, { passive: true });
    });
</script>

@if(session()->has('success'))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: @json(session('success')),
            confirmButtonColor: '#10b981',
            confirmButtonText: 'OK',
            timer: 4000,
            timerProgressBar: true,
            background: document.documentElement.classList.contains('dark') ? '#18181b' : '#ffffff',
            color: document.documentElement.classList.contains('dark') ? '#f4f4f5' : '#18181b',
        });
    });
</script>
@endif

@if(session()->has('error'))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: @json(session('error')),
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'OK',
            background: document.documentElement.classList.contains('dark') ? '#18181b' : '#ffffff',
            color: document.documentElement.classList.contains('dark') ? '#f4f4f5' : '#18181b',
        });
    });
</script>
@endif

@stack('modals')
</body>
</html>
