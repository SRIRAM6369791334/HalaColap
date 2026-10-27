@extends('layouts.app')
@section('title', 'Expense Categories')

@section('content')
<div class="mb-8 animate-fade-in">
    <a href="{{ route('expenses.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 uppercase tracking-wider mb-2">
        <span class="material-symbols-rounded text-sm">arrow_back</span>
        Back to Expenses
    </a>
    <h1 class="font-cabinet text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">Expense Analysis by Category</h1>
    <p class="mt-1 font-outfit text-sm text-zinc-500 dark:text-zinc-400">Classification of business expenditures</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 animate-fade-in">
    @forelse($categories as $cat)
        <x-card class="group hover:border-emerald-500/30 transition-all duration-300 hover:-translate-y-1">
            <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center justify-center w-12 h-12 bg-emerald-100/80 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-xl font-cabinet font-bold text-xl group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                        {{ strtoupper(substr($cat->category, 0, 1)) }}
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest font-outfit">Total Spent</p>
                        <p class="text-xl font-bold font-jetbrains text-zinc-900 dark:text-white">
                            <x-currency :amount="$cat->total" />
                        </p>
                    </div>
                </div>
                <h3 class="font-cabinet text-lg font-bold text-zinc-900 dark:text-white mb-1 capitalize">{{ $cat->category }}</h3>
                <p class="font-outfit text-sm text-zinc-500 dark:text-zinc-400 mb-6">{{ $cat->count }} transactions recorded</p>
                
                <div class="flex gap-2">
                    <a href="{{ route('expenses.index', ['category' => $cat->category]) }}" class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest hover:underline">
                        View Logs
                        <span class="material-symbols-rounded text-sm">arrow_forward</span>
                    </a>
                </div>
            </div>
        </x-card>
    @empty
        <div class="col-span-full">
            <x-empty-state 
                icon="category" 
                title="No categories" 
                description="No expense categories detected yet." />
        </div>
    @endforelse
</div>
@endsection
