@extends('layouts.app')
@section('title', 'Record Customer Collection')

@section('content')
<div class="animate-fade-in max-w-4xl mx-auto">
    <x-page-header title="Record Collection" subtitle="Enter payment details to update the customer ledger">
        <x-slot:actions>
            <x-button variant="outline" href="{{ route('masters.customers.index') }}" icon="arrow_back">
                Back to Collections
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="bg-white/80 dark:bg-zinc-900/80 backdrop-blur-xl border border-zinc-200/60 dark:border-zinc-800/60 shadow-[0_8px_32px_rgba(0,0,0,0.04)] rounded-3xl overflow-hidden p-6 sm:p-10">
        
        @if(isset($errors) && $errors->any())
            <div class="p-4 mb-6 rounded-2xl bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-850/30 text-red-600 dark:text-red-400 text-sm">
                <p class="font-bold mb-1">Please fix the following errors:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('payments.customers.store') }}" method="POST" class="space-y-8" x-data="{ codAmount: 0, bankTransferAmount: 0 }">
            @csrf
            
            {{-- Customer Selection Section --}}
            <section class="space-y-4">
                <div class="flex items-center gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                    <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <span class="material-symbols-rounded">person</span>
                    </div>
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 tracking-tight font-cabinet">Customer Details</h3>
                </div>

                <div class="p-6 bg-zinc-50 dark:bg-zinc-800/40 rounded-2xl border border-zinc-200/60 dark:border-zinc-700/60 transition-all hover:border-emerald-500/30">
                    @if($selected_customer_id && $customers->count() === 1)
                        @php $c = $customers->first(); @endphp
                        <input type="hidden" name="customer_id" value="{{ $c->id }}">
                        <div>
                            <span class="block text-xs font-bold text-zinc-500 uppercase mb-2">Customer</span>
                            <div class="text-lg font-bold text-zinc-800 dark:text-white">
                                {{ $c->name }}
                            </div>
                            <div class="text-sm font-semibold text-rose-500 mt-1">
                                Pending Balance: Rs {{ number_format($c->balance, 2) }}
                            </div>
                        </div>
                    @else
                        <x-form.select name="customer_id" label="Select Customer" required>
                            <option value="">Choose customer…</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ $selected_customer_id == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }} — Pending: Rs {{ number_format($c->balance, 2) }}
                                </option>
                            @endforeach
                        </x-form.select>
                    @endif
                </div>
            </section>

            {{-- Amount Section --}}
            <section class="space-y-4">
                <div class="flex items-center gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                    <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <span class="material-symbols-rounded">payments</span>
                    </div>
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 tracking-tight font-cabinet">Payment Information</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6 bg-emerald-50/50 dark:bg-emerald-900/10 rounded-2xl border border-emerald-100 dark:border-emerald-800/30 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-2">COD / Cash Amount (Rs) <span class="text-rose-500">*</span></label>
                        <input type="number" name="cod_amount" required step="0.01" min="0" x-model.number="codAmount" class="block w-full rounded-xl border-emerald-200 dark:border-emerald-800 focus:ring-emerald-500 focus:border-emerald-500 bg-white dark:bg-zinc-900 text-2xl font-black text-zinc-800 dark:text-white shadow-sm py-3 px-4 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-2">Bank Transfer Amount (Rs) <span class="text-rose-500">*</span></label>
                        <input type="number" name="bank_transfer_amount" required step="0.01" min="0" x-model.number="bankTransferAmount" class="block w-full rounded-xl border-emerald-200 dark:border-emerald-800 focus:ring-emerald-500 focus:border-emerald-500 bg-white dark:bg-zinc-900 text-2xl font-black text-zinc-800 dark:text-white shadow-sm py-3 px-4 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase mb-2">Total Amount (Rs)</label>
                        <div class="rounded-xl border border-emerald-200 dark:border-emerald-800 bg-white dark:bg-zinc-900 text-2xl font-black text-emerald-600 dark:text-emerald-400 py-3 px-4 shadow-sm" x-text="'Rs ' + (codAmount + bankTransferAmount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-form.input type="date" name="date" label="Payment Date" required value="{{ date('Y-m-d') }}" class="!bg-white dark:!bg-zinc-900 shadow-sm" />
                </div>
            </section>

            {{-- Mode and Type Section --}}
            <section class="space-y-4">
                <div class="flex items-center gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                    <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <span class="material-symbols-rounded">receipt_long</span>
                    </div>
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 tracking-tight font-cabinet">Transaction Details</h3>
                </div>

                <div class="p-6 bg-zinc-50 dark:bg-zinc-800/40 rounded-2xl border border-zinc-200/60 dark:border-zinc-700/60">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3 font-cabinet tracking-wide uppercase">Receipt Type <span class="text-rose-500">*</span></label>
                            <div class="grid grid-cols-3 gap-3">
                                @foreach(['Part','Full','Advance'] as $t)
                                    <label class="flex items-center justify-center p-3 border border-zinc-200 dark:border-zinc-700 rounded-xl cursor-pointer bg-white dark:bg-zinc-900 shadow-sm hover:shadow-md hover:emerald-400 dark:hover:border-emerald-500 transition-all group has-[:checked]:border-emerald-500 has-[:checked]:ring-2 has-[:checked]:ring-emerald-500 has-[:checked]:bg-emerald-50 dark:has-[:checked]:bg-emerald-900/20">
                                        <input type="radio" name="payment_type" value="{{ $t }}" {{ $t == 'Part' ? 'checked' : '' }} class="sr-only">
                                        <span class="text-sm font-bold text-zinc-600 dark:text-zinc-400 group-hover:text-zinc-900 dark:group-hover:text-zinc-100 transition-colors">{{ $t }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    
                    <x-form.input name="notes" label="Remarks / Reference" placeholder="e.g. UPI Transaction ID or Cheque Number..." class="!bg-white dark:!bg-zinc-900 shadow-sm" />
                </div>
            </section>

            <div class="flex items-center justify-end gap-4 pt-6 border-t border-zinc-100 dark:border-zinc-800">
                <x-button type="button" variant="outline" href="{{ route('payments.customers.index') }}" class="hover:bg-zinc-100">Cancel</x-button>
                <x-button type="submit" variant="primary" icon="check_circle" size="lg" class="shadow-xl shadow-emerald-500/20 px-8">Confirm & Record</x-button>
            </div>
            
        </form>
    </div>
</div>
@endsection
