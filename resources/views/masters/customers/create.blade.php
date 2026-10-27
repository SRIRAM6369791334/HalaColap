@extends('layouts.app')
@section('title', 'Register Customer')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="mb-4">
        <a href="{{ route('masters.customers.index') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 flex items-center gap-1 transition-colors">
            <span class="material-symbols-rounded text-[20px]">arrow_back</span>
            Back to Customers
        </a>
    </div>

    <x-page-header 
        title="Register New Customer" 
        subtitle="Enter the details below to add a new customer to ."
    />

    <x-card>
        <form action="{{ route('masters.customers.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-form.input 
                    name="name" 
                    label="Full Name" 
                    icon="person" 
                    placeholder="e.g. John Doe" 
                    required 
                />

                <x-form.input 
                    type="tel"
                    name="phone" 
                    label="Phone Number" 
                    icon="call" 
                    placeholder="e.g. 9876543210" 
                    maxlength="10"
                    pattern="[6-9][0-9]{9}"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)"
                    title="Please enter a valid 10-digit mobile number starting with 6-9"
                    required 
                />

                <div class="md:col-span-2">
                    <x-form.textarea 
                        name="address" 
                        label="Store Address" 
                        placeholder="Street, Area, City..." 
                        rows="2"
                        required
                    />
                </div>

                <x-form.input 
                    name="gst_number" 
                    label="GST Number (Optional)" 
                    icon="badge" 
                    placeholder="22AAAAA0000A1Z5" 
                />

                @if($routes->isEmpty())
                    <x-form.input 
                        name="route" 
                        label="Route / Area" 
                        icon="alt_route" 
                        placeholder="Supply route..." 
                        required
                    />
                @else
                    <x-form.select 
                        name="route_id" 
                        label="Route / Area" 
                        :options="$routes->pluck('route_name', 'id')->toArray()" 
                        placeholder="Select Route"
                        required
                    />
                @endif

                <x-form.select 
                    name="type" 
                    label="Customer Type" 
                    :options="['Retail' => 'Retail', 'Wholesale' => 'Wholesale']" 
                    required
                />

                <x-form.input 
                    type="number" 
                    name="balance" 
                    label="Opening Balance (Rs)" 
                    icon="account_balance_wallet" 
                    value="0.00" 
                    step="0.01" 
                    required
                />
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                <x-button href="{{ route('masters.customers.index') }}" variant="ghost">Cancel</x-button>
                <x-button type="submit" variant="primary">Register Customer</x-button>
            </div>
        </form>
    </x-card>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form[action="{{ route('masters.customers.store') }}"]');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) {
            return;
        }

        e.preventDefault();

        Swal.fire({
            title: 'Register Customer?',
            text: 'Are you sure you want to register this new customer?',
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
                    title: 'Registering Customer...',
                    text: 'Please wait while the customer is being saved.',
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
