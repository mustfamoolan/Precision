<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    summary: Object,
    top_expenses: Array,
    ledger: Array,
    filters: Object,
});

const filterType = ref(props.filters.filter || 'month');
const startDate = ref(props.filters.start_date || props.summary.start_date);
const endDate = ref(props.filters.end_date || props.summary.end_date);

const updateFilter = () => {
    router.get('/reports', { 
        filter: filterType.value,
        start_date: startDate.value,
        end_date: endDate.value
    }, { 
        preserveState: true, 
        preserveScroll: true 
    });
};

watch(filterType, (val) => {
    if (val !== 'custom') {
        updateFilter();
    }
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
};

const printReport = () => {
    window.print();
};
</script>

<template>
    <Head title="Business Reports" />

    <div class="space-y-10 print:space-y-6 animate-in fade-in duration-700">
        <!-- Page Header & Filters -->
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-end gap-6 print:hidden">
            <div>
                <h1 class="font-headline text-3xl sm:text-4xl font-extrabold text-on-surface tracking-tight mb-2">Business Intel</h1>
                <p class="text-on-surface-variant font-medium">Precision analytics and professional financial reporting.</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-4">
                <!-- Period Switcher -->
                <div class="bg-surface-container-highest p-1 rounded-xl border border-outline-variant/20 flex items-center shadow-[0_4px_12px_0_rgba(0,0,0,0.03)]">
                    <button 
                        v-for="f in ['week', 'month', 'custom']" 
                        :key="f"
                        @click="filterType = f"
                        class="px-5 py-2 rounded-lg text-[11px] font-black uppercase tracking-widest transition-all"
                        :class="filterType === f ? 'bg-white text-primary shadow-sm' : 'text-outline hover:text-on-surface'"
                    >
                        {{ f }}
                    </button>
                </div>

                <!-- Date Picker (M3 Style) -->
                <div v-if="filterType === 'custom'" class="flex items-center gap-3 bg-surface-container-highest py-2 px-4 rounded-xl border border-outline-variant/20 shadow-[0_4px_12px_0_rgba(0,0,0,0.03)] animate-in slide-in-from-right-4">
                    <span class="material-symbols-outlined text-outline text-[20px]">calendar_today</span>
                    <div class="flex items-center gap-2">
                        <input type="date" v-model="startDate" class="bg-transparent border-none text-xs font-bold text-on-surface p-0 focus:ring-0 w-28" />
                        <span class="text-outline">→</span>
                        <input type="date" v-model="endDate" class="bg-transparent border-none text-xs font-bold text-on-surface p-0 focus:ring-0 w-28" />
                    </div>
                    <button @click="updateFilter" class="ml-2 p-1.5 bg-primary/10 text-primary rounded-lg hover:bg-primary/20 transition-colors">
                        <span class="material-symbols-outlined text-[18px]">refresh</span>
                    </button>
                </div>

                <!-- Print Button -->
                <button @click="printReport" class="flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-on-primary px-6 py-3 rounded-xl font-black text-[11px] uppercase tracking-widest shadow-[0_8px_16px_-4px_rgba(0,62,199,0.2)] hover:shadow-[0_12px_24px_-4px_rgba(0,62,199,0.3)] transition-all active:scale-95">
                    <span class="material-symbols-outlined text-[20px]">print</span>
                    Print Intel
                </button>
            </div>
        </div>

        <!-- REPORT CANVAS -->
        <div class="space-y-10 print:space-y-8">
            <!-- Print Header -->
            <div class="hidden print:flex justify-between items-center border-b-2 border-primary pb-8 mb-10">
                <div>
                    <h1 class="text-4xl font-black font-headline text-primary tracking-tighter">PRECISION</h1>
                    <p class="text-[10px] font-bold text-outline uppercase tracking-[0.4em]">Business Intelligence Division</p>
                </div>
                <div class="text-right">
                    <div class="text-[10px] font-black text-outline uppercase tracking-widest mb-1">Generated Period</div>
                    <div class="text-base font-black text-on-surface">{{ summary.period_label }}</div>
                </div>
            </div>

            <!-- KPI Cards (Bento Style 100%) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Revenue Card -->
                <div class="bg-surface-container-lowest rounded-2xl p-7 shadow-[0_8px_24px_0_rgba(0,0,0,0.04)] border border-outline-variant/10 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
                        <span class="material-symbols-outlined text-[72px] text-primary">trending_up</span>
                    </div>
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-full bg-primary-fixed flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined text-xl">payments</span>
                        </div>
                        <span class="font-label font-bold text-outline text-[11px] uppercase tracking-[0.2em]">Total Revenue</span>
                    </div>
                    <div class="font-headline text-4xl font-extrabold text-on-surface mb-2 tracking-tight">{{ formatCurrency(summary.total_sales) }}</div>
                    <div class="flex items-center gap-1">
                        <span class="text-[10px] bg-[#e6f4ea] text-[#137333] px-2 py-0.5 rounded-full font-bold border border-[#ceead6]">
                            {{ summary.sales_count }} INVOICES
                        </span>
                    </div>
                </div>

                <!-- Expenses Card -->
                <div class="bg-surface-container-lowest rounded-2xl p-7 shadow-[0_8px_24px_0_rgba(0,0,0,0.04)] border border-outline-variant/10 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
                        <span class="material-symbols-outlined text-[72px] text-tertiary">receipt_long</span>
                    </div>
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-full bg-surface-container-high flex items-center justify-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-xl">account_balance_wallet</span>
                        </div>
                        <span class="font-label font-bold text-outline text-[11px] uppercase tracking-[0.2em]">Operational Costs</span>
                    </div>
                    <div class="font-headline text-4xl font-extrabold text-error mb-2 tracking-tight">{{ formatCurrency(summary.total_expenses) }}</div>
                    <div class="flex items-center gap-1">
                        <span class="text-[10px] bg-[#fdf2f2] text-error px-2 py-0.5 rounded-full font-bold border border-[#fbd5d5]">
                            {{ summary.expenses_count }} ENTries
                        </span>
                    </div>
                </div>

                <!-- Profit Card (Premium Gradient) -->
                <div class="bg-gradient-to-br from-primary to-primary-container rounded-2xl p-7 shadow-[0_12px_32px_0_rgba(0,62,199,0.15)] relative overflow-hidden group">
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-on-primary backdrop-blur-md">
                                <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">stars</span>
                            </div>
                            <span class="font-label font-bold text-on-primary-container text-[11px] uppercase tracking-[0.2em] opacity-80">Net Surplus</span>
                        </div>
                        <div class="font-headline text-4xl font-extrabold text-on-primary mb-2 tracking-tight">{{ formatCurrency(summary.net_profit) }}</div>
                        <div class="flex items-center gap-1">
                            <span class="text-[10px] text-on-primary font-bold uppercase tracking-widest opacity-60 flex items-center gap-1">
                                <span class="w-1 h-1 bg-white rounded-full"></span>
                                Performance Node Active
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Ledger Table (Enhanced Stitch Style) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <div class="lg:col-span-8 bg-surface-container-lowest print:bg-white rounded-2xl border border-outline-variant/10 shadow-[0_8px_24px_0_rgba(0,0,0,0.03)] p-6 overflow-hidden print:shadow-none print:border-none print:p-0">
                    <div class="flex items-center justify-between mb-8 print:mb-4">
                        <h3 class="font-headline text-xl font-bold text-on-surface">Financial Record Feed</h3>
                        <div class="text-[10px] font-black text-outline uppercase tracking-widest print:hidden">Sorted by Date Desc</div>
                    </div>
                    
                    <div class="w-full overflow-x-auto">
                        <table class="w-full text-left border-separate border-spacing-y-2">
                            <thead>
                                <tr class="text-[10px] font-black text-outline uppercase tracking-widest border-b border-surface-container-high">
                                    <th class="pb-2 px-4 w-[25%]">Date</th>
                                    <th class="pb-2 px-4 w-[50%]">Transaction Entity</th>
                                    <th class="pb-2 px-4 w-[25%] text-right">Balance Impact</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, i) in ledger" :key="i" class="group hover:bg-surface-container-low transition-colors">
                                    <td class="py-4 px-4 text-xs font-bold text-on-surface bg-surface-container-low/30 group-hover:bg-surface-container-low rounded-l-xl transition-colors">{{ item.date }}</td>
                                    <td class="py-4 px-4 bg-surface-container-low/30 group-hover:bg-surface-container-low transition-colors">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0" :class="item.type === 'sale' ? 'bg-[#e6f4ea] text-[#137333]' : 'bg-error-container/30 text-error'">
                                                <span class="material-symbols-outlined text-[18px]">{{ item.type === 'sale' ? 'add' : 'remove' }}</span>
                                            </div>
                                            <span class="text-sm font-body text-on-background font-bold truncate">{{ item.name }}</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-right rounded-r-xl bg-surface-container-low/30 group-hover:bg-surface-container-low transition-colors">
                                        <span class="text-sm font-headline font-black" :class="item.type === 'sale' ? 'text-on-surface' : 'text-error'">
                                            {{ item.type === 'expense' ? '-' : '+' }}{{ formatCurrency(item.amount) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="ledger.length === 0">
                                    <td colspan="3" class="py-20 text-center">
                                        <span class="material-symbols-outlined text-outline-variant text-[48px] mb-4">folder_off</span>
                                        <p class="text-outline text-sm font-bold uppercase tracking-widest">No intelligence found in this sector</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right Rail: Expense Intelligence -->
                <div class="lg:col-span-4 space-y-8 print:break-before-page">
                    <div class="bg-surface-container-low rounded-2xl border border-outline-variant/10 p-7 shadow-sm print:bg-white print:border-none print:shadow-none print:p-0">
                        <h3 class="font-headline text-lg font-bold text-on-surface mb-8 print:mb-4">Cost Distribution</h3>
                        
                        <div class="space-y-8">
                            <div v-for="item in top_expenses" :key="item.description" class="space-y-3">
                                <div class="flex justify-between text-[10px] font-black uppercase tracking-widest">
                                    <span class="text-on-surface-variant truncate pr-4">{{ item.description }}</span>
                                    <span class="text-error">{{ formatCurrency(item.total) }}</span>
                                </div>
                                <div class="w-full h-2 bg-surface-container-highest rounded-full overflow-hidden shadow-inner">
                                    <div 
                                        class="h-full bg-gradient-to-r from-error/60 to-error rounded-full transition-all duration-1000"
                                        :style="{ width: (item.total / summary.total_expenses * 100) + '%' }"
                                    ></div>
                                </div>
                            </div>
                            <div v-if="top_expenses.length === 0" class="py-12 text-center text-outline text-xs italic">Sector cleared. No expenses.</div>
                        </div>

                        <div class="mt-12 p-5 bg-white/40 dark:bg-white/5 rounded-xl border border-white/20 backdrop-blur-md">
                            <h4 class="text-[9px] font-black text-on-surface-variant uppercase tracking-[0.3em] mb-2">Internal Note</h4>
                            <p class="text-[11px] text-on-surface-variant font-medium leading-relaxed italic opacity-80">Reports are synchronized with the primary vault ledger. All calculations are real-time.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Professional Print Legal Footer -->
        <div class="hidden print:block text-center mt-20 pt-10 border-t border-slate-100">
            <p class="text-[10px] text-outline font-bold uppercase tracking-[0.1em] mb-4 italic">This document is proprietary information of the Precision Admin Platform.</p>
            <div class="flex justify-center gap-20 mt-10">
                <div class="text-center">
                    <div class="w-40 border-b border-on-background mb-2"></div>
                    <span class="text-[9px] font-black text-outline uppercase tracking-widest">Authorized Auditor</span>
                </div>
                <div class="text-center">
                    <div class="w-40 border-b border-on-background mb-2">{{ new Date().toLocaleDateString() }}</div>
                    <span class="text-[9px] font-black text-outline uppercase tracking-widest">Date of Issue</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.font-headline { font-family: 'Manrope', sans-serif; }
.font-label { font-family: 'Inter', sans-serif; }

@media print {
    header, nav, footer, .print-hidden, [role="navigation"], button {
        display: none !important;
    }
    
    main, .ml-64, .lg\:ml-64 {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    
    body {
        background-color: white !important;
        color: black !important;
    }

    .bg-surface-container-low\/30 {
        background-color: #f9fafb !important;
    }

    .text-error { color: #ba1a1a !important; }
    .text-primary { color: #003ec7 !important; }
    
    .shadow-\[0_8px_24px_0_rgba\(0\,0\,0\,0\.04\)\] {
        box-shadow: none !important;
    }
}
</style>
