<script setup>
import { ref, computed } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import Badge from '@/Components/Badge.vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    stats: Object,
    chart_data: Object,
    banks: Array,
    recent_sales: Array,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-AE', { style: 'currency', currency: 'AED' }).format(value);
};

const getStatusVariant = (status) => {
    switch (status) {
        case 'paid': return 'success';
        case 'partial': return 'warning';
        case 'unpaid': return 'error';
        default: return 'neutral';
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="space-y-6 animate-in fade-in duration-700">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-headline font-bold text-on-surface tracking-tight">Precision Overview</h1>
                <p class="text-sm text-outline font-label">Financial and Logistics Monitoring</p>
            </div>
            <div class="flex items-center gap-3">
                <button class="bg-surface-container-low border border-outline-variant/30 px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 hover:bg-surface-container-high transition-colors">
                    <span class="material-symbols-outlined text-[18px]">calendar_today</span>
                    {{ new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}
                </button>
            </div>
        </div>

        <!-- Main Stats Row -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-primary border border-primary-container p-6 rounded-2xl shadow-lg shadow-primary/10 text-on-primary">
                <p class="text-[10px] font-bold opacity-80 uppercase tracking-widest mb-1">Monthly Revenue</p>
                <h3 class="text-4xl font-headline font-black">{{ formatCurrency(stats.monthly_sales) }}</h3>
                <div class="flex items-center gap-1 mt-2 text-xs font-bold">
                    <span class="material-symbols-outlined text-[16px]">{{ stats.sales_growth >= 0 ? 'trending_up' : 'trending_down' }}</span>
                    {{ Math.abs(stats.sales_growth) }}% from last month
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/20 p-6 rounded-2xl shadow-sm">
                <p class="text-[10px] font-bold text-outline uppercase tracking-widest mb-1">Monthly Expenses</p>
                <h3 class="text-4xl font-headline font-black text-error">{{ formatCurrency(stats.monthly_expenses) }}</h3>
                <p class="text-[10px] text-outline font-bold mt-2">Operating costs</p>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/20 p-6 rounded-2xl shadow-sm">
                <p class="text-[10px] font-bold text-outline uppercase tracking-widest mb-1">Total Bank Liquidity</p>
                <h3 class="text-4xl font-headline font-black text-emerald-600">{{ formatCurrency(stats.bank_liquidity) }}</h3>
                <p class="text-[10px] text-outline font-bold mt-2">Combined Bank 1 & 2</p>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/20 p-6 rounded-2xl shadow-sm">
                <p class="text-[10px] font-bold text-outline uppercase tracking-widest mb-1">Cash Balance</p>
                <h3 class="text-4xl font-headline font-black text-on-surface">{{ formatCurrency(stats.cash_balance) }}</h3>
                <p class="text-[10px] text-outline font-bold mt-2">Physical Cash on Hand</p>
            </div>
        </div>

        <!-- Secondary Stats & Alerts Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Active Shipments -->
            <div class="bg-surface-container-lowest border border-outline-variant/20 p-6 rounded-2xl shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-sm font-headline font-bold text-on-surface uppercase tracking-widest">Active Shippings</h3>
                    <div class="w-10 h-10 bg-orange-500/10 rounded-xl flex items-center justify-center text-orange-500">
                        <span class="material-symbols-outlined">directions_boat</span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <h2 class="text-6xl font-black text-orange-500">{{ stats.active_shipments }}</h2>
                    <p class="text-sm text-outline font-label">Containers currently <br> in transit or on board</p>
                </div>
                <Link href="/shipping" class="mt-6 block text-center py-2 bg-surface-container-low rounded-lg text-xs font-bold hover:bg-surface-container-high transition-colors">
                    View Shipping Board
                </Link>
            </div>

            <!-- Upcoming Cheques -->
            <div class="bg-surface-container-lowest border border-outline-variant/20 p-6 rounded-2xl shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-sm font-headline font-bold text-on-surface uppercase tracking-widest">Cheque Alerts</h3>
                    <div class="w-10 h-10 bg-purple-500/10 rounded-xl flex items-center justify-center text-purple-500 relative">
                        <span class="material-symbols-outlined">payments</span>
                        <span v-if="stats.upcoming_cheques > 0" class="absolute -top-1 -right-1 w-3 h-3 bg-error rounded-full animate-pulse border-2 border-surface-container-lowest"></span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <h2 class="text-6xl font-black text-purple-500">{{ stats.upcoming_cheques }}</h2>
                    <p class="text-sm text-outline font-label">Cheques due for collection <br> in the next 5 days</p>
                </div>
                <Link href="/banks" class="mt-6 block text-center py-2 bg-purple-500/5 text-purple-600 rounded-lg text-xs font-bold hover:bg-purple-500/10 transition-colors">
                    Open Bank Manager
                </Link>
            </div>

            <!-- Top Expense Distribution -->
            <div class="bg-surface-container-lowest border border-outline-variant/20 p-6 rounded-2xl shadow-sm">
                <h3 class="text-sm font-headline font-bold text-on-surface uppercase tracking-widest mb-6">Expense Breakdown</h3>
                <div class="space-y-4">
                    <div v-for="expense in chart_data.expense_breakdown" :key="expense.description" class="space-y-1">
                        <div class="flex justify-between text-sm font-bold">
                            <span class="text-on-surface truncate pr-2">{{ expense.description }}</span>
                            <span class="text-outline">{{ formatCurrency(expense.total) }}</span>
                        </div>
                        <div class="w-full bg-surface-container-low rounded-full h-1.5">
                            <div class="bg-primary h-full rounded-full" :style="{ width: (expense.total / stats.monthly_expenses * 100) + '%' }"></div>
                        </div>
                    </div>
                    <div v-if="chart_data.expense_breakdown.length === 0" class="text-center py-4 text-xs text-outline">No expenses recorded this month.</div>
                </div>
            </div>
        </div>

        <!-- Recent Activity & Chart -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pb-10">
            <!-- Daily Sales Chart -->
            <div class="md:col-span-2 bg-surface-container-lowest border border-outline-variant/20 p-6 rounded-2xl shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-sm font-headline font-bold text-on-surface uppercase tracking-widest">Revenue Trend</h3>
                    <div class="flex gap-2">
                        <div class="flex items-center gap-1 text-[10px] text-outline font-bold"><div class="w-2 h-2 bg-primary rounded-full"></div> Last 7 Days</div>
                    </div>
                </div>
                <div class="h-48 flex items-end justify-between gap-2 px-2">
                    <div v-for="day in chart_data.daily_sales" :key="day.day" class="flex-1 flex flex-col items-center gap-2 group">
                        <div 
                            class="w-full bg-primary/20 rounded-t-lg transition-all group-hover:bg-primary/40 relative" 
                            :style="{ height: (day.amount / Math.max(...chart_data.daily_sales.map(d => d.amount), 1) * 100) + '%' }"
                        >
                            <div v-if="day.amount > 0" class="absolute -top-6 left-1/2 -translate-x-1/2 bg-on-surface text-surface text-[8px] px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 transition-opacity">
                                {{ formatCurrency(day.amount).replace('AED', '') }}
                            </div>
                        </div>
                        <span class="text-[10px] font-bold text-outline uppercase">{{ day.day }}</span>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl shadow-sm overflow-hidden flex flex-col">
                <div class="p-6 border-b border-outline-variant/20">
                    <h3 class="text-xl font-headline font-bold text-on-surface uppercase tracking-widest">Latest Sales</h3>
                </div>
                <div class="flex-1 overflow-y-auto max-h-[300px]">
                    <div v-for="sale in recent_sales" :key="sale.id" class="p-6 border-b border-outline-variant/10 flex justify-between items-center hover:bg-surface-container-low transition-colors">
                        <div class="min-w-0">
                            <p class="text-xl font-bold text-on-surface truncate">{{ sale.customer_name }}</p>
                            <p class="text-base text-outline mt-1">{{ sale.date }} • {{ sale.invoice_number }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-black text-on-surface">{{ formatCurrency(sale.amount) }}</p>
                            <Badge :variant="getStatusVariant(sale.status)" class="!text-xs !px-3 !py-1 mt-1">{{ sale.status }}</Badge>
                        </div>
                    </div>
                    <div v-if="recent_sales.length === 0" class="p-8 text-center text-xs text-outline">No recent activity.</div>
                </div>
                <Link href="/sales" class="p-3 text-center text-[10px] font-bold text-primary hover:bg-primary/5 transition-colors uppercase tracking-widest">
                    View All Sales
                </Link>
            </div>
        </div>
    </div>
</template>
