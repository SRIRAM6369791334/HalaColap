@extends('layouts.app')
@section('title', 'Edit Dealer')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="mb-4">
        <a href="{{ route('masters.dealers.index') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 flex items-center gap-1 transition-colors">
            <span class="material-symbols-rounded text-[20px]">arrow_back</span>
            Back to Directory
        </a>
    </div>

    <x-page-header 
        title="Edit Dealer" 
        subtitle="Modify existing dealer credentials for {{ $dealer->firm_name }}"
    />

    <x-card>
        <form action="{{ route('masters.dealers.update', $dealer) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <x-form.input 
                        name="firm_name" 
                        label="Firm Name" 
                        icon="store" 
                        :value="$dealer->firm_name" 
                        required 
                    />
                </div>

                <x-form.input 
                    name="contact_person" 
                    label="Contact Person" 
                    icon="person" 
                    :value="$dealer->contact_person" 
                    required
                />

                <x-form.input 
                    type="tel"
                    name="phone" 
                    label="Phone Number" 
                    icon="call" 
                    :value="$dealer->phone" 
                    maxlength="10"
                    pattern="[6-9][0-9]{9}"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)"
                    title="Please enter a valid 10-digit mobile number starting with 6-9"
                    required 
                />

                <x-form.input 
                    name="gst_number" 
                    label="GSTIN" 
                    icon="badge" 
                    :value="$dealer->gst_number" 
                    class="uppercase"
                />

                <x-form.input 
                    name="location" 
                    label="Location" 
                    icon="location_on" 
                    :value="$dealer->location" 
                    required
                />

                <x-form.input 
                    name="route" 
                    label="Route" 
                    icon="alt_route" 
                    :value="$dealer->route" 
                    required
                />

                <x-form.input 
                    type="number" 
                    name="sort_order" 
                    label="Delivery Sequence / Order #" 
                    icon="format_list_numbered" 
                    :value="$dealer->sort_order" 
                    placeholder="e.g. 1, 2, 3... (Optional)" 
                    min="1" 
                    step="1" 
                />

                <x-form.input 
                    type="number" 
                    name="pending_amount" 
                    label="Initial Opening Balance (Rs)" 
                    icon="account_balance_wallet" 
                    :value="$dealer->pending_amount" 
                    step="0.01" 
                    required
                />
            </div>

            {{-- Live Outstanding Info Card --}}
            <div class="p-4 bg-amber-50 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-800/80 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-2xs">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300 flex items-center justify-center shrink-0">
                        <span class="material-symbols-rounded text-xl">account_balance_wallet</span>
                    </div>
                    <div>
                        <span class="text-xs font-black text-amber-800 dark:text-amber-300 uppercase tracking-wider block">Current Total Outstanding Balance</span>
                        <span class="text-xs font-medium text-amber-600 dark:text-amber-400">Initial Opening Balance (<span id="opening-balance-text">Rs {{ number_format($dealer->pending_amount, 2) }}</span>) + Unpaid Day-Load Sales (Rs {{ number_format($dealer->dayload_outstanding, 2) }})</span>
                    </div>
                </div>
                <span class="font-cabinet font-black text-2xl text-amber-700 dark:text-amber-300" id="live-total-outstanding">
                    <x-currency :amount="$dealer->displayed_outstanding" />
                </span>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                <x-button href="{{ route('masters.dealers.index') }}" variant="ghost">Cancel</x-button>
                <x-button type="submit" variant="primary" icon="save">Update Dealer</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const pendingInput = document.querySelector('input[name="pending_amount"]');
    const dayloadOutstanding = {{ (float) $dealer->dayload_outstanding }};
    const openingText = document.getElementById('opening-balance-text');
    const totalSpan = document.getElementById('live-total-outstanding');

    if (pendingInput) {
        pendingInput.addEventListener('input', function() {
            const val = parseFloat(this.value) || 0;
            const newTotal = val + dayloadOutstanding;

            if (openingText) {
                openingText.textContent = 'Rs ' + val.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
            if (totalSpan) {
                totalSpan.textContent = '₹' + newTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
        });
    }

    const form = document.querySelector('form[action="{{ route('masters.dealers.update', $dealer) }}"]');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) {
            return;
        }

        e.preventDefault();

        Swal.fire({
            title: 'Update Dealer?',
            text: 'Are you sure you want to update this dealer profile?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, Update',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            background: document.documentElement.classList.contains('dark') ? '#18181b' : '#ffffff',
            color: document.documentElement.classList.contains('dark') ? '#f4f4f5' : '#18181b',
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Updating Dealer...',
                    text: 'Please wait while the dealer details are being updated.',
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
