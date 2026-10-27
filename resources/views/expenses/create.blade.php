@extends('layouts.app')
@section('title', 'Add New Expense')

@section('content')
<div class="animate-fade-in max-w-2xl mx-auto">
    
    <div class="mb-6">
        <a href="{{ route('expenses.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 hover:text-emerald-700 uppercase tracking-wider mb-2">
            <span class="material-symbols-rounded text-sm">arrow_back</span>
            Back to Expenses
        </a>
        <h1 class="font-cabinet text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">Record New Expense</h1>
        <p class="mt-1 font-outfit text-sm text-zinc-500 dark:text-zinc-400">Log an operational or miscellaneous expenditure</p>
    </div>

    <x-card>
        <div class="p-6">
            <form action="{{ route('expenses.store') }}" method="POST">
                @csrf
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
                    <x-form.input type="date" name="date" label="Date" required value="{{ date('Y-m-d') }}" />
                    <x-form.select name="category" label="Category" required>
                        <option value="Feed">Feed</option>
                        <option value="Medicine">Medicine</option>
                        <option value="Labor">Labor</option>
                        <option value="Electricity">Electricity</option>
                        <option value="Transport">Transport</option>
                        <option value="Miscellaneous">Miscellaneous</option>
                    </x-form.select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
                    <x-form.input type="number" name="amount" label="Amount (Rs)" required step="0.01" placeholder="0.00" class="text-xl font-bold" />
                    <x-form.select name="payment_method" label="Payment Method" required>
                        <option value="Cash" {{ old('payment_method') === 'Cash' ? 'selected' : '' }}>Cash</option>
                        <option value="Bank Transfer" {{ old('payment_method') === 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    </x-form.select>
                </div>

                <div class="mb-6">
                    <div class="flex flex-col space-y-2 font-outfit">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Description</label>
                        <textarea name="description" rows="3" class="bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700/50 text-zinc-900 dark:text-zinc-100 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 transition-colors" placeholder="Optional notes about this expense..."></textarea>
                    </div>
                </div>

                <div class="pt-5 border-t border-zinc-200/50 dark:border-zinc-800/50 flex justify-end gap-3">
                    <x-button type="reset" variant="outline">Clear</x-button>
                    <x-button type="submit" variant="primary" icon="check">Save Expense</x-button>
                </div>
            </form>
        </div>
    </x-card>
</div>
@endsection
