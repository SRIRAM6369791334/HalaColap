@extends('layouts.app')
@section('title', 'Upcoming Alerts')

@section('content')
<div>
    
    <x-page-header title="System Alerts & Notifications" subtitle="Track upcoming payments and critical system business dates" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
         
        {{-- EMI Alerts Section --}}
        <div class="lg:col-span-2">
            <x-card title="Upcoming EMI Payments" padding="p-0">
                <x-slot name="actions">
                    <x-badge variant="warning" size="sm" class="uppercase">Next 30 Days</x-badge>
                </x-slot>
                
                <div class="p-4 sm:p-6">
                    <x-data-table :headers="['Item/Asset', 'Due Date', 'Amount', 'Status']">
                        @forelse($upcomingEmis as $emi)
                            <tr class="transition-colors hover:bg-zinc-50/80 dark:hover:bg-zinc-800/50 group">
                                <td class="px-6 py-4">
                                    <p class="font-bold text-zinc-900 dark:text-zinc-100">{{ $emi->item }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 font-outfit">{{ $emi->note ?? 'No description' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-jetbrains {{ $emi->due_date->isToday() ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-zinc-700 dark:text-zinc-300' }}">
                                        {{ $emi->due_date->format('d M Y') }}
                                    </span>
                                    <p class="text-[10px] uppercase font-bold text-zinc-500 dark:text-zinc-500 mt-0.5">{{ $emi->due_date->diffForHumans() }}</p>
                                </td>
                                <td class="px-6 py-4 font-jetbrains font-bold text-zinc-900 dark:text-zinc-100">
                                    ₹{{ number_format($emi->amount, 0) }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($emi->status === 'Paid')
                                        <x-badge variant="success" dot="true">Paid</x-badge>
                                    @elseif($emi->due_date->isPast())
                                        <x-badge variant="danger" dot="true" class="uppercase">Overdue</x-badge>
                                    @else
                                        <x-badge variant="warning" dot="true">Pending</x-badge>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-zinc-500 dark:text-zinc-400">
                                        <span class="material-symbols-rounded text-4xl mb-2 opacity-50">event_available</span>
                                        <p class="text-sm">No upcoming EMIs found for the next 30 days.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </x-data-table>
                </div>
            </x-card>
        </div>

        {{-- System Logs/Notifications Sidebar --}}
        <div class="space-y-6">
            <x-card title="System Status" padding="p-6">
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="relative flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                        </div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300 font-outfit">Database connected & synchronized</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="h-2.5 w-2.5 rounded-full bg-emerald-500"></div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300 font-outfit">Daily backups completed (02:00 AM)</p>
                    </div>
                </div>
            </x-card>

            <div class="rounded-2xl border border-emerald-700/50 bg-gradient-to-br from-emerald-600 to-emerald-800 p-6 text-white shadow-lg shadow-emerald-900/20 relative overflow-hidden">
                <!-- Decorative background elements -->
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full blur-2xl -mr-10 -mt-10"></div>
                
                <div class="relative z-10">
                    <h3 class="text-sm font-bold uppercase tracking-widest mb-3 opacity-90 flex items-center gap-2">
                        <span class="material-symbols-rounded text-emerald-200">lightbulb</span>
                        Pro-Tip
                    </h3>
                    <p class="text-sm leading-relaxed opacity-95 font-outfit">
                        Keep your EMI data updated to ensure accurate profit and loss projections on the <strong class="font-bold text-white">Analytics Dashboard</strong>.
                    </p>
                    <a href="{{ route('expenses.index') }}" class="inline-flex items-center gap-2 mt-5 text-xs font-bold bg-white/20 hover:bg-white/30 px-4 py-2 rounded-xl transition-colors backdrop-blur-sm">
                        Manage Expenses
                        <span class="material-symbols-rounded text-[16px]">arrow_forward</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
