@extends('layouts.app')
@section('title', 'Setup New EMI')

@section('content')
<div class="mb-6 animate-fade-in max-w-2xl mx-auto">
    <a href="{{ route('expenses.emis.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 hover:text-emerald-700 uppercase tracking-wider mb-2">
        <span class="material-symbols-rounded text-sm">arrow_back</span>
        Back to EMIs
    </a>
    <h1 class="font-cabinet text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">Setup New EMI</h1>
    <p class="mt-1 font-outfit text-sm text-zinc-500 dark:text-zinc-400">Define periodic installment details</p>
</div>

<div class="max-w-2xl mx-auto animate-fade-in">
    <x-card>
        <div class="p-6">
            <form action="{{ route('expenses.emis.store') }}" method="POST">
                @csrf
                
                <div class="grid grid-cols-1 gap-5 mb-5">
                    <x-form.select name="emi_type" id="emi_type" label="EMI Type" required onchange="toggleEntitySelect()">
                        <option value="Customer">Customer (To Receive)</option>
                        <option value="Dealer">Dealer (To Receive)</option>
                        <option value="Vendor">Vendor (To Pay)</option>
                        <option value="Bank Loan">Bank Loan / Finance (To Pay)</option>
                    </x-form.select>
                </div>

                <div class="mb-5" id="loan_name_div">
                    <x-form.input type="text" name="loan_name" id="loan_name" label="Loan / EMI Name" placeholder="e.g. MSR BROILERS House Loan, Vehicle EMI" required />
                </div>

                <div class="mb-5 hidden" id="entity_div">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 font-outfit mb-2" id="entity_label">Select Person <span class="text-rose-500">*</span></label>
                    <select name="entity_id" id="entity_id" class="bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700/50 text-zinc-900 dark:text-zinc-100 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 transition-colors">
                        <option value="">-- Select --</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                    <x-form.input type="number" name="amount" label="Monthly Amount (Rs)" step="0.01" required />
                    <x-form.input type="date" name="due_date" label="Due Date" required />
                </div>

                <div class="grid grid-cols-1 gap-5 mb-6">
                    <div class="bg-blue-50/50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-800 rounded-xl p-4 flex gap-3">
                        <span class="material-symbols-rounded text-blue-500 mt-0.5">info</span>
                        <div>
                            <p class="text-sm text-blue-800 dark:text-blue-300 font-medium">Status is calculated automatically</p>
                            <p class="text-xs text-blue-600/80 dark:text-blue-400/80 mt-1">When creating a new EMI, it starts as <b>Upcoming</b> (or <b>Overdue</b> if the date has passed). After creation, use the <b>Record Payment</b> button to pay it.</p>
                        </div>
                    </div>
                </div>

                <div class="pt-5 border-t border-zinc-200/50 dark:border-zinc-800/50 flex justify-end gap-3">
                    <x-button type="reset" variant="outline">Clear</x-button>
                    <x-button type="submit" variant="primary" icon="save">Save EMI Schedule</x-button>
                </div>
            </form>
        </div>
    </x-card>
</div>

@push('scripts')
<script>
    const customers = @json($customers ?? []);
    const dealers = @json($dealers ?? []);
    const vendors = @json($vendors ?? []);
    
    function toggleEntitySelect() {
        const type = document.getElementById('emi_type').value;
        const entityDiv = document.getElementById('entity_div');
        const entityLabel = document.getElementById('entity_label');
        const entitySelect = document.getElementById('entity_id');
        const loanNameDiv = document.getElementById('loan_name_div');
        const loanNameInput = document.getElementById('loan_name');
        
        entitySelect.innerHTML = '<option value="">-- Select --</option>';
        
        if (type === 'Bank Loan') {
            entityDiv.classList.add('hidden');
            entitySelect.required = false;
            
            loanNameDiv.classList.remove('hidden');
            loanNameInput.required = true;
        } else {
            entityDiv.classList.remove('hidden');
            entitySelect.required = true;
            
            loanNameDiv.classList.add('hidden');
            loanNameInput.required = false;
            
            let list = [];
            if (type === 'Customer') {
                list = customers;
            } else if (type === 'Dealer') {
                list = dealers;
            } else if (type === 'Vendor') {
                list = vendors;
            }
            
            entityLabel.innerHTML = 'Select ' + type + ' <span class="text-rose-500">*</span>';
            
            list.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.text = type === 'Customer' ? item.name : item.firm_name;
                entitySelect.appendChild(option);
            });
        }
    }
    
    document.addEventListener('DOMContentLoaded', () => {
        toggleEntitySelect();

        const form = document.querySelector('form[action="{{ route('expenses.emis.store') }}"]');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                return;
            }

            e.preventDefault();

            Swal.fire({
                title: 'Save EMI Schedule?',
                text: 'Are you sure you want to setup this new EMI installment schedule?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Save',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                background: document.documentElement.classList.contains('dark') ? '#18181b' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#f4f4f5' : '#18181b',
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Saving EMI...',
                        text: 'Please wait while the EMI schedule is being created.',
                        icon: 'info',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        background: document.documentElement.classList.contains('dark') ? '#18181b' : '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#f4f4f5' : '#18181b',
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    form.submit();
                } else {
                    if (typeof window.resetSubmitButtons === 'function') {
                        window.resetSubmitButtons(form);
                    }
                }
            });
        });
    });
</script>
@endpush
@endsection
