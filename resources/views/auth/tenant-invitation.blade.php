@extends('layouts.app')

@section('title', 'Business Invitation')

@section('content')
<div class="mx-auto flex min-h-[65vh] max-w-2xl items-center px-4 py-10">
    <section class="w-full rounded-2xl border border-zinc-200 bg-white p-6 shadow-lg dark:border-zinc-800 dark:bg-zinc-900 sm:p-8" aria-labelledby="invitation-title">
        <div class="flex items-start gap-4">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300" aria-hidden="true">
                <span class="material-symbols-rounded">business_center</span>
            </span>
            <div>
                <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">Business access invitation</p>
                <h1 id="invitation-title" class="mt-1 text-2xl font-bold text-zinc-950 dark:text-white">Join {{ $invitation->tenant->name }}</h1>
                <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                    Your username in this business will be <strong>{{ $invitation->username }}</strong> with the <strong>{{ Str::title(str_replace('_', ' ', $invitation->role)) }}</strong> role.
                </p>
            </div>
        </div>

        @if($errors->any())
            <div role="alert" class="mt-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-200">{{ $errors->first() }}</div>
        @endif

        <div class="mt-6 rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-600 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300">
            Signed in as <strong>{{ auth()->user()->email }}</strong>. This invitation expires {{ $invitation->expires_at->diffForHumans() }}.
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-zinc-300 px-5 py-2.5 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Not now</a>
            <form action="{{ URL::temporarySignedRoute('tenant-invitations.accept', $invitation->expires_at, ['token' => $token]) }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 sm:w-auto">Accept invitation</button>
            </form>
        </div>
    </section>
</div>
@endsection
