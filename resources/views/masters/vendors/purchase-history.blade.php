@extends('layouts.app')
@section('title', 'Purchase History - ' . $vendor->firm_name)

@section('content')
<div class="space-y-6">
    <div class="mb-4">
        <a href="{{ route('masters.vendors.index') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 flex items-center gap-1 transition-colors">
            <span class="material-symbols-rounded text-[20px]">arrow_back</span>
            Back to directory
        </a>
    </div>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <x-avatar name="{{ $vendor->firm_name }}" size="lg" />
            <div>
                <h1 class="text-2xl font-bold font-cabinet text-zinc-900 dark:text-zinc-100 tracking-tight">{{ $vendor->firm_name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <x-badge color="teal">Supplier Partner</x-badge>
                    <x-badge color="zinc">
                        <span class="material-symbols-rounded text-[14px] mr-1">alt_route</span>
                        {{ $vendor->route ?: 'General Sector' }}
                    </x-badge>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <x-button href="{{ route('masters.vendors.edit', $vendor) }}" variant="secondary" icon="edit">Edit Profile</x-button>
            <form action="{{ route('masters.vendors.destroy', $vendor) }}" method="POST" onsubmit="return confirm('Delete {{ $vendor->firm_name }}? This will keep their transaction history intact.')">
                @csrf @method('DELETE')
                <x-button type="submit" variant="danger" icon="delete">Delete</x-button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-1 space-y-6">
            <x-card title="Profile Credentials" icon="contact_page">
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-rounded text-zinc-400">person</span>
                        <div>
                            <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Contact Person</div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $vendor->contact_person ?: 'Not specified' }}</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-rounded text-zinc-400">call</span>
                        <div>
                            <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Contact Phone</div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $vendor->phone }}</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-rounded text-zinc-400">location_on</span>
                        <div>
                            <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Firm Location</div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $vendor->location ?: 'Not set' }}</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-rounded text-zinc-400">badge</span>
                        <div>
                            <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">GSTIN / Registration</div>
                            <div class="font-mono text-sm text-zinc-900 dark:text-zinc-100">{{ $vendor->gst_number ?: 'Unregistered' }}</div>
                        </div>
                    </div>
                </div>
            </x-card>

            @if($vendor->notes)
            <div class="p-5 rounded-xl border-l-4 border-l-teal-500 border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm">
                <h3 class="text-xs font-bold text-teal-700 dark:text-teal-400 uppercase tracking-wider flex items-center gap-2 mb-3">
                    <span class="material-symbols-rounded text-base">description</span>
                    Vendor Notes
                </h3>
                <div class="text-sm text-zinc-600 dark:text-zinc-400 whitespace-pre-line">{{ $vendor->notes }}</div>
            </div>
            @endif
        </div>

        <div class="lg:col-span-2">
            <div id="cm-tabs-container" class="bg-white/30 dark:bg-zinc-900/40 backdrop-blur-2xl border border-white/60 dark:border-zinc-800/80 rounded-[2rem] overflow-hidden shadow-[0_8px_32px_rgba(31,38,135,0.07)] z-10 relative">
                <div class="flex flex-wrap p-2 m-4 bg-white/40 dark:bg-zinc-900/40 backdrop-blur-md rounded-2xl border border-white/50 dark:border-zinc-700/50 gap-2">
                    <a href="{{ route('masters.vendors.show', $vendor) }}" class="flex-1 text-center py-3 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-white/50 dark:hover:bg-zinc-800/50 rounded-xl transition-all duration-300">
                        Quick Look
                    </a>
                    <a href="{{ route('masters.vendors.purchase-history', $vendor) }}" class="flex-1 text-center py-3 text-sm font-bold text-teal-700 dark:text-teal-400 bg-white/70 dark:bg-zinc-800/80 shadow-sm rounded-xl transition-all duration-300">
                        Full Purchase History
                    </a>
                </div>

                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider">Day-Load History</h4>
                    </div>

                    <div class="grid grid-cols-4 gap-4 mb-6">
                        <div class="p-3 rounded-xl border border-blue-200 bg-blue-50 dark:border-blue-900/50 dark:bg-blue-900/20">
                            <div class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Total Boxes</div>
                            <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100 font-jetbrains">{{ number_format($totalBoxes) }}</div>
                        </div>
                        <div class="p-3 rounded-xl border border-emerald-200 bg-emerald-50 dark:border-emerald-900/50 dark:bg-emerald-900/20">
                            <div class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Bird Weight</div>
                            <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100 font-jetbrains">{{ number_format($totalBirdWeight, 1) }} kg</div>
                        </div>
                        <div class="p-3 rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-900/50 dark:bg-amber-900/20">
                            <div class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Farm Weight</div>
                            <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100 font-jetbrains">{{ number_format($totalFarmWeight, 1) }} kg</div>
                        </div>
                        <div class="p-3 rounded-xl border border-rose-200 bg-rose-50 dark:border-rose-900/50 dark:bg-rose-900/20">
                            <div class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Loss Weight</div>
                            <div class="text-lg font-bold text-rose-600 dark:text-rose-400 font-jetbrains">{{ number_format($totalLossWeight, 1) }} kg</div>
                        </div>
                    </div>

                    <x-data-table :headers="['Date', 'Dealer', ['label' => 'Boxes', 'align' => 'right'], ['label' => 'Bird Weight', 'align' => 'right'], ['label' => 'Farm Weight', 'align' => 'right'], ['label' => 'Loss', 'align' => 'right']]">
                        @forelse($dayLoadEntries as $entry)
                            <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50">
                                <td class="px-6 py-4 font-bold text-sm">{{ $entry->batch->billing_date->format('d M Y') }}</td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $entry->dealer->firm_name ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 text-right font-jetbrains text-sm">{{ $entry->no_of_boxes }}</td>
                                <td class="px-6 py-4 text-right font-jetbrains text-sm">{{ number_format($entry->bird_weight, 1) }} kg</td>
                                <td class="px-6 py-4 text-right font-jetbrains text-sm">{{ number_format($entry->farm_weight ?? 0, 1) }} kg</td>
                                <td class="px-6 py-4 text-right font-jetbrains text-sm">
                                    @if(($entry->loss_weight ?? 0) > 0)
                                        <span class="text-rose-600 dark:text-rose-400">{{ number_format($entry->loss_weight, 1) }} kg</span>
                                    @else
                                        <span class="text-emerald-600 dark:text-emerald-400">0 kg</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-8 text-zinc-500">No day-load entries found.</td></tr>
                        @endforelse
                        @if($dayLoadEntries->hasPages())
                            <x-slot:pagination>
                                {{ $dayLoadEntries->links() }}
                            </x-slot:pagination>
                        @endif
                    </x-data-table>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
