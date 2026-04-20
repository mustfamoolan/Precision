<script setup>
import { ref, computed } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import SideModal from '@/Components/SideModal.vue';
import FormField from '@/Components/FormField.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    sales: Array,
    total_sales_period: Number,
    total_sales_month: Number,
    filters: Object,
});

const showAddModal = ref(false);
const showCustomRange = ref(props.filters.filter === 'custom' || (props.filters.start_date && props.filters.end_date));
const search = ref(props.filters.search || '');

const customRange = ref({
    start: props.filters.start_date || '',
    end: props.filters.end_date || '',
});

const form = useForm({
    date: new Date().toISOString().substr(0, 10),
    invoice_number: '',
    customer_name: '',
    amount: '',
});

const submit = () => {
    form.post('/sales', {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset();
        },
    });
};

const applyFilter = (filterType) => {
    if (filterType === 'custom') {
        showCustomRange.value = !showCustomRange.value;
        return;
    }
    showCustomRange.value = false;
    router.get('/sales', { filter: filterType }, { preserveState: true, preserveScroll: true });
};

const applyCustomRange = () => {
    if (customRange.value.start && customRange.value.end) {
        router.get('/sales', { 
            filter: 'custom', 
            start_date: customRange.value.start, 
            end_date: customRange.value.end 
        }, { preserveState: true, preserveScroll: true });
    }
};

const resetFilters = () => {
    search.value = '';
    showCustomRange.value = false;
    router.get('/sales', {}, { preserveState: true, preserveScroll: true });
};

const getInitials = (name) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().substr(0, 2);
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
};

// Handle Search
const handleSearch = () => {
    router.get('/sales', { 
        search: search.value,
        filter: props.filters.filter,
        start_date: props.filters.start_date,
        end_date: props.filters.end_date
    }, { preserveState: true, preserveScroll: true });
};
</script>

