@extends('layouts.app')
@section('title', 'Expenses & EMI')

@section('content')
<div class="animate-fade-in">
    <x-page-header title="Expenses & EMI" subtitle="Manage operational burn and financial obligations">
        <x-slot:actions>
            <x-button variant="outline" href="{{ route('expenses.export') }}" icon="download">
                Export
            </x-button>
            @can('create expenses')
            <x-button variant="primary" x-data x-on:click="$dispatch('open-modal', 'add-expense')" icon="add">
                Record Expense
            </x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <x-stat-card 
            label="Monthly Burn" 
            value="Rs {{ number_format($totals['total_expenses'], 0) }}" 
            icon="trending_up" 
            color="rose" />
        <x-stat-card 
            label="EMI Total" 
            value="Rs {{ number_format($totals['total_emis'], 0) }}" 
            icon="account_balance" 
            color="amber" />
        <div class="rounded-2xl border border-zinc-200/80 dark:border-zinc-800/80 bg-white/70 dark:bg-zinc-900/70 backdrop-blur-xl p-6 shadow-sm flex flex-col justify-center">
            <p class="font-outfit text-sm font-medium text-emerald-600 dark:text-emerald-400">Financial Outlook</p>
            <p class="mt-2 font-outfit text-sm italic text-zinc-500 dark:text-zinc-400 font-medium">"Optimizing cash flow by tracking every penny."</p>
        </div>
    </div>

    @if(isset($pendingWeightLossBatches) && $pendingWeightLossBatches->isNotEmpty())
        <div class="mb-8 rounded-2xl border border-amber-200 dark:border-amber-800/60 bg-amber-50/70 dark:bg-amber-900/20 p-5 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-rounded text-amber-600 dark:text-amber-400">scale</span>
                <h3 class="font-cabinet text-base font-bold text-amber-900 dark:text-amber-100">Pending Weight Loss Approvals</h3>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300">
                    {{ $pendingWeightLossBatches->count() }} Pending
                </span>
            </div>
            <div class="space-y-3">
                @foreach($pendingWeightLossBatches as $pBatch)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-white dark:bg-zinc-900 rounded-xl border border-amber-200/70 dark:border-amber-800/40 gap-3 shadow-xs">
                        <div>
                            <p class="font-bold text-sm text-zinc-900 dark:text-zinc-100">
                                Day-Load Batch #{{ $pBatch->id }} — {{ $pBatch->billing_date->format('d M Y (l)') }}
                            </p>
                            <p class="text-xs text-zinc-500 font-jetbrains mt-1">
                                Loss Weight: <span class="font-bold text-amber-700 dark:text-amber-400">{{ number_format((float)$pBatch->total_loss_weight, 2) }} kg</span>
                                · Financial Amount: <span class="font-bold text-rose-600 dark:text-rose-400">Rs {{ number_format((float)$pBatch->weight_loss_amount, 2) }}</span>
                            </p>
                        </div>
                        <div>
                            @can('create expenses')
                            <form method="POST" action="{{ route('billing.day-load.approve-weight-loss', $pBatch) }}" onsubmit="return confirm('Approve Weight Loss Expense of Rs {{ number_format((float)$pBatch->weight_loss_amount, 2) }} for {{ $pBatch->billing_date->format('d M Y') }}?')">
                                @csrf
                                <x-button type="submit" variant="primary" size="sm" icon="check_circle">
                                    Approve Weight Loss (Rs {{ number_format((float)$pBatch->weight_loss_amount, 0) }})
                                </x-button>
                            </form>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Main Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- Ledger Table Card --}}
        <div class="lg:col-span-2">
            <x-card>
                <div class="p-4 border-b border-zinc-200/50 dark:border-zinc-800/50">
                    <h2 class="font-cabinet text-lg font-bold text-zinc-900 dark:text-zinc-50">General Expense Ledger</h2>
                </div>
                
                <x-data-table :headers="['Date', 'Category', 'Description', 'Payment Method', 'Amount', 'Action']">
                    @forelse($expenses as $e)
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors group">
                            <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $e->date->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <x-badge :variant="$e->category === 'Weight Loss' ? 'rose' : 'zinc'">{{ $e->category }}</x-badge>
                            </td>
                            <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                                {{ $e->description }}
                            </td>
                            <td class="px-6 py-4">
                                <x-badge :variant="$e->payment_method === 'Bank Transfer' ? 'info' : 'zinc'">
                                    {{ $e->payment_method ?? 'Cash' }}
                                </x-badge>
                            </td>
                            <td class="px-6 py-4 font-jetbrains font-medium text-rose-600 dark:text-rose-400">
                                <x-currency :amount="$e->amount" />
                            </td>
                            <td class="px-6 py-4">
                                @can('delete expenses')
                                <form action="{{ route('expenses.destroy', $e) }}" method="POST" onsubmit="return confirm('Delete this expense entry?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-zinc-400 hover:text-rose-600 transition-colors" title="Delete">
                                        <span class="material-symbols-rounded text-lg">delete</span>
                                    </button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <x-slot:empty>
                            <x-empty-state 
                                icon="receipt_long" 
                                title="No expenses recorded" 
                                description="No expenses logged in this cycle." />
                        </x-slot:empty>
                    @endforelse

                    @if($expenses->hasPages())
                        <x-slot:pagination>
                            {{ $expenses->links() }}
                        </x-slot:pagination>
                    @endif
                </x-data-table>
            </x-card>
        </div>

        {{-- EMI obligations --}}
        <div>
            <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-4 font-outfit">EMI Obligations</h3>
            <div class="flex flex-col gap-4">
                @forelse($emis as $emi)
                    @php
                        $statusMap = [
                            'Paid' => ['variant' => 'success', 'icon' => 'check_circle'],
                            'Overdue' => ['variant' => 'danger', 'icon' => 'warning'],
                            'Upcoming' => ['variant' => 'info', 'icon' => 'schedule']
                        ];
                        $style = $statusMap[$emi->status] ?? $statusMap['Upcoming'];
                        
                        $displayName = $emi->loan_name;
                        if ($emi->emi_type === 'Vendor' && $emi->vendor) {
                            $displayName = ($emi->vendor->firm_name ?? $emi->vendor->name) . ' — ' . $emi->loan_name;
                        } elseif ($emi->bank_name) {
                            $displayName = $emi->bank_name . ' — ' . $emi->loan_name;
                        }
                    @endphp
                    <div class="bg-white/80 dark:bg-zinc-900/80 backdrop-blur-xl border border-zinc-200/50 dark:border-zinc-800/50 rounded-2xl p-5 hover:border-emerald-500/30 transition-colors">
                        <div class="flex items-center justify-between mb-2">
                            <div class="font-cabinet text-base font-bold text-zinc-900 dark:text-white">{{ $displayName }}</div>
                            <span class="material-symbols-rounded text-xl {{ $emi->status == 'Paid' ? 'text-emerald-500' : ($emi->status == 'Overdue' ? 'text-rose-500' : 'text-blue-500') }}">{{ $style['icon'] }}</span>
                        </div>
                        <div class="flex items-center justify-between mt-4">
                            <div class="flex flex-col gap-1">
                                <span class="text-xs text-zinc-500 uppercase tracking-wider">Due: {{ $emi->due_date->format('M d, Y') }}</span>
                                <x-badge :variant="$style['variant']">{{ $emi->status }}</x-badge>
                            </div>
                            <div class="font-jetbrains text-lg font-bold text-zinc-900 dark:text-white">
                                <x-currency :amount="$emi->amount" />
                            </div>
                        </div>
                    </div>
                @empty
                    <x-empty-state 
                        icon="account_balance" 
                        title="No Active EMIs" 
                        description="You have no upcoming EMI payments." />
                @endforelse
            </div>
        </div>

    </div>
