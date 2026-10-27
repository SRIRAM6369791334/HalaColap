@extends('layouts.app')
@section('title', 'Customer Ranking')

@section('content')
<div class="mb-2">
    <a href="{{ route('reports.index') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 uppercase tracking-wider inline-block">← Back to Analytics</a>
</div>
<x-page-header 
    title="Customer Rankings" 
    subtitle="Top customers by outstanding balance and transaction volume" />

<x-card class="!p-0 overflow-hidden">
    <x-data-table>
        <x-slot name="head">
            <tr>
                <th class="w-16">Rank</th>
                <th>Customer Name</th>
                <th>Type</th>
                <th>Route</th>
                <th class="text-right">Outstanding</th>
                <th class="text-center w-32">Score</th>
            </tr>
        </x-slot>
        @forelse($customers as $index => $customer)
            @php $rank = (($customers->currentPage()-1) * $customers->perPage()) + $index + 1; @endphp
            <tr>
                <td>
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-[10px] font-black {{ $rank <= 3 ? 'bg-amber-100 text-amber-700' : 'bg-sky-50 text-zinc-500' }}">
                        {{ $rank }}
                    </span>
                </td>
                <td class="font-bold text-zinc-950">{{ $customer->name }}</td>
                <td>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-tight {{ $customer->type == 'Wholesale' ? 'text-indigo-600 bg-indigo-50' : 'text-zinc-600 bg-zinc-50' }}">
                        {{ $customer->type }}
                    </span>
                </td>
                <td class="text-zinc-500 text-xs">{{ $customer->route ?: 'General' }}</td>
                <td class="text-right font-black text-emerald-900"><x-currency :amount="$customer->balance" /></td>
                <td class="text-center">
                    @php $score = max(0, 100 - ($customer->balance / 1000)); @endphp
                    <div class="w-full bg-zinc-100 h-1.5 rounded-full overflow-hidden min-w-[60px]">
                        <div class="h-full bg-emerald-500" style="width: {{ $score }}%"></div>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-5 py-12 text-center text-zinc-400 italic">No customer data found.</td></tr>
        @endforelse
    </x-data-table>
    
    @if($customers->hasPages())
    <div class="px-5 py-4 border-t border-zinc-100">
        {{ $customers->links() }}
    </div>
    @endif
</x-card>
@endsection
