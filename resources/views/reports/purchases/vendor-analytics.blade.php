@extends('layouts.app')
@section('title', 'Vendor Analytics')

@section('content')
<x-page-header 
    title="Vendor Analytics" 
    subtitle="Comparative spend analysis and order volume breakdown">
    <div class="flex items-center gap-3">
        <x-button variant="outline" onclick="window.print()" icon="ph-printer">Print Analysis</x-button>
    </div>
</x-page-header>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    {{-- Table Section --}}
    <x-card class="flex flex-col">
        <x-slot name="header">
            <h3 class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Top Vendors by Purchase Volume</h3>
        </x-slot>
        <x-data-table>
            <x-slot name="head">
                <tr>
                    <th>Vendor Firm</th>
                    <th class="text-center">Orders</th>
                    <th class="text-right">Total Spent</th>
                </tr>
            </x-slot>
            @forelse($vendorWise as $data)
            <tr>
                <td class="font-medium text-zinc-950">{{ $data->vendor->firm_name ?? 'Unknown' }}</td>
                <td class="text-center font-mono text-zinc-600">{{ $data->orders }}</td>
                <td class="text-right font-mono font-bold text-emerald-600"><x-currency :amount="$data->total" /></td>
            </tr>
            @empty
            <tr>
                <td colspan="3" class="px-6 py-12 text-center text-zinc-400 italic">No vendor data available.</td>
            </tr>
            @endforelse
        </x-data-table>
    </x-card>

    {{-- Chart Section --}}
    <x-card class="flex flex-col">
        <x-slot name="header">
            <h3 class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest text-center">Purchase Distribution (Rs)</h3>
        </x-slot>
        <div class="flex-grow relative min-h-[300px] mt-4">
            <canvas id="vendorChart"></canvas>
        </div>
    </x-card>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('vendorChart').getContext('2d');
        const labels = {!! json_encode($vendorWise->map(fn($v) => $v->vendor->firm_name ?? 'Unknown')) !!};
        const data = {!! json_encode($vendorWise->pluck('total')) !!};

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Spent (Rs)',
                    data: data,
                    backgroundColor: 'rgba(16, 185, 129, 0.6)',
                    borderColor: 'rgb(16, 185, 129)',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: 'rgba(0,0,0,0.03)'
                        },
                        ticks: {
                            font: { size: 10 },
                            callback: function(value) {
                                return 'Rs ' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    });
</script>
@endsection

