<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    stats: Object,
    chart_data: Object,
    banks: Array,
    recent_sales: Array,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
};

const getInitials = (name) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().substr(0, 2);
};

// --- Chart Logic ---

// SVG Line Chart Logic
const salesPoints = computed(() => {
    const data = props.chart_data.daily_sales;
    const max = Math.max(...data.map(d => d.amount), 100);
    const height = 100;
    const width = 400;
    const step = width / (data.length - 1);
    
    return data.map((d, i) => ({
        x: i * step,
        y: height - (d.amount / max * height)
    }));
});

const polylinePoints = computed(() => {
    return salesPoints.value.map(p => `${p.x},${p.y}`).join(' ');
});

const areaPoints = computed(() => {
    const points = salesPoints.value;
    if (points.length === 0) return '';
    return `${points[0].x},100 ` + points.map(p => `${p.x},${p.y}`).join(' ') + ` ${points[points.length-1].x},100`;
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="space-y-10 animate-in fade-in slide-in-from-bottom-4 duration-700">
        <!-- Page Header -->
        <div class="flex items-end justify-between">
            <div>
                <h2 class="font-headline text-3xl sm:text-4xl font-extrabold text-on-surface tracking-tight mb-2">Welcome back, Admin</h2>
                <p class="text-on-surface-variant font-medium">Visual intelligence for your active organization.</p>
            </div>
            <div class="hidden md:flex items-center gap-3 bg-surface-container-highest py-2 px-4 rounded-xl border border-outline-variant/20 shadow-sm">
                <span class="material-symbols-outlined text-on-surface-variant text-[20px]">insights</span>
                <span class="font-bold text-sm text-on-surface uppercase tracking-tight italic">Live Analytics</span>
            </div>
        </div>

        <!-- KPI Bento Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Monthly Sales -->
            <div class="bg-surface-container-lowest rounded-2xl p-6 shadow-sm border border-outline-variant/10 relative overflow-hidden group hover:-translate-y-1 transition-all duration-300">
                <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
                    <span class="material-symbols-outlined text-[72px] text-primary">payments</span>
                </div>
                <div class="flex items-center gap-3 mb-4 relative z-10">
                    <div class="w-10 h-10 rounded-full bg-primary-fixed flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-xl">trending_up</span>
                    </div>
                    <span class="font-label font-bold text-on-surface-variant text-xs uppercase tracking-widest">Monthly Sales</span>
                </div>
                <div class="font-headline text-3xl font-extrabold text-on-surface mb-2 relative z-10">{{ formatCurrency(stats.monthly_sales) }}</div>
                <div class="flex items-center gap-1 text-xs relative z-10">
                    <span class="material-symbols-outlined text-tertiary text-[16px]" v-if="stats.sales_growth >= 0">arrow_upward</span>
                    <span class="material-symbols-outlined text-error text-[16px]" v-else>arrow_downward</span>
                    <span :class="stats.sales_growth >= 0 ? 'text-[#14804a]' : 'text-error'" class="font-bold">{{ Math.abs(stats.sales_growth) }}%</span>
                    <span class="text-on-surface-variant ml-1 font-medium italic">vs last month</span>
                </div>
            </div>

            <!-- Available Cash -->
            <div class="bg-surface-container-lowest rounded-2xl p-6 shadow-sm border border-outline-variant/10 relative overflow-hidden group hover:-translate-y-1 transition-all duration-300">
                <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
                    <span class="material-symbols-outlined text-[72px] text-secondary">account_balance</span>
                </div>
                <div class="flex items-center gap-3 mb-4 relative z-10">
                    <div class="w-10 h-10 rounded-full bg-secondary-fixed flex items-center justify-center text-on-secondary-fixed">
                        <span class="material-symbols-outlined text-xl">account_balance_wallet</span>
                    </div>
                    <span class="font-label font-bold text-on-surface-variant text-xs uppercase tracking-widest">Available Cash</span>
                </div>
                <div class="font-headline text-3xl font-extrabold text-on-surface mb-2 relative z-10">{{ formatCurrency(stats.total_bank_cash) }}</div>
                <div class="text-[10px] text-on-surface-variant font-bold uppercase tracking-tight relative z-10">Aggregate of all Bank Balances</div>
            </div>

            <!-- Monthly Expenses -->
            <div class="bg-surface-container-lowest rounded-2xl p-6 shadow-sm border border-outline-variant/10 relative overflow-hidden group hover:-translate-y-1 transition-all duration-300">
                <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
                    <span class="material-symbols-outlined text-[72px] text-tertiary">receipt_long</span>
                </div>
                <div class="flex items-center gap-3 mb-4 relative z-10">
                    <div class="w-10 h-10 rounded-full bg-tertiary-fixed flex items-center justify-center text-on-tertiary-fixed">
                        <span class="material-symbols-outlined text-xl">outbox</span>
                    </div>
                    <span class="font-label font-bold text-on-surface-variant text-xs uppercase tracking-widest">Monthly Expenses</span>
                </div>
                <div class="font-headline text-3xl font-extrabold text-on-surface mb-2 relative z-10 text-error">{{ formatCurrency(stats.monthly_expenses) }}</div>
                <div class="text-[10px] text-on-surface-variant font-bold uppercase tracking-tight relative z-10">Tracked organizational burn</div>
            </div>

            <!-- Net Profit -->
            <div class="bg-gradient-to-br from-primary to-primary-container rounded-2xl p-6 text-on-primary shadow-xl relative overflow-hidden group hover:-translate-y-1 transition-all duration-300">
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-2xl group-hover:scale-110 transition-transform duration-700"></div>
                <div class="relative z-10 flex flex-col items-start">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-on-primary backdrop-blur-md">
                            <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">stars</span>
                        </div>
                        <span class="font-label font-bold text-on-primary-container/80 text-xs uppercase tracking-widest">Net monthly profit</span>
                    </div>
                    <div class="font-headline text-3xl font-extrabold mb-2">{{ formatCurrency(stats.net_profit) }}</div>
                    <div class="text-[10px] text-on-primary-container font-bold uppercase tracking-widest flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-on-primary-container"></span>
                        Keep Building Value
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Weekly Trend Chart -->
            <div class="bg-surface-container-lowest rounded-2xl p-6 border border-outline-variant/10 shadow-sm relative overflow-hidden group">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-10 gap-2">
                    <div>
                        <h3 class="font-headline text-lg font-bold text-on-surface">Weekly Sales Trend</h3>
                        <p class="text-xs text-outline font-body">Performance overview of the last 7 days</p>
                    </div>
                    <div class="px-3 py-1 bg-primary/10 rounded-full text-[10px] font-bold text-primary uppercase tracking-widest">Real-time</div>
                </div>

                <!-- Custom SVG Chart -->
                <div class="relative h-48 w-full mt-4">
                    <svg class="w-full h-full overflow-visible" viewBox="0 0 400 100" preserveAspectRatio="none font-['Inter']">
                        <!-- Area Fill -->
                        <polygon :points="areaPoints" fill="url(#salesGradient)" opacity="0.15" />
                        <!-- Line -->
                        <polyline :points="polylinePoints" fill="none" stroke="currentColor" stroke-width="3" class="text-primary" stroke-linecap="round" stroke-linejoin="round" />
                        <!-- Points -->
                        <circle v-for="(p, i) in salesPoints" :key="i" :cx="p.x" :cy="p.y" r="4" class="fill-surface-container-lowest stroke-primary stroke-[2px]" />
                        
                        <!-- Definitions -->
                        <defs>
                            <linearGradient id="salesGradient" x1="0" x2="0" y1="0" y2="1">
                                <stop offset="0%" stop-color="currentColor" class="text-primary" />
                                <stop offset="100%" stop-color="transparent" />
                            </linearGradient>
                        </defs>
                    </svg>
                    
                    <!-- Labels -->
                    <div class="flex justify-between mt-4 px-1">
                        <span v-for="d in chart_data.daily_sales" :key="d.day" class="text-[10px] font-bold text-outline uppercase tracking-tighter">{{ d.day }}</span>
                    </div>
                </div>
            </div>

            <!-- Expense Distribution -->
            <div class="bg-surface-container-lowest rounded-2xl p-6 border border-outline-variant/10 shadow-sm relative group flex flex-col">
                <div class="flex justify-between items-start mb-8">
                    <div>
                        <h3 class="font-headline text-lg font-bold text-on-surface">Top Expenses</h3>
                        <p class="text-xs text-outline font-body">Highest spending categories this month</p>
                    </div>
                    <span class="material-symbols-outlined text-outline">pie_chart</span>
                </div>

                <div class="space-y-6 flex-1 flex flex-col justify-center">
                    <div v-for="item in chart_data.expense_breakdown" :key="item.description" class="space-y-2">
                        <div class="flex justify-between text-xs font-bold font-label">
                            <span class="text-on-surface truncate pr-4">{{ item.description }}</span>
                            <span class="text-error">{{ formatCurrency(item.total) }}</span>
                        </div>
                        <div class="w-full h-2 bg-surface-container-high rounded-full overflow-hidden">
                            <div 
                                class="h-full bg-tertiary rounded-full group-hover:opacity-80 animate-bar-grow"
                                :style="{ width: (item.total / stats.monthly_expenses * 100) + '%' }"
                            ></div>
                        </div>
                    </div>
                    <div v-if="chart_data.expense_breakdown.length === 0" class="py-12 text-center text-outline italic text-sm">
                        No expenses recorded yet for this month.
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Left: Recent Sales -->
            <div class="lg:col-span-8 space-y-6">
                <div class="bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/10 p-6 overflow-hidden">
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="font-headline text-xl font-bold text-on-surface">Recent Activity Feed</h3>
                        <Link href="/sales" class="text-primary font-bold text-xs uppercase tracking-widest hover:underline flex items-center gap-1">
                            View All Records <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                        </Link>
                    </div>
                    
                    <div class="w-full overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-[11px] font-label font-bold text-outline uppercase tracking-[0.2em] border-b border-surface-container-high">
                                    <th class="pb-4 px-4">Date</th>
                                    <th class="pb-4 px-4">Customer</th>
                                    <th class="pb-4 px-4 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-container-low">
                                <tr v-for="sale in recent_sales" :key="sale.id" class="group hover:bg-surface-container-low/50 transition-colors">
                                    <td class="py-4 px-4 text-sm font-body text-on-surface-variant font-medium">{{ sale.date }}</td>
                                    <td class="py-4 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-secondary-container flex items-center justify-center text-on-secondary-container text-[10px] font-bold font-headline shrink-0">
                                                {{ getInitials(sale.customer_name) }}
                                            </div>
                                            <span class="text-sm font-body text-on-background font-bold truncate">{{ sale.customer_name }}</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-right text-sm font-body text-on-background font-black">{{ formatCurrency(sale.amount) }}</td>
                                </tr>
                                <tr v-if="recent_sales.length === 0">
                                    <td colspan="3" class="py-12 text-center text-outline text-sm">No recent transactions recorded.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right: Bank Overview -->
            <div class="lg:col-span-4 space-y-6">
                <div class="bg-surface-container-low rounded-2xl p-6 border border-outline-variant/10 relative overflow-hidden group">
                    <h3 class="font-headline text-lg font-bold text-on-surface mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary">account_balance</span>
                        Vault Overview
                    </h3>
                    <div class="space-y-4">
                        <div v-for="bank in banks" :key="bank.id" class="bg-surface-container-lowest p-4 rounded-xl flex items-center justify-between border-l-4 border-secondary shadow-sm hover:shadow-md transition-all group/item">
                            <div>
                                <div class="text-[10px] font-bold text-outline uppercase tracking-widest mb-1">{{ bank.name }}</div>
                                <div class="text-lg font-headline font-extrabold text-on-surface">{{ formatCurrency(bank.balance) }}</div>
                            </div>
                            <div class="opacity-0 group-hover/item:opacity-10 transition-opacity">
                                <span class="material-symbols-outlined text-[32px] text-secondary">savings</span>
                            </div>
                        </div>

                        <div v-if="banks.length === 0" class="p-8 text-center text-outline italic text-sm">
                            No bank infrastructure configured yet.
                        </div>

                        <Link href="/banks" class="mt-4 w-full py-3 rounded-xl border border-secondary/20 text-[11px] font-bold uppercase tracking-widest text-secondary hover:bg-secondary-container/10 transition-colors flex items-center justify-center gap-2">
                            Manage Financial Nodes
                            <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.font-headline { font-family: 'Manrope', sans-serif; }
.font-label { font-family: 'Inter', sans-serif; }

/* Scoped animation for progress bars to prevent global layout shifting */
@keyframes barGrow {
    from { width: 0; }
}
.animate-bar-grow { 
    animation: barGrow 1s ease-out forwards; 
}
</style>