<template>
    <Head title="Sales Management" />

    <div class="space-y-8 animate-in fade-in duration-500">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-4">
            <div class="space-y-2 max-w-xl">
                <h1 class="text-3xl sm:text-4xl font-headline font-extrabold tracking-tight text-on-background">Sales Management</h1>
                <p class="text-on-surface-variant font-body text-base">Monitor and manage your organization's revenue streams across all active channels.</p>
            </div>
            
            <PrimaryButton @click="showAddModal = true" class="w-fit flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">add</span>
                Add Sale
            </PrimaryButton>
        </div>

        <!-- Filters & Search Bar Replacement in Page -->
        <div class="flex flex-col gap-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-surface-container-lowest p-2 rounded-xl border border-outline-variant/10 shadow-sm transition-colors">
                <div class="flex bg-surface-container-low rounded-lg p-1">
                    <button 
                        @click="applyFilter('week')"
                        class="px-4 py-2 text-sm font-label font-medium rounded-md transition-colors"
                        :class="[filters.filter === 'week' ? 'bg-surface-container-lowest text-primary shadow-sm font-bold' : 'text-on-surface-variant hover:text-on-surface']"
                    >
                        This Week
                    </button>
                    <button 
                        @click="applyFilter('month')"
                        class="px-4 py-2 text-sm font-label font-medium rounded-md transition-colors"
                        :class="[filters.filter === 'month' ? 'bg-surface-container-lowest text-primary shadow-sm font-bold' : 'text-on-surface-variant hover:text-on-surface']"
                    >
                        This Month
                    </button>
                    <button 
                        @click="applyFilter('custom')"
                        class="px-4 py-2 text-sm font-label font-medium rounded-md transition-colors flex items-center gap-1"
                        :class="[showCustomRange || filters.filter === 'custom' ? 'bg-surface-container-lowest text-primary shadow-sm font-bold' : 'text-on-surface-variant hover:text-on-surface']"
                    >
                        Custom Range
                        <span class="material-symbols-outlined text-[16px]">calendar_today</span>
                    </button>
                </div>

                <div class="flex items-center gap-2 px-2 w-full sm:w-auto">
                    <div class="relative flex items-center w-full sm:w-64">
                        <span class="material-symbols-outlined absolute left-3 text-on-surface-variant text-[20px]">search</span>
                        <input 
                            v-model="search"
                            @keyup.enter="handleSearch"
                            type="text" 
                            placeholder="Search invoices..." 
                            class="w-full pl-10 pr-4 py-2 bg-surface-container-high rounded-lg border border-outline-variant/10 focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 text-sm font-body transition-all"
                        />
                    </div>
                    <button @click="resetFilters" class="p-2 text-outline hover:text-error transition-colors" title="Clear All Filters">
                        <span class="material-symbols-outlined">filter_alt_off</span>
                    </button>
                </div>
            </div>

            <!-- Custom Range Inputs (Hidden by default) -->
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="transform -translate-y-2 opacity-0"
                enter-to-class="transform translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="transform translate-y-0 opacity-100"
                leave-to-class="transform -translate-y-2 opacity-0"
            >
                <div v-if="showCustomRange" class="flex flex-col sm:flex-row items-end gap-4 bg-surface-container-low/50 p-4 rounded-xl border border-outline-variant/10">
                    <div class="w-full sm:w-auto">
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-outline mb-1 mx-1">Start Date</label>
                        <TextInput v-model="customRange.start" type="date" class="!py-2" />
                    </div>
                    <div class="w-full sm:w-auto">
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-outline mb-1 mx-1">End Date</label>
                        <TextInput v-model="customRange.end" type="date" class="!py-2" />
                    </div>
                    <PrimaryButton @click="applyCustomRange" class="w-full sm:w-auto !py-2.5 !px-6">
                        Apply Range
                    </PrimaryButton>
                </div>
            </Transition>
        </div>

        <!-- Sales Table (Bento Style) -->
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/20 overflow-hidden shadow-sm transition-colors">
            <div class="p-6 pb-2">
                <h2 class="text-lg font-headline font-bold text-on-background">Recent Transactions</h2>
            </div>
            
            <div class="w-full px-6">
                <!-- Table Header -->
                <div class="grid grid-cols-12 gap-4 pb-3 border-b border-surface-container-high text-[11px] font-label font-bold text-on-surface-variant uppercase tracking-wider">
                    <div class="col-span-3 sm:col-span-2">Date</div>
                    <div class="col-span-4 sm:col-span-3">Invoice Number</div>
                    <div class="col-span-5 sm:col-span-5">Customer Name</div>
                    <div class="hidden sm:block col-span-2 text-right">Amount</div>
                </div>

                <!-- Table Rows -->
                <div class="flex flex-col gap-1 py-4 mb-2">
                    <div 
                        v-for="(sale, index) in sales" 
                        :key="sale.id"
                        class="grid grid-cols-12 gap-4 items-center p-3 rounded-lg hover:bg-surface-container-high/50 transition-colors cursor-pointer group"
                        :class="{ 'bg-surface-container-low/30': index % 2 !== 0 }"
                    >
                        <div class="col-span-3 sm:col-span-2 text-sm font-body text-on-surface">{{ sale.date }}</div>
                        <div class="col-span-4 sm:col-span-3 flex items-center gap-2 text-sm font-body text-primary font-bold">
                            <span class="material-symbols-outlined text-[16px] text-primary/40 group-hover:text-primary transition-colors">receipt</span>
                            {{ sale.invoice_number }}
                        </div>
                        <div class="col-span-5 sm:col-span-5 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-secondary-container flex items-center justify-center text-on-secondary-container text-[10px] font-bold font-headline shrink-0">
                                {{ getInitials(sale.customer_name) }}
                            </div>
                            <span class="text-sm font-body text-on-background font-medium truncate">{{ sale.customer_name }}</span>
                            <!-- Show amount on mobile inside this column -->
                            <span class="sm:hidden text-xs font-bold text-on-background ml-auto">{{ formatCurrency(sale.amount) }}</span>
                        </div>
                        <div class="hidden sm:block col-span-2 text-right text-sm font-body text-on-background font-bold">
                            {{ formatCurrency(sale.amount) }}
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="sales.length === 0" class="p-12 text-center text-outline">
                No transactions found for the selected filter.
            </div>

            <div class="p-4 border-t border-surface-container-high bg-surface-container-low/20 flex justify-center">
                <button class="text-primary text-xs font-label font-bold uppercase tracking-widest hover:underline flex items-center gap-1">
                    View full history
                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                </button>
            </div>
        </div>

        <!-- Summary Section -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4">
            <div class="md:col-span-2 bg-gradient-to-br from-primary to-primary-container rounded-2xl p-8 text-on-primary shadow-xl relative overflow-hidden group">
                <div class="absolute -top-12 -right-12 w-48 h-48 bg-white/10 rounded-full blur-2xl group-hover:scale-110 transition-transform duration-700"></div>
                <div class="relative z-10 flex flex-col justify-between h-full">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h3 class="text-on-primary-container/80 font-label text-xs uppercase tracking-widest font-bold mb-1">Total Sales</h3>
                            <p class="text-on-primary/70 text-[10px] font-body">Selected Period Overview</p>
                        </div>
                        <div class="p-3 bg-white/20 rounded-xl backdrop-blur-md">
                            <span class="material-symbols-outlined text-on-primary" style="font-variation-settings: 'FILL' 1;">trending_up</span>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-4 mt-auto">
                        <span class="text-4xl sm:text-5xl font-headline font-extrabold tracking-tighter">{{ formatCurrency(total_sales_period) }}</span>
                        <span class="text-[10px] font-bold text-on-primary-container bg-white/10 px-2 py-1 rounded-md backdrop-blur-sm uppercase">Real-time Data</span>
                    </div>
                </div>
            </div>

            <div class="bg-surface-container-lowest rounded-2xl p-6 border border-outline-variant/20 shadow-sm flex flex-col justify-between transition-colors">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-on-surface-variant font-label text-[10px] uppercase font-bold tracking-widest">Monthly Target</h3>
                    <span class="material-symbols-outlined text-outline text-[20px]">account_balance_wallet</span>
                </div>
                <div>
                    <div class="text-3xl font-headline font-bold text-on-background mb-1">{{ formatCurrency(total_sales_month) }}</div>
                    <div class="text-xs font-body text-tertiary font-bold flex items-center gap-1 uppercase tracking-tight">
                        <span class="w-1.5 h-1.5 rounded-full bg-tertiary"></span>
                        Full Month Progress
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Sale Side Drawer -->
        <SideModal 
            :show="showAddModal" 
            title="New Sale" 
            @close="showAddModal = false"
        >
            <form @submit.prevent="submit" class="space-y-6">
                <FormField label="Customer Name" :error="form.errors.customer_name" required>
                    <TextInput v-model="form.customer_name" placeholder="e.g. Acme Corp" autofocus />
                </FormField>

                <FormField label="Invoice Number" :error="form.errors.invoice_number" required>
                    <TextInput v-model="form.invoice_number" placeholder="INV-2024-001" />
                </FormField>

                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Date" :error="form.errors.date" required>
                        <TextInput v-model="form.date" type="date" />
                    </FormField>

                    <FormField label="Amount" :error="form.errors.amount" required>
                        <TextInput v-model="form.amount" type="number" step="0.01" prefix="$" placeholder="0.00" />
                    </FormField>
                </div>

                <!-- Footer in Sidebar style -->
                <div class="pt-6 flex justify-end gap-3 mt-8">
                    <SecondaryButton @click="showAddModal = false" type="button">Cancel</SecondaryButton>
                    <PrimaryButton :loading="form.processing" :disabled="form.processing">Save Transaction</PrimaryButton>
                </div>
            </form>
        </SideModal>
    </div>
</template>

<style>
.font-headline { font-family: 'Manrope', sans-serif; }
.font-label { font-family: 'Inter', sans-serif; }
</style>
