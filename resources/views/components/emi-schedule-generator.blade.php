@props(['totalAmountId' => 'display-total', 'totalAmountRawId' => ''])

<div x-data="emiGenerator()" x-modelable="localPaymentMode" x-model="paymentMode" x-show="localPaymentMode === 'Pay later(EMI)'" style="display: none;" class="col-span-1 md:col-span-2 space-y-4 mb-6 pt-4 border-t border-zinc-100 dark:border-zinc-800">
    <div class="flex items-center gap-2 mb-2">
        <span class="material-symbols-rounded text-indigo-600 dark:text-indigo-400">calendar_month</span>
        <h3 class="text-sm font-bold text-zinc-800 dark:text-white">EMI Schedule Configuration</h3>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">No. of Months</label>
            <x-form.input type="number" x-model.number="months" min="1" max="60" @input="generateSchedule" class="font-bold" />
        </div>
        <div>
            <label class="block text-xs font-bold text-zinc-500 uppercase mb-1">Start Date</label>
            <x-form.input type="date" x-model="startDate" @change="generateSchedule" class="font-bold" />
        </div>
        <div class="flex items-end">
            <x-button type="button" variant="outline" @click="generateSchedule" icon="refresh" class="w-full justify-center">
                Regenerate
            </x-button>
        </div>
    </div>

    <div x-show="schedules.length > 0" class="mt-4 border rounded-xl border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                    <th class="px-4 py-2 text-xs font-bold text-zinc-500 uppercase tracking-wider">Inst. #</th>
                    <th class="px-4 py-2 text-xs font-bold text-zinc-500 uppercase tracking-wider">Due Date</th>
                    <th class="px-4 py-2 text-xs font-bold text-zinc-500 uppercase tracking-wider">Amount (₹)</th>
                    <th class="px-4 py-2 text-xs font-bold text-zinc-500 uppercase tracking-wider text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(emi, index) in schedules" :key="index">
                    <tr class="border-t border-zinc-200 dark:border-zinc-700">
                        <td class="px-4 py-2 text-sm font-medium text-zinc-900 dark:text-zinc-100" x-text="index + 1"></td>
                        <td class="px-4 py-2">
                            <input type="date" :name="`emis[${index}][due_date]`" x-model="emi.date" required class="w-full px-2 py-1 text-sm rounded border border-zinc-200 dark:border-zinc-700 bg-transparent" />
                        </td>
                        <td class="px-4 py-2">
                            <input type="number" :name="`emis[${index}][amount]`" x-model.number="emi.amount" step="0.01" required @input="recalculateTotal" class="w-full px-2 py-1 text-sm rounded border border-zinc-200 dark:border-zinc-700 bg-transparent font-mono font-bold" />
                        </td>
                        <td class="px-4 py-2 text-right">
                            <button type="button" @click="removeRow(index)" class="text-rose-500 hover:text-rose-700">
                                <span class="material-symbols-rounded text-sm">close</span>
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
            <tfoot>
                <tr class="bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-200 dark:border-zinc-700">
                    <td colspan="2" class="px-4 py-2 text-right text-xs font-bold text-zinc-500 uppercase">Total EMI Sum:</td>
                    <td colspan="2" class="px-4 py-2 font-mono font-bold text-zinc-900 dark:text-white">
                        ₹<span x-text="totalEmiAmount.toFixed(2)"></span>
                        <span x-show="isTotalMismatch()" class="text-rose-500 text-xs ml-2 block">Matches required: ₹<span x-text="getRequiredTotal().toFixed(2)"></span></span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('emiGenerator', () => ({
        months: 3,
        startDate: new Date().toISOString().split('T')[0],
        schedules: (@json(old('emis', [])) || []).map(item => ({ date: item.due_date || item.date || '', amount: parseFloat(item.amount) || 0 })),
        totalEmiAmount: 0,
        localPaymentMode: '',

        getRequiredTotal() {
            let totalStr = '0';
            @if($totalAmountRawId)
                totalStr = document.getElementById('{{ $totalAmountRawId }}')?.value || '0';
            @else
                totalStr = document.getElementById('{{ $totalAmountId }}')?.textContent || '0';
                totalStr = totalStr.replace(/[^0-9.]/g, '');
            @endif
            return parseFloat(totalStr) || 0;
        },

        generateSchedule() {
            const requiredTotal = this.getRequiredTotal();
            if (requiredTotal <= 0 || this.months <= 0) return;

            const baseAmount = Math.floor((requiredTotal / this.months) * 100) / 100;
            let remainder = requiredTotal - (baseAmount * this.months);
            
            this.schedules = [];
            let currentDt = new Date(this.startDate);

            for(let i=0; i < this.months; i++) {
                let amount = baseAmount;
                if (i === this.months - 1) {
                    amount += remainder;
                }
                
                let isoDate = currentDt.toISOString().split('T')[0];
                this.schedules.push({
                    date: isoDate,
                    amount: parseFloat(amount.toFixed(2))
                });
                currentDt.setMonth(currentDt.getMonth() + 1);
            }
            this.recalculateTotal();
        },

        removeRow(index) {
            this.schedules.splice(index, 1);
            this.recalculateTotal();
        },

        recalculateTotal() {
            this.totalEmiAmount = this.schedules.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0);
        },

        isTotalMismatch() {
            return Math.abs(this.totalEmiAmount - this.getRequiredTotal()) > 0.05;
        },

        init() {
            @if($totalAmountRawId)
                const targetNode = document.getElementById('{{ $totalAmountRawId }}');
            @else
                const targetNode = document.getElementById('{{ $totalAmountId }}');
            @endif
            
            if (targetNode) {
                const observer = new MutationObserver(() => {
                    if (this.localPaymentMode === 'Pay later(EMI)') {
                        this.generateSchedule(); 
                    }
                });
                observer.observe(targetNode, { childList: true, characterData: true, subtree: true, attributes: true });
            }

            // Watch parent's paymentMode via localPaymentMode to generate schedule when selected
            this.$watch('localPaymentMode', (value) => {
                if (value === 'Pay later(EMI)') {
                    setTimeout(() => {
                        this.generateSchedule();
                    }, 50);
                }
            });

            // If initially Pay later(EMI) (e.g. redirect back on validation error)
            setTimeout(() => {
                this.recalculateTotal();
                if (this.localPaymentMode === 'Pay later(EMI)' && this.schedules.length === 0) {
                    this.generateSchedule();
                }
            }, 100);
        }
    }));
});
</script>
