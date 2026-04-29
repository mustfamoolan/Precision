<script setup>
import { ref } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Badge from '@/Components/Badge.vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    summary: Object,
    cash_flow: Array,
    aging: Object,
    top_expenses: Array,
    ledger: Array,
    filters: Object,
});

const search = ref('');

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-AE', { style: 'currency', currency: 'AED' }).format(value);
};

const handleFilter = (filter) => {
    router.get('/reports', { filter }, { preserveState: true });
};
</script>

<template>
    <Head title="Financial Reports" />

    <div class="space-y-6 animate-in fade-in duration-500 pb-10">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-headline font-bold text-on-surface tracking-tight">Financial Reports</h1>
                <p class="text-sm text-outline font-label">{{ summary.period_label }}</p>
            </div>
            <div class="flex items-center gap-2">
                <div class="bg-surface-container-low p-1 rounded-xl flex gap-1">
                    <button @click="handleFilter('month')" :class="filters.filter === 'month' ? 'bg-primary text-on-primary' : 'text-outline'" class="px-3 py-1 rounded-lg text-xs font-bold transition-all">Month</button>
                    <button @click="handleFilter('week')" :class="filters.filter === 'week' ? 'bg-primary text-on-primary' : 'text-outline'" class="px-3 py-1 rounded-lg text-xs font-bold transition-all">Week</button>
                </div>
                <div class="flex gap-2">
                    <a href="/export/sales" class="bg-surface-container-low border border-outline-variant/20 p-2 rounded-lg hover:bg-surface-container-high transition-colors" title="Export Sales">
                        <span class="material-symbols-outlined text-[18px]">download</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- KPI Row -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-surface-container-lowest border border-outline-variant/20 p-5 rounded-2xl">
                <p class="text-[10px] font-bold text-outline uppercase tracking-widest mb-1">Total Sales</p>
                <h3 class="text-xl font-headline font-black text-on-surface">{{ formatCurrency(summary.total_sales) }}</h3>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant/20 p-5 rounded-2xl">
                <p class="text-[10px] font-bold text-outline uppercase tracking-widest mb-1">Total Expenses</p>
                <h3 class="text-xl font-headline font-black text-error">{{ formatCurrency(summary.total_expenses) }}</h3>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant/20 p-5 rounded-2xl">
                <p class="text-[10px] font-bold text-outline uppercase tracking-widest mb-1">Net Profit</p>
                <h3 class="text-xl font-headline font-black text-emerald-600">{{ formatCurrency(summary.net_profit) }}</h3>
            </div>
            <div class="bg-primary/5 border border-primary/20 p-5 rounded-2xl">
                <p class="text-[10px] font-bold text-primary uppercase tracking-widest mb-1">Total Receivables</p>
                <h3 class="text-xl font-headline font-black text-primary">{{ formatCurrency(aging.total_receivable) }}</h3>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Cash Flow Analysis -->
            <div class="md:col-span-2 bg-surface-container-lowest border border-outline-variant/20 p-6 rounded-2xl shadow-sm">
                <h3 class="text-sm font-headline font-bold text-on-surface mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[20px]">analytics</span>
                    Cash Flow Analysis (Last 6 Months)
                </h3>
                <div class="h-48 flex items-end justify-between gap-4 px-2">
                    <div v-for="month in cash_flow" :key="month.month" class="flex-1 flex flex-col items-center gap-2 group">
                        <div class="w-full flex justify-center gap-1 items-end h-32 relative">
                            <!-- Inflow Bar -->
                            <div 
                                class="w-3 bg-emerald-500/80 rounded-t-sm transition-all group-hover:bg-emerald-500" 
                                :style="{ height: (month.inflow / Math.max(...cash_flow.map(m => m.inflow)) * 100) + '%' }"
                                :title="'In: ' + formatCurrency(month.inflow)"
                            ></div>
                            <!-- Outflow Bar -->
                            <div 
                                class="w-3 bg-error/80 rounded-t-sm transition-all group-hover:bg-error" 
                                :style="{ height: (month.outflow / Math.max(...cash_flow.map(m => m.inflow)) * 100) + '%' }"
                                :title="'Out: ' + formatCurrency(month.outflow)"
                            ></div>
                        </div>
                        <span class="text-[10px] font-bold text-outline uppercase">{{ month.month }}</span>
                    </div>
                </div>
                <div class="flex justify-center gap-6 mt-6 border-t border-outline-variant/10 pt-4">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-emerald-500 rounded-full"></div>
                        <span class="text-[10px] font-bold text-outline uppercase tracking-widest">Inflow (Collections)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-error rounded-full"></div>
                        <span class="text-[10px] font-bold text-outline uppercase tracking-widest">Outflow (Expenses)</span>
                    </div>
                </div>
            </div>

            <!-- Debt Aging Summary -->
            <div class="bg-surface-container-lowest border border-outline-variant/20 p-6 rounded-2xl shadow-sm">
                <h3 class="text-sm font-headline font-bold text-on-surface mb-6">Receivables Aging</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-end border-b border-outline-variant/10 pb-2">
                        <span class="text-xs text-outline font-bold">Current (<30d)</span>
                        <span class="text-sm font-black text-on-surface">{{ formatCurrency(aging.current) }}</span>
                    </div>
                    <div class="flex justify-between items-end border-b border-outline-variant/10 pb-2">
                        <span class="text-xs text-outline font-bold">30 - 60 Days</span>
                        <span class="text-sm font-black text-orange-600">{{ formatCurrency(aging['30_60_days']) }}</span>
                    </div>
                    <div class="flex justify-between items-end border-b border-outline-variant/10 pb-2">
                        <span class="text-xs text-outline font-bold">60 - 90 Days</span>
                        <span class="text-sm font-black text-error/80">{{ formatCurrency(aging['60_90_days']) }}</span>
                    </div>
                    <div class="flex justify-between items-end border-b border-outline-variant/10 pb-2">
                        <span class="text-xs text-outline font-bold">Over 90 Days</span>
                        <span class="text-sm font-black text-error">{{ formatCurrency(aging.over_90_days) }}</span>
                    </div>
                </div>
                <div class="mt-6 p-4 bg-error/5 rounded-xl border border-error/10">
                    <p class="text-[10px] text-error font-bold uppercase tracking-widest mb-1">Critical Debt (>60d)</p>
                    <p class="text-lg font-headline font-black text-error">{{ formatCurrency(aging['60_90_days'] + aging.over_90_days) }}</p>
                </div>
            </div>
        </div>

        <!-- Ledger Table -->
        <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-outline-variant/20 flex justify-between items-center">
                <h3 class="text-sm font-headline font-bold text-on-surface uppercase tracking-widest">Transaction Ledger</h3>
                <div class="flex gap-2">
                    <button class="p-2 border border-outline-variant/20 rounded-lg hover:bg-surface-container-high transition-colors"><span class="material-symbols-outlined text-[18px]">print</span></button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low/50 text-[11px] font-bold text-outline uppercase tracking-wider">
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4">Transaction</th>
                            <th class="px-6 py-4">Type</th>
                            <th class="px-6 py-4">Total</th>
                            <th class="px-6 py-4">Paid</th>
                            <th class="px-6 py-4">Due</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <tr v-for="item in ledger" :key="item.id" class="text-xs hover:bg-surface-container-low/30 transition-colors">
                            <td class="px-6 py-4 text-outline">{{ item.date }}</td>
                            <td class="px-6 py-4 font-bold">{{ item.name }}</td>
                            <td class="px-6 py-4">
                                <Badge :variant="item.type === 'sale' ? 'success' : 'error'">{{ item.type.toUpperCase() }}</Badge>
                            </td>
                            <td class="px-6 py-4 font-black">{{ formatCurrency(item.amount) }}</td>
                            <td class="px-6 py-4 font-bold text-emerald-600">{{ formatCurrency(item.paid_amount) }}</td>
                            <td class="px-6 py-4 font-bold" :class="item.due_amount > 0 ? 'text-error' : 'text-outline'">{{ formatCurrency(item.due_amount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
