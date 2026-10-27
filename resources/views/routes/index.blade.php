@extends('layouts.app')

@section('title', 'Route Management')

@section('content')
<div class="relative min-h-screen">
    {{-- Decorative Orbs --}}
    <div class="absolute -top-24 -left-24 w-96 h-96 bg-emerald-400/10 blur-[100px] rounded-full pointer-events-none"></div>
    <div class="absolute top-1/2 -right-24 w-96 h-96 bg-sky-400/10 blur-[100px] rounded-full pointer-events-none"></div>

    <div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
        <div>
            <h1 class="text-3xl font-black text-zinc-950 tracking-tight">Neural Logistics</h1>
            <p class="text-zinc-500 font-medium">Real-time route monitoring and fleet orchestration</p>
        </div>
        <div class="flex items-center gap-3">
            @can('create routes')
            <button onclick="openModal('vehicleModal')" 
                    class="group relative inline-flex items-center justify-center gap-3 overflow-hidden rounded-xl bg-white border border-zinc-200 px-6 py-4 text-sm font-black text-zinc-950 shadow-sm transition-all hover:border-emerald-200 active:scale-95">
                <span class="material-symbols-rounded text-xl text-emerald-600">local_shipping</span>
                Add Vehicle
            </button>
            <button onclick="openModal('routeModal')" 
                    class="group relative inline-flex items-center justify-center gap-3 overflow-hidden rounded-xl bg-zinc-950 px-6 py-4 text-sm font-black text-white shadow-2xl transition-all hover:scale-[1.02] active:scale-95">
                <div class="absolute inset-0 bg-gradient-to-r from-emerald-600 to-sky-500 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <span class="relative z-10 flex items-center gap-2">
                    <span class="material-symbols-rounded text-xl">add_road</span>
                    New Route
                </span>
            </button>
            @endcan
        </div>
    </div>

    {{-- Bento Grid Dashboard --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-8 relative z-10">
        
        {{-- Heartbeat Visualization --}}
        <div class="lg:col-span-4 bg-zinc-950 rounded-[2.5rem] p-8 shadow-2xl shadow-zinc-900/20 text-white flex flex-col items-center justify-center text-center relative overflow-hidden group">
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 to-transparent pointer-events-none"></div>
            <div class="w-24 h-24 bg-emerald-500/10 rounded-full flex items-center justify-center mb-6 relative">
                <div class="absolute inset-0 bg-emerald-500/20 rounded-full animate-ping"></div>
                <span class="material-symbols-rounded text-4xl text-emerald-400 group-hover:scale-125 transition-transform">hub</span>
            </div>
            <h3 class="text-2xl font-black mb-2">Fleet Connectivity</h3>
            <p class="text-[10px] text-emerald-400/70 font-black uppercase tracking-[0.3em]">System Online · {{ count($vehicles) }} Units</p>
            
            <div class="mt-8 flex gap-8">
                <div class="text-center">
                    <p class="text-3xl font-black text-white tabular-nums">{{ $vehicles->count() }}</p>
                    <p class="text-[9px] text-zinc-500 font-black uppercase tracking-widest mt-1">Vehicles</p>
                </div>
                <div class="w-px h-10 bg-white/10"></div>
                <div class="text-center">
                    <p class="text-3xl font-black text-white tabular-nums">{{ $drivers->count() }}</p>
                    <p class="text-[9px] text-zinc-500 font-black uppercase tracking-widest mt-1">Drivers</p>
                </div>
            </div>
        </div>

        {{-- Active Routes Monitor --}}
        <div class="lg:col-span-8 bg-white/60 backdrop-blur-xl rounded-[2.5rem] border border-white/40 shadow-xl shadow-zinc-200/40 overflow-hidden">
            <div class="p-8 border-b border-zinc-100 bg-gradient-to-r from-emerald-50/50 to-sky-50/50 flex items-center justify-between">
                <h3 class="font-black text-zinc-950 flex items-center gap-2 uppercase tracking-widest text-xs">
                    <span class="material-symbols-rounded text-emerald-600">route</span>
                    Active Network
                </h3>
                <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-[9px] font-black uppercase tracking-widest animate-pulse">Live Monitor</span>
            </div>
            
            <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($routes as $route)
                <div class="group flex items-center gap-5 p-5 rounded-3xl bg-white/40 border border-zinc-100 hover:border-emerald-200 hover:bg-emerald-50/30 transition-all">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-50 to-sky-50 flex items-center justify-center text-xl shrink-0 group-hover:from-emerald-600 group-hover:to-sky-500 group-hover:text-white transition-all shadow-sm">
                        <span class="material-symbols-rounded">map</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-black text-zinc-950 tracking-tight truncate">{{ $route->route_name }}</p>
                        <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mt-1">
                            {{ $route->vehicle->vehicle_number ?? 'NO_VEH' }} · {{ $route->driver->driver_name ?? 'NO_DRV' }}
                        </p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-xs font-black text-zinc-950">{{ ($route->customers_count ?? 0) + ($route->dealers_count ?? 0) }} Drops</p>
                        <div class="flex gap-1 justify-end mt-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.4)]"></span>
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.4)]"></span>
                            <span class="w-1.5 h-1.5 rounded-full bg-zinc-200"></span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full py-12 text-center">
                    <div class="w-16 h-16 bg-zinc-50 rounded-2xl flex items-center justify-center text-zinc-200 mx-auto mb-4">
                        <span class="material-symbols-rounded text-3xl">route_off</span>
                    </div>
                    <p class="text-[10px] font-black text-zinc-400 uppercase tracking-[0.2em]">No active logistics pipelines</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Vehicle Fleet --}}
        <div class="lg:col-span-6 bg-white/60 backdrop-blur-xl rounded-[2.5rem] border border-white/40 shadow-xl shadow-zinc-200/40 p-8">
            <h3 class="font-black text-zinc-950 flex items-center gap-2 uppercase tracking-widest text-xs mb-8">
                <span class="material-symbols-rounded text-sky-600">local_shipping</span>
                Fleet Status
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($vehicles as $vehicle)
                <div class="p-5 border border-zinc-100 rounded-[2rem] bg-white/40 relative group hover:border-sky-200 transition-all">
                    <div class="absolute top-4 right-4 w-2 h-2 rounded-full bg-emerald-500 animate-pulse shadow-[0_0_8px_rgba(16,185,129,0.6)]"></div>
                    <p class="text-[9px] text-zinc-400 uppercase font-black tracking-widest mb-1">{{ $vehicle->vehicle_type }}</p>
                    <p class="text-sm font-black text-zinc-950 tracking-tight">{{ $vehicle->vehicle_number }}</p>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Personnel --}}
        <div class="lg:col-span-6 bg-white/60 backdrop-blur-xl rounded-[2.5rem] border border-white/40 shadow-xl shadow-zinc-200/40 p-8">
            <h3 class="font-black text-zinc-950 flex items-center gap-2 uppercase tracking-widest text-xs mb-8">
                <span class="material-symbols-rounded text-violet-600">badge</span>
                Personnel
            </h3>
            <div class="space-y-4">
                @foreach($drivers as $driver)
                <div class="flex items-center gap-4 p-4 rounded-3xl bg-white/40 border border-zinc-100 hover:border-violet-200 transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-50 to-sky-50 flex items-center justify-center text-xs font-black text-violet-600 group-hover:scale-110 transition-transform shadow-sm">
                        {{ substr($driver->driver_name, 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-black text-zinc-950 tracking-tight">{{ $driver->driver_name }}</p>
                        <p class="text-[10px] text-zinc-400 font-bold tracking-widest">{{ $driver->phone }}</p>
                    </div>
                    <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-[9px] font-black uppercase tracking-widest">
                        Active
                    </span>
                </div>
                @endforeach
            </div>
        </div>

    </div>
</div>
@endsection
