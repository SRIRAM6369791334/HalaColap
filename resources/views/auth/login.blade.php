@extends('layouts.auth')
@section('title', 'Login')

@section('content')
<div id="login-container">
    <div class="mb-8 flex flex-col items-center justify-center gap-3">
        <div id="logo-icon" class="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-600/20">
            <span class="material-symbols-rounded text-4xl" aria-hidden="true">egg</span>
        </div>
        <div class="text-center">
            <h1 class="text-3xl font-black tracking-tight text-zinc-900 drop-shadow-sm">MSR BROILERS</h1>
            <p class="text-sm font-medium tracking-wide text-zinc-500">Management System</p>
        </div>
    </div>

    <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-6 shadow-xl shadow-zinc-200/60 sm:p-8">
        <!-- Inner subtle glow -->
        <div class="absolute -top-24 -right-24 h-48 w-48 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none"></div>

        <h2 class="mb-2 text-2xl font-bold tracking-tight text-zinc-900">Welcome back</h2>
        <p class="mb-8 text-sm font-medium text-zinc-500">Sign in with your email or username</p>

        @if($errors->any())
            <div class="mb-6 animate-shake rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700 backdrop-blur-md">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="relative z-10 space-y-5">
            @csrf

            <div class="group">
                <label for="login" class="mb-1.5 block text-sm font-bold text-zinc-700 transition-colors group-focus-within:text-emerald-600">Email or Username</label>
                <div class="relative">
                    <input type="text" id="login" name="login" required autofocus autocomplete="username"
                           value="{{ old('login') }}"
                           placeholder="you@example.com or username"
                           aria-describedby="login-help @error('login') login-error @enderror"
                           @error('login') aria-invalid="true" @enderror
                           class="peer min-h-12 w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-sm font-semibold text-zinc-900 shadow-sm transition-colors placeholder:text-zinc-400 hover:border-zinc-400 focus:border-emerald-500 focus:ring-emerald-500/20 @error('login') border-rose-400 @enderror">
                </div>
                <p id="login-help" class="mt-1.5 text-xs font-medium text-zinc-500">If your username is shared across businesses, use your email address.</p>
                @error('login')<p id="login-error" role="alert" class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div class="group">
                <label for="password" class="mb-1.5 block text-sm font-bold text-zinc-700 transition-colors group-focus-within:text-emerald-600">Password</label>
                <div class="relative">
                    <input type="password" id="password" name="password" required
                           placeholder="••••••••"
                           class="peer min-h-12 w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 pr-14 text-sm font-semibold text-zinc-900 shadow-sm transition-colors placeholder:text-zinc-400 hover:border-zinc-400 focus:border-emerald-500 focus:ring-emerald-500/20">
                    <button type="button" id="toggle-password" class="absolute right-1 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-lg text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-emerald-700" aria-label="Show password" aria-pressed="false">
                        <span id="password-icon" class="material-symbols-rounded select-none text-xl" aria-hidden="true">visibility</span>
                    </button>
                </div>
            </div>

            <!-- <div class="flex items-center gap-3 pt-2 pb-4">
                <div class="relative flex h-5 w-5 items-center justify-center">
                    <input type="checkbox" id="remember" name="remember" value="1"
                           class="peer h-5 w-5 cursor-pointer appearance-none rounded-md border border-zinc-300 bg-white checked:border-emerald-500 checked:bg-emerald-500 transition-all hover:border-emerald-400">
                    <span class="material-symbols-rounded absolute pointer-events-none text-white opacity-0 text-[14px] font-bold peer-checked:opacity-100 transition-opacity">check</span>
                </div>
                <label for="remember" class="cursor-pointer text-sm font-medium text-zinc-600 transition-colors hover:text-zinc-900">Remember me</label>
            </div> -->

            <button type="submit" id="magnetic-btn"
                    class="group relative flex min-h-12 w-full items-center justify-center rounded-xl border border-emerald-700/30 bg-emerald-600 px-4 py-3 text-sm font-bold text-white shadow-md transition-colors hover:bg-emerald-700">
                <span class="relative z-10 flex items-center gap-2">
                    Sign In
                    <span class="material-symbols-rounded text-lg transition-transform duration-300 group-hover:translate-x-1">arrow_forward</span>
                </span>
            </button>
        </form>
    </div>

    <p class="mt-8 text-center text-xs font-semibold tracking-wide text-zinc-400">MSR BROILERS v1.0 &copy; {{ date('Y') }}</p>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        // Toggle Password Visibility Logic
        const passwordInput = document.getElementById('password');
        const togglePasswordBtn = document.getElementById('toggle-password');
        const passwordIcon = document.getElementById('password-icon');

        if (togglePasswordBtn && passwordInput && passwordIcon) {
            togglePasswordBtn.addEventListener('click', () => {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                passwordIcon.textContent = type === 'password' ? 'visibility' : 'visibility_off';
                togglePasswordBtn.setAttribute('aria-pressed', type === 'text' ? 'true' : 'false');
                togglePasswordBtn.setAttribute('aria-label', type === 'password' ? 'Show password' : 'Hide password');
            });
        }
    });
</script>
@endsection