</div>

@endsection

@push('modals')
<x-modal name="add-expense" title="Record Expense" subtitle="Log operational expenditures" icon="receipt_long" maxWidth="720" :show="$errors->any()">
    <form id="add-expense-form" action="{{ route('expenses.store') }}" method="POST">
        @csrf

        {{-- Category Selection --}}
        <div class="mb-8">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 font-outfit mb-3.5">
                Category <span class="text-zinc-400 dark:text-zinc-500 text-xs ml-0.5">*</span>
            </label>
            <div class="grid grid-cols-5 gap-3">
                @php $catIcons = ['Fuel' => 'local_gas_station', 'Salary' => 'payments', 'Transport' => 'local_shipping', 'Utility' => 'bolt', 'Misc' => 'more_horiz']; @endphp
                @php $catColors = ['Fuel' => 'text-orange-500', 'Salary' => 'text-blue-500', 'Transport' => 'text-amber-500', 'Utility' => 'text-purple-500', 'Misc' => 'text-zinc-400']; @endphp
                @foreach(['Fuel','Salary','Transport','Utility','Misc'] as $c)
                <label class="group relative flex flex-col items-center gap-2 py-4 px-2 rounded-2xl border-2 cursor-pointer transition-all duration-200 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/80 dark:has-[:checked]:bg-emerald-500/12 has-[:checked]:shadow-[0_0_0_1px_rgba(16,185,129,0.15),0_4px_12px_rgba(16,185,129,0.15)] border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 bg-white/50 dark:bg-zinc-900/50">
                    <input type="radio" name="category" value="{{ $c }}" class="sr-only" {{ $c === 'Fuel' ? 'checked' : '' }} required>
                    <div class="relative">
                        <span class="material-symbols-rounded text-[28px] {{ $catColors[$c] }}">{{ $catIcons[$c] }}</span>
                        <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-emerald-500 text-white flex items-center justify-center scale-0 group-has-[:checked]:scale-100 transition-transform duration-200">
                            <span class="material-symbols-rounded text-[12px]">check</span>
                        </span>
                    </div>
                    <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 group-has-[:checked]:text-emerald-700 dark:group-has-[:checked]:text-emerald-300 group-has-[:checked]:font-bold transition-all">{{ $c }}</span>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Date & Amount --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-8">
            <x-form.input type="date" name="date" label="Date" required value="{{ date('Y-m-d') }}" icon="calendar_month" />
            <x-form.input type="number" name="amount" label="Amount" required step="0.01" min="0.01" placeholder="Enter amount" icon="currency_rupee" />
        </div>

        {{-- Description --}}
        <div class="mb-8">
            <x-form.textarea name="description" label="Description" required placeholder="e.g. Purchased MSR BROILERS feed from ABC Traders" rows="4" />
        </div>

        {{-- Payment Method --}}
        <div class="mb-2">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 font-outfit mb-3.5">
                Payment Method <span class="text-zinc-400 dark:text-zinc-500 text-xs ml-0.5">*</span>
            </label>
            <div class="grid grid-cols-4 gap-2.5">
                @php $pmOptions = [['value' => 'Cash', 'icon' => 'payments', 'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50'], ['value' => 'Bank Transfer', 'icon' => 'account_balance', 'color' => 'text-blue-500', 'bg' => 'bg-blue-50'], ['value' => 'UPI', 'icon' => 'smartphone', 'color' => 'text-violet-500', 'bg' => 'bg-violet-50'], ['value' => 'Card', 'icon' => 'credit_card', 'color' => 'text-rose-500', 'bg' => 'bg-rose-50']]; @endphp
                @foreach($pmOptions as $pm)
                <label class="group relative flex flex-col items-center gap-2 py-4 px-1 rounded-2xl border-2 cursor-pointer transition-all duration-200 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/80 dark:has-[:checked]:bg-emerald-500/12 has-[:checked]:shadow-[0_0_0_1px_rgba(16,185,129,0.15),0_4px_12px_rgba(16,185,129,0.15)] border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 bg-white/50 dark:bg-zinc-900/50">
                    <input type="radio" name="payment_method" value="{{ $pm['value'] }}" class="sr-only" {{ $loop->first ? 'checked' : '' }} required>
                    <div class="w-9 h-9 rounded-full {{ $pm['bg'] }} dark:{{ $pm['bg'] }}/10 flex items-center justify-center {{ $pm['color'] }}">
                        <span class="material-symbols-rounded text-xl">{{ $pm['icon'] }}</span>
                    </div>
                    <span class="text-[11px] font-semibold text-zinc-500 dark:text-zinc-400 group-has-[:checked]:text-emerald-700 dark:group-has-[:checked]:text-emerald-300 group-has-[:checked]:font-bold transition-all text-center leading-tight">{{ $pm['value'] }}</span>
                </label>
                @endforeach
            </div>
        </div>

        <x-slot:footer>
            <x-button type="button" variant="outline" x-on:click="show = false">Cancel</x-button>
            <x-button type="submit" form="add-expense-form" variant="primary" icon="check" class="px-8">Log Expense</x-button>
        </x-slot:footer>
    </form>
</x-modal>
@endpush
