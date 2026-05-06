<script setup>
import { ref } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Badge from '@/Components/Badge.vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    stats: Object,
    banks: Array,
    recent_sales: Array,
    recent_expenses: Array,
});

const showFilters = ref(false);
const filters = ref({
    start_date: props.stats.filters.start_date,
    end_date: props.stats.filters.end_date,
});

const applyFilters = () => {
    router.get('/dashboard', filters.value, { preserveState: true });
    showFilters.value = false;
};

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

    <div class="space-y-6 animate-in fade-in duration-700 pb-20">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-headline font-bold text-on-surface tracking-tight">Precision Overview</h1>
                <p class="text-sm text-outline font-label">Financial and Logistics Monitoring</p>
            </div>
            <div class="flex items-center gap-3 relative">
                <div v-if="showFilters" class="absolute top-full right-0 mt-2 p-4 bg-surface-container-high border border-outline-variant/30 rounded-xl shadow-2xl z-50 flex flex-col gap-3 min-w-[250px]">
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-outline uppercase">From</label>
                        <input type="date" v-model="filters.start_date" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-lg p-2 text-sm text-on-surface focus:outline-none focus:border-primary">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-outline uppercase">To</label>
                        <input type="date" v-model="filters.end_date" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-lg p-2 text-sm text-on-surface focus:outline-none focus:border-primary">
                    </div>
                    <button @click="applyFilters" class="w-full bg-primary text-on-primary py-2 rounded-lg text-xs font-bold hover:opacity-90 transition-opacity">
                        Apply Filter
                    </button>
                </div>

                <button @click="showFilters = !showFilters" class="bg-surface-container-low border border-outline-variant/30 px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 hover:bg-surface-container-high transition-colors">
                    <span class="material-symbols-outlined text-[18px]">calendar_today</span>
                    {{ new Date(stats.filters.start_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }} - 
                    {{ new Date(stats.filters.end_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}
                </button>
            </div>
        </div>

        <!-- Main Stats Row -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-primary border border-primary-container p-6 rounded-2xl shadow-lg shadow-primary/10 text-on-primary">
                <p class="text-[10px] font-bold opacity-80 uppercase tracking-widest mb-1">Period Revenue</p>
                <h3 class="text-4xl font-headline font-black">{{ formatCurrency(stats.monthly_sales) }}</h3>
                <div class="flex items-center gap-1 mt-2 text-xs font-bold">
                    <span class="material-symbols-outlined text-[16px]">{{ stats.sales_growth >= 0 ? 'trending_up' : 'trending_down' }}</span>
                    {{ Math.abs(stats.sales_growth) }}% from previous period
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/20 p-6 rounded-2xl shadow-sm">
                <p class="text-[10px] font-bold text-outline uppercase tracking-widest mb-1">Period Expenses</p>
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

        <!-- Secondary Stats Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
        </div>

        <!-- Latest Transactions Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Latest Sales -->
            <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl shadow-sm overflow-hidden flex flex-col">
                <div class="p-6 border-b border-outline-variant/20 flex justify-between items-center">
                    <h3 class="text-sm font-headline font-bold text-on-surface uppercase tracking-widest">Latest Sales</h3>
                    <Link href="/sales" class="text-[10px] font-bold text-primary hover:underline uppercase tracking-widest">
                        View All
                    </Link>
                </div>
                <div class="flex-1">
                    <div v-for="sale in recent_sales" :key="sale.id" class="p-4 border-b border-outline-variant/10 flex justify-between items-center hover:bg-surface-container-low transition-colors">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-on-surface truncate">{{ sale.customer_name }}</p>
                            <p class="text-[10px] text-outline mt-1">{{ sale.date }} • {{ sale.invoice_number }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-black text-on-surface">{{ formatCurrency(sale.amount) }}</p>
                            <Badge :variant="getStatusVariant(sale.status)" class="!text-[9px] !px-2 !py-0.5 mt-1">{{ sale.status }}</Badge>
                        </div>
                    </div>
                    <div v-if="recent_sales.length === 0" class="p-12 text-center text-xs text-outline italic">No recent sales.</div>
                </div>
            </div>

            <!-- Latest Expenses -->
            <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl shadow-sm overflow-hidden flex flex-col">
                <div class="p-6 border-b border-outline-variant/20 flex justify-between items-center">
                    <h3 class="text-sm font-headline font-bold text-on-surface uppercase tracking-widest text-error">Latest Expenses</h3>
                    <Link href="/expenses" class="text-[10px] font-bold text-error hover:underline uppercase tracking-widest">
                        View All
                    </Link>
                </div>
                <div class="flex-1">
                    <div v-for="expense in recent_expenses" :key="expense.id" class="p-4 border-b border-outline-variant/10 flex justify-between items-center hover:bg-surface-container-low transition-colors">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-on-surface truncate">{{ expense.description }}</p>
                            <p class="text-[10px] text-outline mt-1">{{ expense.date }} • {{ expense.category }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-black text-error">{{ formatCurrency(expense.amount) }}</p>
                            <p class="text-[9px] font-bold text-outline mt-1 uppercase">{{ expense.payment_method }}</p>
                        </div>
                    </div>
                    <div v-if="recent_expenses.length === 0" class="p-12 text-center text-xs text-outline italic">No recent expenses.</div>
                </div>
            </div>
        </div>
    </div>
</template>
