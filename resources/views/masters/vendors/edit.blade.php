@extends('layouts.app')
@section('title', 'Edit Vendor')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="mb-4">
        <a href="{{ route('masters.vendors.index') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 flex items-center gap-1 transition-colors">
            <span class="material-symbols-rounded text-[20px]">arrow_back</span>
            Back to Directory
        </a>
    </div>

    <x-page-header 
        title="Edit Vendor Profile" 
        subtitle="Update profile details and credentials for {{ $vendor->firm_name }}"
    />

    <x-card>
        <form action="{{ route('masters.vendors.update', $vendor) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <x-form.input 
                        name="firm_name" 
                        label="Firm Name" 
                        icon="store" 
                        :value="$vendor->firm_name" 
                        required 
                    />
                </div>

                <x-form.input 
                    name="contact_person" 
                    label="Contact Person" 
                    icon="person" 
                    :value="$vendor->contact_person" 
                    required
                />

                <x-form.input 
                    type="tel"
                    name="phone" 
                    label="Phone" 
                    icon="call" 
                    :value="$vendor->phone" 
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
                    :value="$vendor->gst_number" 
                    class="uppercase"
                />

                <x-form.input 
                    name="location" 
                    label="Location / City" 
                    icon="location_on" 
                    :value="$vendor->location" 
                    required
                />

                <x-form.input 
                    name="route" 
                    label="Route" 
                    icon="alt_route" 
                    :value="$vendor->route" 
                    required
                />

                <x-form.input 
                    type="number" 
                    step="0.01" 
                    min="0" 
                    name="pending_amount" 
                    label="Initial Opening Balance (Rs)" 
                    icon="currency_rupee" 
                    :value="old('pending_amount', $vendor->pending_amount ?? '0.00')"
                />

                <div class="md:col-span-2">
                    <x-form.textarea 
                        name="notes" 
                        label="Strategic Notes" 
                        :value="$vendor->notes" 
                        rows="3"
                        required
                    />
                </div>

                {{-- Live Total Vendor Payable Info Card --}}
                @php
                    $otherVendorLiabilities = (float) $vendor->outstanding_balance - (float) $vendor->pending_amount;
                @endphp
                <div class="p-4 bg-purple-50 dark:bg-purple-950/40 border border-purple-200/80 dark:border-purple-800/80 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-2xs md:col-span-2">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-900/60 text-purple-700 dark:text-purple-300 flex items-center justify-center shrink-0">
                            <span class="material-symbols-rounded text-xl">account_balance_wallet</span>
                        </div>
                        <div>
                            <span class="text-xs font-black text-purple-800 dark:text-purple-300 uppercase tracking-wider block">Current Total Vendor Payable Balance</span>
                            <span class="text-xs font-medium text-purple-600 dark:text-purple-400">Initial Opening Balance (<span id="vendor-opening-text">Rs {{ number_format($vendor->pending_amount, 2) }}</span>) + Live Unpaid Purchases & Day-Load Costs (Rs {{ number_format($otherVendorLiabilities, 2) }})</span>
                        </div>
                    </div>
                    <span class="font-cabinet font-black text-2xl text-purple-700 dark:text-purple-300" id="live-vendor-outstanding">
                        <x-currency :amount="$vendor->outstanding_balance" />
                    </span>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                <x-button href="{{ route('masters.vendors.index') }}" variant="ghost">Cancel</x-button>
                <x-button type="submit" variant="primary" icon="save">Save Changes</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const pendingInput = document.querySelector('input[name="pending_amount"]');
    const otherLiabilities = {{ (float) $otherVendorLiabilities }};
    const openingText = document.getElementById('vendor-opening-text');
    const totalSpan = document.getElementById('live-vendor-outstanding');

    if (pendingInput) {
        pendingInput.addEventListener('input', function() {
            const val = parseFloat(this.value) || 0;
            const newTotal = val + otherLiabilities;

            if (openingText) {
                openingText.textContent = 'Rs ' + val.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
            if (totalSpan) {
                totalSpan.textContent = '₹' + newTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
        });
    }

    const form = document.querySelector('form[action="{{ route('masters.vendors.update', $vendor) }}"]');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) {
            return;
        }

        e.preventDefault();

        Swal.fire({
            title: 'Update Vendor Profile?',
            text: 'Are you sure you want to update this vendor profile details?',
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
                    title: 'Updating Vendor Profile...',
                    text: 'Please wait while the vendor details are being updated.',
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
