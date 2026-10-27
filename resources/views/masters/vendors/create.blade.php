@extends('layouts.app')
@section('title', 'Add Vendor')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="mb-4">
        <a href="{{ route('masters.vendors.index') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 flex items-center gap-1 transition-colors">
            <span class="material-symbols-rounded text-[20px]">arrow_back</span>
            Back to Directory
        </a>
    </div>

    <x-page-header 
        title="Register New Vendor" 
        subtitle="Onboard a new strategic partner or supply chain vendor"
    />

    <x-card>
        <form action="{{ route('masters.vendors.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <x-form.input 
                        name="firm_name" 
                        label="Firm Name" 
                        icon="store" 
                        placeholder="e.g. Apex Feed Suppliers" 
                        required 
                    />
                </div>

                <x-form.input 
                    name="contact_person" 
                    label="Contact Person" 
                    icon="person" 
                    placeholder="Manager Name" 
                    required
                />

                <x-form.input 
                    type="tel"
                    name="phone" 
                    label="Phone" 
                    icon="call" 
                    placeholder="e.g. 9876543210" 
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
                    placeholder="Optional GSTIN" 
                    class="uppercase"
                />

                <x-form.input 
                    name="location" 
                    label="Location / City" 
                    icon="location_on" 
                    placeholder="e.g. Salem, TN" 
                    required
                />

                <x-form.input 
                    name="route" 
                    label="Route" 
                    icon="alt_route" 
                    placeholder="e.g. Main Highway Route" 
                    required
                />

                <x-form.input 
                    type="number" 
                    step="0.01" 
                    min="0" 
                    name="pending_amount" 
                    label="Initial Opening Balance (Rs)" 
                    icon="currency_rupee" 
                    placeholder="0.00" 
                    :value="old('pending_amount', '0.00')"
                />

                <div class="md:col-span-2">
                    <x-form.textarea 
                        name="notes" 
                        label="Strategic Notes" 
                        placeholder="Vendor specifications, items supplied, pricing rules..." 
                        rows="3"
                        required
                    />
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                <x-button type="reset" variant="ghost">Reset Fields</x-button>
                <x-button type="submit" variant="primary" icon="check_circle">Register Vendor Profile</x-button>
            </div>
        </form>
    </x-card>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form[action="{{ route('masters.vendors.store') }}"]');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) {
            return;
        }

        e.preventDefault();

        Swal.fire({
            title: 'Register Vendor?',
            text: 'Are you sure you want to onboard this new vendor profile?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, Register',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            background: document.documentElement.classList.contains('dark') ? '#18181b' : '#ffffff',
            color: document.documentElement.classList.contains('dark') ? '#f4f4f5' : '#18181b',
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Registering Vendor...',
                    text: 'Please wait while the vendor profile is being saved.',
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
