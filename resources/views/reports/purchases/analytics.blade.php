@extends('layouts.app')
@section('title', 'Purchase Analytics')

@section('content')
<div class="mb-2">
    <a href="{{ route('reports.index') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 uppercase tracking-wider inline-block">← Back to Analytics</a>
</div>
<x-page-header 
    title="Purchase Analytics" 
    subtitle="Inventory procurement and cost distribution breakdown" />

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <x-card title="Inward Goods distribution">
            <div class="space-y-8">
                @forelse($analytics as $row)
                    @php 
                        $total = $analytics->sum('total');
                        $percent = $total > 0 ? ($row->total / $total * 100) : 0;
                        $colors = ['Feed' => 'bg-amber-500', 'Chicks' => 'bg-emerald-500', 'Medicines' => 'bg-indigo-500', 'Accessories' => 'bg-zinc-500'];
                    @endphp
                    <div class="space-y-2">
                        <div class="flex justify-between items-end">
                            <div>
                                <h4 class="text-sm font-black text-zinc-950">{{ $row->item }}</h4>
                                <p class="text-[10px] text-zinc-500 uppercase font-bold tracking-tight">Total Qty: {{ number_format($row->qty, 0) }} units</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-zinc-950"><x-currency :amount="$row->total" /></p>
                                <p class="text-[10px] text-zinc-400 font-bold">{{ number_format($percent, 1) }}% of Expense</p>
                            </div>
                        </div>
                        <div class="w-full bg-sky-50 h-2.5 rounded-full overflow-hidden">
                            <div class="h-full {{ $colors[$row->item] ?? 'bg-blue-500' }}" style="width: {{ $percent }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-zinc-400 italic">No purchase data available for analysis.</p>
                @endforelse
            </div>
        </x-card>
    </div>

    <div class="space-y-6">
        <div class="bg-primary rounded-2xl p-6 text-white shadow-md">
            <h3 class="text-xs font-bold uppercase tracking-widest text-indigo-200 mb-6">Vendor Dynamics</h3>
            <div class="space-y-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center text-xl"><i class="ph ph-buildings"></i></div>
                    <div>
                        <p class="text-[10px] font-bold uppercase opacity-60">Top Vendor</p>
                        <p class="text-sm font-bold">Reliable Feeds Corp</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center text-xl"><i class="ph ph-hourglass"></i></div>
                    <div>
                        <p class="text-[10px] font-bold uppercase opacity-60">Procurement Cycle</p>
                        <p class="text-sm font-bold">Bi-Weekly</p>
                    </div>
                </div>
            </div>
            <p class="mt-8 text-[10px] text-indigo-300 italic">Analytics automatically updated every 24 hours based on PO entries.</p>
        </div>
    </div>
</div>
@endsection
