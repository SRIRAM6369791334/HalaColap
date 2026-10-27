@extends('layouts.auth')
@section('title', 'Session expired')

@section('content')
<section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-lg sm:p-8" aria-labelledby="expiry-title">
    <span class="material-symbols-rounded mb-4 text-3xl text-amber-600" aria-hidden="true">schedule</span>
    <h1 id="expiry-title" class="text-2xl font-bold text-zinc-900">Your session has expired</h1>
    <p class="mt-3 text-sm leading-6 text-zinc-600">This form was open for too long, or your session changed in another tab. Your last submission was not saved.</p>
    <p class="mt-2 text-sm leading-6 text-zinc-600">Open a fresh page and enter the details again.</p>
    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="mt-6 inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
        {{ auth()->check() ? 'Return to dashboard' : 'Sign in again' }}
    </a>
</section>
@endsection
