<script setup>
import { ref, computed, watch } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import SideModal from '@/Components/SideModal.vue';
import FormField from '@/Components/FormField.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import Badge from '@/Components/Badge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    sales: Array,
    summary: Object,
    filters: Object,
});

const showAddModal = ref(false);
const search = ref(props.filters.search || '');

const form = useForm({
    date: new Date().toISOString().substr(0, 10),
    invoice_number: '',
    customer_name: '',
    amount: '',
    type: 'export',
    items_count: 1,
    paid_amount: '',
    container_number: '',
    shipping_status: 'On Board',
});

const submit = () => {
    form.post('/sales', {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset();
        },
    });
};

const handleSearch = () => {
    router.get('/sales', { 
        search: search.value,
        type: 'export',
        filter: props.filters.filter
    }, { preserveState: true, preserveScroll: true });
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

const getShippingVariant = (status) => {
    switch (status) {
        case 'Delivered': return 'success';
        case 'In Transit': return 'orange';
        case 'On Board': return 'info';
        default: return 'neutral';
    }
};

const shippingStatuses = ['On Board', 'In Transit', 'Delivered'];
</script>

<template>
    <Head title="Export Invoices" />

    <div class="space-y-6 animate-in fade-in duration-500">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-headline font-bold text-on-surface tracking-tight">EXP INV</h1>
                <p class="text-sm text-outline font-label">Manage export invoices (Container sales)</p>
            </div>
            <div class="flex items-center gap-3">
                <button class="bg-surface-container-low border border-outline-variant/30 px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 hover:bg-surface-container-high transition-colors">
                    <span class="material-symbols-outlined text-[18px]">calendar_today</span>
                    Apr 28, 2024
                </button>
                <button class="bg-surface-container-low border border-outline-variant/30 p-2 rounded-lg relative">
                    <span class="material-symbols-outlined">notifications</span>
                    <span class="absolute top-1 right-1 w-2 h-2 bg-error rounded-full"></span>
                </button>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-surface-container-lowest border border-outline-variant/20 p-5 rounded-2xl shadow-sm">
                <div class="p-3 bg-purple-500/10 rounded-xl w-fit mb-4">
                    <span class="material-symbols-outlined text-purple-500">public</span>
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-outline uppercase tracking-widest">Total Export Sales</p>
                    <h3 class="text-xl font-headline font-black text-on-surface">{{ formatCurrency(summary.total_amount) }}</h3>
                    <p class="text-[10px] text-emerald-500 font-bold flex items-center gap-1">
                        <span class="material-symbols-outlined text-[12px]">trending_up</span>
                        18.6% from last month
                    </p>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/20 p-5 rounded-2xl shadow-sm">
                <div class="p-3 bg-emerald-500/10 rounded-xl w-fit mb-4">
                    <span class="material-symbols-outlined text-emerald-500">account_balance</span>
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-outline uppercase tracking-widest">Paid Amount</p>
                    <h3 class="text-xl font-headline font-black text-on-surface">{{ formatCurrency(summary.total_paid) }}</h3>
                    <p class="text-[10px] text-outline font-bold">63.8% of total</p>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/20 p-5 rounded-2xl shadow-sm">
                <div class="p-3 bg-orange-500/10 rounded-xl w-fit mb-4">
                    <span class="material-symbols-outlined text-orange-500">schedule</span>
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-outline uppercase tracking-widest">Pending Amount</p>
                    <h3 class="text-xl font-headline font-black text-on-surface">{{ formatCurrency(summary.total_pending) }}</h3>
                    <p class="text-[10px] text-outline font-bold">29.6% of total</p>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/20 p-5 rounded-2xl shadow-sm">
                <div class="p-3 bg-error/10 rounded-xl w-fit mb-4">
                    <span class="material-symbols-outlined text-error">warning</span>
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-outline uppercase tracking-widest">Overdue Amount</p>
                    <h3 class="text-xl font-headline font-black text-on-surface">{{ formatCurrency(summary.total_overdue) }}</h3>
                    <p class="text-[10px] text-outline font-bold">6.6% of total</p>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/20 p-5 rounded-2xl shadow-sm">
                <div class="p-3 bg-primary/10 rounded-xl w-fit mb-4">
                    <span class="material-symbols-outlined text-primary">description</span>
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-outline uppercase tracking-widest">Total Invoices</p>
                    <h3 class="text-xl font-headline font-black text-on-surface">{{ summary.total_count }}</h3>
                    <p class="text-[10px] text-outline font-bold">This Month</p>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-outline-variant/20 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-2">
                    <SelectInput :options="[{label: 'Apr 1, 2024 - Apr 28, 2024', value: 'range'}]" class="!w-56 !py-1.5" />
                    <SelectInput :options="[{label: 'All Customers', value: 'all'}]" class="!w-40 !py-1.5" />
                    <SelectInput :options="[{label: 'All Status', value: 'all'}]" class="!w-32 !py-1.5" />
                </div>
                
                <div class="flex flex-1 max-w-md relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                    <input 
                        v-model="search"
                        @keyup.enter="handleSearch"
                        type="text" 
                        placeholder="Search invoice, customer, container..." 
                        class="w-full pl-10 pr-4 py-2 bg-surface-container-low rounded-xl border border-outline-variant/20 focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm font-label transition-all"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <PrimaryButton @click="showAddModal = true" class="flex items-center gap-2 !py-2">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        Add EXP Invoice
                    </PrimaryButton>
                    <button class="p-2 border border-outline-variant/20 rounded-lg hover:bg-surface-container-high transition-colors">
                        <span class="material-symbols-outlined text-[18px]">ios_share</span>
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low/50 text-[11px] font-bold text-outline uppercase tracking-wider">
                            <th class="px-6 py-4">Invoice #</th>
                            <th class="px-6 py-4">Invoice Date</th>
                            <th class="px-6 py-4">Customer</th>
                            <th class="px-6 py-4">Container / Shipment</th>
                            <th class="px-6 py-4">Total Amount (AED)</th>
                            <th class="px-6 py-4">Paid (AED)</th>
                            <th class="px-6 py-4">Due (AED)</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <tr v-for="sale in sales" :key="sale.id" class="hover:bg-surface-container-low/30 transition-colors group">
                            <td class="px-6 py-4 text-xs font-bold text-on-surface">{{ sale.invoice_number }}</td>
                            <td class="px-6 py-4 text-xs text-on-surface-variant">{{ sale.date }}</td>
                            <td class="px-6 py-4 text-xs font-bold text-on-surface">{{ sale.customer_name }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2 text-xs font-bold text-primary">
                                    <span class="material-symbols-outlined text-[16px]">directions_boat</span>
                                    {{ sale.container_number }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs font-black text-on-surface">{{ formatCurrency(sale.amount).replace('AED', '') }}</td>
                            <td class="px-6 py-4 text-xs font-bold text-on-surface">{{ formatCurrency(sale.paid_amount).replace('AED', '') }}</td>
                            <td class="px-6 py-4 text-xs font-bold text-on-surface">{{ formatCurrency(sale.due_amount).replace('AED', '') }}</td>
                            <td class="px-6 py-4">
                                <Badge :variant="getStatusVariant(sale.status)">{{ sale.status }}</Badge>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button class="p-1.5 text-outline hover:text-primary transition-colors"><span class="material-symbols-outlined text-[18px]">visibility</span></button>
                                    <button class="p-1.5 text-outline hover:text-emerald-500 transition-colors"><span class="material-symbols-outlined text-[18px]">print</span></button>
                                    <button class="p-1.5 text-outline hover:text-error transition-colors"><span class="material-symbols-outlined text-[18px]">more_horiz</span></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-surface-container-lowest border border-outline-variant/20 p-6 rounded-2xl shadow-sm">
                <h3 class="text-sm font-headline font-bold text-on-surface mb-6">Export Sales by Status</h3>
                <div class="flex items-center justify-center py-4">
                    <div class="w-40 h-40 rounded-full border-[12px] border-emerald-500 relative flex items-center justify-center">
                        <div class="text-center">
                            <p class="text-[10px] font-bold text-outline uppercase">AED</p>
                            <p class="text-xl font-headline font-black text-on-surface">{{ summary.total_amount }}</p>
                            <p class="text-[10px] font-bold text-outline">Total</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/20 p-6 rounded-2xl shadow-sm">
                <h3 class="text-sm font-headline font-bold text-on-surface mb-6">Top Customers (By Amount)</h3>
                <div class="space-y-4">
                    <div v-for="sale in sales.slice(0, 4)" :key="sale.id" class="space-y-1">
                        <div class="flex justify-between text-xs font-bold">
                            <span class="text-on-surface">{{ sale.customer_name }}</span>
                            <span class="text-on-surface">{{ formatCurrency(sale.amount) }}</span>
                        </div>
                        <div class="w-full bg-surface-container-high rounded-full h-1.5 overflow-hidden">
                            <div class="bg-primary h-full rounded-full" :style="{width: (sale.amount / summary.total_amount * 100) + '%'}"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/20 p-6 rounded-2xl shadow-sm">
                <h3 class="text-sm font-headline font-bold text-on-surface mb-6">Recent Shipments</h3>
                <div class="space-y-4">
                    <div v-for="sale in sales.slice(0, 4)" :key="sale.id" class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-surface-container-low flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined">directions_boat</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-on-surface truncate">{{ sale.container_number }}</p>
                            <p class="text-[10px] text-outline">{{ sale.date }}</p>
                        </div>
                        <Badge :variant="getShippingVariant(sale.shipping_status)">{{ sale.shipping_status || 'On Board' }}</Badge>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Modal -->
        <SideModal :show="showAddModal" title="Add Export Invoice" @close="showAddModal = false">
            <form @submit.prevent="submit" class="space-y-5 p-2">
                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Date" :error="form.errors.date" required>
                        <TextInput v-model="form.date" type="date" />
                    </FormField>
                    <FormField label="Invoice #" :error="form.errors.invoice_number" required>
                        <TextInput v-model="form.invoice_number" placeholder="EXP-1000" />
                    </FormField>
                </div>

                <FormField label="Customer Name" :error="form.errors.customer_name" required>
                    <TextInput v-model="form.customer_name" placeholder="Client Name" />
                </FormField>

                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Container / Shipment #" :error="form.errors.container_number" required>
                        <TextInput v-model="form.container_number" placeholder="CN-123456" />
                    </FormField>
                    <FormField label="Shipping Status" :error="form.errors.shipping_status" required>
                        <SelectInput v-model="form.shipping_status" :options="shippingStatuses.map(s => ({label: s, value: s}))" />
                    </FormField>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Total Amount (AED)" :error="form.errors.amount" required>
                        <TextInput v-model="form.amount" type="number" step="0.01" prefix="AED" placeholder="0.00" />
                    </FormField>
                    <FormField label="Paid Amount (AED)" :error="form.errors.paid_amount">
                        <TextInput v-model="form.paid_amount" type="number" step="0.01" prefix="AED" placeholder="0.00" />
                    </FormField>
                </div>

                <div class="pt-6 flex justify-end gap-3 border-t border-outline-variant/10 mt-6">
                    <SecondaryButton @click="showAddModal = false" type="button">Cancel</SecondaryButton>
                    <PrimaryButton :loading="form.processing" :disabled="form.processing">
                        Create EXP Invoice
                    </PrimaryButton>
                </div>
            </form>
        </SideModal>
    </div>
</template>
