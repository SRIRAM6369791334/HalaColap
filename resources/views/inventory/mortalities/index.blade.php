@extends('layouts.app')
@section('title', 'Mortality Tracking')

@section('content')
<div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
    <div>
        <h1 class="text-3xl font-black text-zinc-950 tracking-tight">Mortality Tracking</h1>
        <p class="text-zinc-500 font-medium">Monitor flock health and attrition across active batches</p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('inventory.mortalities.create') }}" 
           class="px-6 py-4 bg-gradient-to-r from-rose-600 to-amber-500 text-white text-sm font-black rounded-xl hover:from-rose-700 hover:to-amber-600 transition-all duration-200 shadow-md shadow-red-600/20 active:scale-95">
            + Record Loss 
        </a>
    </div>
</div>

{{-- Operational Health Stats --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-gradient-to-br from-white via-emerald-50/30 to-sky-50/30 p-6 rounded-2xl border border-zinc-200 shadow-md shadow-zinc-200/60 flex items-center gap-6 group hover:border-red-200 transition-all">
        <div class="w-14 h-14 bg-red-50 text-red-600 rounded-2xl flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
            <span class="material-symbols-rounded">trending_down</span>
        </div>
        <div>
            <h3 class="text-[10px] font-black text-zinc-400 uppercase tracking-widest mb-1">Total Deaths</h3>
            <p class="text-2xl font-black text-zinc-950">{{ number_format($mortalities->sum('count')) }}</p>
        </div>
    </div>
    <div class="bg-gradient-to-br from-white via-emerald-50/30 to-sky-50/30 p-6 rounded-2xl border border-zinc-200 shadow-md shadow-zinc-200/60 flex items-center gap-6 group hover:border-emerald-200 transition-all">
        <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
            <span class="material-symbols-rounded">favorite</span>
        </div>
        <div>
            <h3 class="text-[10px] font-black text-zinc-400 uppercase tracking-widest mb-1">Survival Pulse</h3>
            <p class="text-2xl font-black text-zinc-950">Tracking Active</p>
        </div>
    </div>
    <div class="bg-gradient-to-br from-white via-emerald-50/30 to-sky-50/30 border border-zinc-200 p-6 rounded-2xl shadow-md shadow-zinc-200/60 text-white flex items-center gap-6 col-span-2">
        <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center text-2xl">
            <span class="material-symbols-rounded">flock</span>
        </div>
        <div>
            <h3 class="text-[10px] font-black text-emerald-200 uppercase tracking-widest mb-1">Real-time Flock Count</h3>
            <p class="text-2xl font-black text-white italic">"Ensuring every bird is accounted for in your ledger."</p>
        </div>
    </div>
</div>

{{-- Mortality Logs --}}
<div class="bg-gradient-to-br from-white via-emerald-50/40 to-sky-50/40 rounded-2xl border border-zinc-200 shadow-lg overflow-hidden mb-12">
    <div class="p-8 border-b border-zinc-100 flex flex-col md:flex-row md:items-center justify-between gap-6 bg-gradient-to-r from-emerald-50/80 to-sky-50/80">
        <h3 class="text-xs font-black text-zinc-400 uppercase tracking-widest">Historical Attrition Log</h3>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="bg-gradient-to-r from-emerald-50/80 to-sky-50/80 text-zinc-400 font-black uppercase text-[10px] tracking-widest border-b border-zinc-200">
                    <th class="px-8 py-5">Event Date</th>
                    <th class="px-8 py-5">Source Batch</th>
                    <th class="px-8 py-5 text-center">Loss Count</th>
                    <th class="px-8 py-5 text-center">Remaining</th>
                    <th class="px-8 py-5">Reason & Observation</th>
                    <th class="px-8 py-5 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse($mortalities as $m)
                    <tr class="hover:bg-red-50/20 transition-all group">
                        <td class="px-8 py-5">
                            <span class="font-black text-zinc-950 tracking-tighter">{{ $m->date->format('M d, Y') }}</span>
                        </td>
                        <td class="px-8 py-5">
                            <div class="flex flex-col">
                                <span class="font-black text-red-700 tracking-tight">{{ $m->batch->batch_code }}</span>
                                <span class="text-[10px] text-zinc-400 font-bold uppercase tracking-widest">{{ $m->batch->breed }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-5 text-center">
                            <span class="px-4 py-2 bg-red-100 text-red-700 text-sm font-black rounded-xl border border-red-200">
                                -{{ number_format($m->count) }}
                            </span>
                        </td>
                        <td class="px-8 py-5 text-center">
                            <span class="text-sm font-black text-zinc-600">{{ number_format($m->batch->current_count) }} Birds</span>
                        </td>
                        <td class="px-8 py-5">
                            <div class="flex flex-col">
                                <span class="font-bold text-zinc-800">{{ $m->reason ?: 'General Attrition' }}</span>
                                @if($m->remarks)
                                    <span class="text-[10px] text-zinc-400 italic font-medium truncate max-w-[200px]">{{ $m->remarks }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-8 py-5 text-center">
                            <form action="{{ route('inventory.mortalities.destroy', $m->id) }}" method="POST" onsubmit="return confirm('Restore these bird counts and delete record?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="w-10 h-10 flex items-center justify-center bg-gradient-to-br from-white via-emerald-50/30 to-sky-50/30 border border-zinc-200 rounded-2xl text-zinc-400 hover:text-red-600 hover:border-red-200 hover:shadow-lg transition-all duration-200 active:scale-95">
                                    <span class="material-symbols-rounded text-lg">undo</span>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-8 py-24 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-20 h-20 bg-emerald-50 rounded-full flex items-center justify-center text-4xl mb-6 shadow-inner">
                                    <span class="material-symbols-rounded text-emerald-600">shield_heart</span>
                                </div>
                                <h3 class="text-xl font-black text-zinc-950 tracking-tight uppercase tracking-widest">No Losses Recorded</h3>
                                <p class="text-zinc-400 font-medium mt-1">Excellent flock health status. No attrition logs found.</p>
                                <a href="{{ route('inventory.mortalities.create') }}" class="mt-8 px-8 py-4 bg-gradient-to-br from-white via-emerald-50/30 to-sky-50/30 border border-zinc-200 text-zinc-700 text-xs font-black rounded-2xl hover:bg-emerald-50 transition-all duration-200 uppercase tracking-widest">Record First Entry</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($mortalities->hasPages())
        <div class="p-8 border-t border-zinc-100 bg-gradient-to-r from-emerald-50/70 to-sky-50/70">
            {{ $mortalities->links() }}
        </div>
    @endif
</div>
@endsection
