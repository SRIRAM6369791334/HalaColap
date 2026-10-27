@extends('layouts.app')
@section('title', 'Payment Ledger - ' . $dealer->firm_name)

@section('content')
<div class="space-y-6">
    <div class="mb-4">
        <a href="{{ route('masters.dealers.index') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 flex items-center gap-1 transition-colors">
            <span class="material-symbols-rounded text-[20px]">arrow_back</span>
            Back to Directory
        </a>
    </div>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <x-avatar name="{{ $dealer->firm_name }}" size="lg" />
            <div>
                <h1 class="text-2xl font-bold font-cabinet text-zinc-900 dark:text-zinc-100 tracking-tight">{{ $dealer->firm_name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <x-badge color="blue">Supplier / Partner</x-badge>
                    <x-badge color="zinc">
                        <span class="material-symbols-rounded text-[14px] mr-1">alt_route</span>
                        {{ $dealer->route ?: 'General Area' }}
                    </x-badge>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <x-button href="{{ route('masters.dealers.edit', $dealer) }}" variant="secondary" icon="edit">Edit Profile</x-button>
            <form action="{{ route('masters.dealers.destroy', $dealer) }}" method="POST" onsubmit="return confirm('Delete {{ $dealer->firm_name }}? This will keep their transaction history intact.')">
                @csrf @method('DELETE')
                <x-button type="submit" variant="danger" icon="delete">Delete</x-button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-1 space-y-6">
            <div class="rounded-3xl p-6 bg-amber-500/40 dark:bg-amber-900/40 backdrop-blur-2xl text-amber-900 dark:text-amber-100 shadow-[0_8px_32px_rgba(245,158,11,0.15)] border border-amber-300/50 dark:border-amber-700/50 relative overflow-hidden transition-all duration-300 hover:shadow-[0_8px_32px_rgba(245,158,11,0.25)] hover:-translate-y-1">
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/20 dark:bg-amber-400/10 rounded-full blur-2xl"></div>
                <div class="absolute -left-10 -bottom-10 w-32 h-32 bg-amber-400/20 dark:bg-amber-600/20 rounded-full blur-2xl"></div>
                <div class="relative z-10 text-center">
                    <div class="text-xs font-bold uppercase tracking-widest text-amber-800/80 dark:text-amber-200 mb-2">Total Payable</div>
                    <div class="text-3xl font-extrabold tracking-tight font-jetbrains mb-2 text-amber-950 dark:text-white drop-shadow-sm">
                        Rs {{ number_format($dealer->displayed_outstanding, 2) }}
                    </div>
                    @if($dealer->dayload_outstanding > 0)
                        <div class="text-xs font-medium text-amber-700/70 dark:text-amber-300 mb-6">
                            Old: Rs {{ number_format($dealer->pending_amount, 0) }} + Day-Load: Rs {{ number_format($dealer->dayload_outstanding, 0) }}
                        </div>
                    @endif
                    <div class="flex flex-col gap-3">
                        <x-button href="{{ route('payments.dealers.create', ['dealer_id' => $dealer->id]) }}" variant="secondary" icon="payments" class="w-full justify-center !text-amber-700 !bg-white/80 hover:!bg-white !border-white backdrop-blur-md shadow-sm">
                            Record Payment
                        </x-button>
                        <x-button href="{{ route('masters.dealers.ledger-pdf', $dealer) }}" variant="secondary" icon="download" class="w-full justify-center !bg-amber-600/20 !text-amber-900 dark:!text-amber-100 !border-amber-400/30 hover:!bg-amber-600/30 backdrop-blur-md">
                            Download Ledger
                        </x-button>
                    </div>
                </div>
            </div>

            <x-card title="Firm Credentials" icon="contact_page">
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-rounded text-zinc-400">person</span>
                        <div>
                            <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Contact Person</div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $dealer->contact_person ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-rounded text-zinc-400">call</span>
                        <div>
                            <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Contact Phone</div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $dealer->phone }}</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-rounded text-zinc-400">location_on</span>
                        <div>
                            <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">Store Location</div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $dealer->location ?: 'Not provided' }}</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-rounded text-zinc-400">badge</span>
                        <div>
                            <div class="text-xs font-bold text-zinc-500 uppercase tracking-wider">GSTIN / Registration</div>
                            <div class="font-mono text-sm text-zinc-900 dark:text-zinc-100">{{ $dealer->gst_number ?: 'Unregistered' }}</div>
                        </div>
                    </div>
                </div>
            </x-card>
        </div>

        <div class="lg:col-span-2">
            <div id="cm-tabs-container" class="bg-white/30 dark:bg-zinc-900/40 backdrop-blur-2xl border border-white/60 dark:border-zinc-800/80 rounded-[2rem] overflow-hidden shadow-[0_8px_32px_rgba(31,38,135,0.07)] z-10 relative">
                <div class="flex flex-wrap p-2 m-4 bg-white/40 dark:bg-zinc-900/40 backdrop-blur-md rounded-2xl border border-white/50 dark:border-zinc-700/50 gap-2">
                    <a href="{{ route('masters.dealers.show', $dealer) }}" class="flex-1 text-center py-3 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-white/50 dark:hover:bg-zinc-800/50 rounded-xl transition-all duration-300">
                        Quick Overview
                    </a>
                    <a href="{{ route('masters.dealers.purchase-history', $dealer) }}" class="flex-1 text-center py-3 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-white/50 dark:hover:bg-zinc-800/50 rounded-xl transition-all duration-300">
                        Purchase Orders
                    </a>
                    <a href="{{ route('payments.dealers.ledger', $dealer) }}" class="flex-1 text-center py-3 text-sm font-bold text-emerald-700 dark:text-emerald-400 bg-white/70 dark:bg-zinc-800/80 border border-black dark:border-white shadow-sm rounded-xl transition-all duration-300">
                        Payment Ledger
                    </a>
                    <a href="{{ route('masters.dealers.outstanding-report', $dealer) }}" class="flex-1 text-center py-3 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-white/50 dark:hover:bg-zinc-800/50 rounded-xl transition-all duration-300">
                        Outstanding Report
                    </a>
                </div>

                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider">Transaction History</h4>
                        <button onclick="window.print()" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 flex items-center gap-1">
                            <span class="material-symbols-rounded text-[16px]">print</span> Print
                        </button>
                    </div>

                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="p-3 rounded-xl border border-blue-200 bg-blue-50 dark:border-blue-900/50 dark:bg-blue-900/20">
                            <div class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Total Loads (Kg)</div>
                            <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100 font-jetbrains">{{ number_format($totalDebit, 1) }}</div>
                        </div>
                        <div class="p-3 rounded-xl border border-emerald-200 bg-emerald-50 dark:border-emerald-900/50 dark:bg-emerald-900/20">
                            <div class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Total Paid (Rs)</div>
                            <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400 font-jetbrains">{{ number_format($totalCredit, 2) }}</div>
                        </div>
                        <div class="p-3 rounded-xl border border-rose-200 bg-rose-50 dark:border-rose-900/50 dark:bg-rose-900/20">
                            <div class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Outstanding (Rs)</div>
                            <div class="text-lg font-bold text-rose-600 dark:text-rose-400 font-jetbrains">{{ number_format($dealer->displayed_outstanding, 2) }}</div>
                        </div>
                    </div>

                    <x-data-table :headers="['Date', 'Transaction Details', 'Ref #', ['label' => 'Debit (Load Kg)', 'align' => 'right'], ['label' => 'Credit (Payment Rs)', 'align' => 'right']]">
                        @php $expandedGroups = []; @endphp
                        @forelse($paginated as $row)
                            @if($row['group_id'])
                                <tr x-data="{ open: false }" class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 cursor-pointer" x-on:click="open = !open">
                                    <td class="px-6 py-4 font-bold text-sm">{{ $row['date']->format('d M Y') }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                                <span class="material-symbols-rounded text-[14px]">payments</span>
                                            </div>
                                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row['desc'] }}</div>
                                            <span class="material-symbols-rounded text-sm text-zinc-400 transition-transform" :class="{ 'rotate-180': open }">expand_more</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center font-mono text-xs text-zinc-500">{{ $row['ref'] }}</td>
                                    <td class="px-6 py-4 text-right font-jetbrains text-sm">—</td>
                                    <td class="px-6 py-4 text-right font-jetbrains text-sm">
                                        <span class="font-bold text-emerald-600 dark:text-emerald-400">Rs {{ number_format($row['credit'], 2) }}</span>
                                    </td>
                                </tr>
                                <template x-if="open">
                                    <tr>
                                        <td colspan="5" class="px-6 py-2 bg-zinc-50/70 dark:bg-zinc-800/30">
                                            <div class="space-y-1">
                                                @foreach($row['sub_items'] as $sub)
                                                    <div class="flex items-center justify-between text-sm px-4 py-1.5 rounded-lg {{ $sub['entry_label'] === 'Unallocated / Advance' ? 'bg-amber-50 dark:bg-amber-900/10 text-amber-700 dark:text-amber-300' : 'text-zinc-600 dark:text-zinc-400' }}">
                                                        <span class="text-xs">{{ $sub['entry_label'] }}</span>
                                                        <span class="font-jetbrains font-bold">Rs {{ number_format($sub['amount'], 2) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            @else
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50">
                                    <td class="px-6 py-4 font-bold text-sm">{{ $row['date']->format('d M Y') }}</td>
                                    <td class="px-6 py-4">
                                        @if($row['type'] === 'load')
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 rounded bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                                                    <span class="material-symbols-rounded text-[14px]">local_shipping</span>
                                                </div>
                                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row['desc'] }}</div>
                                            </div>
                                        @else
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 rounded bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                                    <span class="material-symbols-rounded text-[14px]">payments</span>
                                                </div>
                                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row['desc'] }}</div>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center font-mono text-xs text-zinc-500">{{ $row['ref'] }}</td>
                                    <td class="px-6 py-4 text-right font-jetbrains text-sm">
                                        @if($row['debit'] > 0)
                                            <span class="font-bold text-blue-600 dark:text-blue-400">{{ number_format($row['debit'], 1) }} kg</span>
                                        @else
                                            <span class="text-zinc-300 dark:text-zinc-600">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right font-jetbrains text-sm">
                                        @if($row['credit'] > 0)
                                            <span class="font-bold text-emerald-600 dark:text-emerald-400">Rs {{ number_format($row['credit'], 2) }}</span>
                                        @else
                                            <span class="text-zinc-300 dark:text-zinc-600">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr><td colspan="5" class="text-center py-8 text-zinc-500">No transactions found.</td></tr>
                        @endforelse

                        <x-slot:pagination>
                            {{ $paginated->links() }}
                        </x-slot:pagination>
                    </x-data-table>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
@media print {
    body { background: white !important; }
    nav, aside, header, .no-print, .pagination, #cm-tabs-container .flex-wrap { display: none !important; }
    .shadow-sm, .border-zinc-200, .shadow-\[0_8px_32px_rgba\(31\,38\,135\,0\.07\)\] { border: none !important; box-shadow: none !important; }
}
</style>
@endsection
