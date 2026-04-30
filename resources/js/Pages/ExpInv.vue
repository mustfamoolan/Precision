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
    banks: Array,
    customers: {
        type: Array,
        default: () => []
    },
    local_invoices: {
        type: Array,
        default: () => []
    }
});

const showAddModal = ref(false);
const search = ref(props.filters.search || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');
const selectedStatus = ref(props.filters.status || 'all');

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
    bank_id: '',
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
        start_date: startDate.value,
        end_date: endDate.value,
        status: selectedStatus.value,
    }, { preserveState: true, preserveScroll: true });
};

watch(selectedStatus, () => handleSearch());

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-AE', { style: 'currency', currency: 'AED' }).format(value || 0);
};

const shippingStatuses = ['On Board', 'In Transit', 'Delivered'];
const statuses = [{label: 'All Status', value: 'all'}, {label: 'Paid', value: 'paid'}, {label: 'Partial', value: 'partial'}, {label: 'Pending', value: 'pending'}];
</script>

<template>
    <Head title="Export Invoices" />

    <div class="min-h-screen bg-[#f8fafc] pb-20 px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="py-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tight">Export Invoices</h1>
                <p class="mt-1 text-slate-500 font-medium">Manage export container sales</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Date range -->
                <div class="flex items-center gap-2 bg-white border border-slate-200 rounded-2xl px-4 py-2.5 shadow-sm">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">From</span>
                    <input type="date" v-model="startDate" class="text-xs font-bold text-slate-600 outline-none bg-transparent cursor-pointer" />
                </div>
                <div class="flex items-center gap-2 bg-white border border-slate-200 rounded-2xl px-4 py-2.5 shadow-sm">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">To</span>
                    <input type="date" v-model="endDate" class="text-xs font-bold text-slate-600 outline-none bg-transparent cursor-pointer" />
                </div>
                <button @click="handleSearch"
                    class="px-5 py-2.5 bg-indigo-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition-all active:scale-95"
                >Apply</button>
                <button @click="() => {startDate = ''; endDate = ''; handleSearch();}" 
                    class="px-3 py-2.5 bg-rose-50 text-rose-600 border border-rose-100 rounded-2xl hover:bg-rose-100 transition-all" title="Clear Dates"
                >
                    <span class="material-symbols-outlined text-sm block leading-none">close</span>
                </button>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-6 mb-12">
            <!-- Total Export Sales -->
            <div class="group relative overflow-hidden bg-white/80 backdrop-blur-xl rounded-[2.5rem] p-8 border border-white shadow-xl shadow-slate-200/50 transition-all hover:-translate-y-1 hover:shadow-2xl">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-600">
                            <span class="material-symbols-outlined text-3xl">public</span>
                        </div>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Sales</p>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tighter">{{ formatCurrency(summary.total_amount) }}</h2>
                </div>
            </div>

            <!-- Paid Amount -->
            <div class="group relative overflow-hidden bg-white/80 backdrop-blur-xl rounded-[2.5rem] p-8 border border-white shadow-xl shadow-slate-200/50 transition-all hover:-translate-y-1 hover:shadow-2xl">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                            <span class="material-symbols-outlined text-3xl">account_balance</span>
                        </div>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Paid Amount</p>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tighter">{{ formatCurrency(summary.total_paid) }}</h2>
                </div>
            </div>

            <!-- Pending Amount -->
            <div class="group relative overflow-hidden bg-white/80 backdrop-blur-xl rounded-[2.5rem] p-8 border border-white shadow-xl shadow-slate-200/50 transition-all hover:-translate-y-1 hover:shadow-2xl">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-14 h-14 rounded-2xl bg-orange-50 flex items-center justify-center text-orange-600">
                            <span class="material-symbols-outlined text-3xl">schedule</span>
                        </div>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Pending</p>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tighter">{{ formatCurrency(summary.total_pending) }}</h2>
                </div>
            </div>

            <!-- Overdue Amount -->
            <div class="group relative overflow-hidden bg-white/80 backdrop-blur-xl rounded-[2.5rem] p-8 border border-white shadow-xl shadow-slate-200/50 transition-all hover:-translate-y-1 hover:shadow-2xl">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-14 h-14 rounded-2xl bg-rose-50 flex items-center justify-center text-rose-600">
                            <span class="material-symbols-outlined text-3xl">warning</span>
                        </div>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Overdue</p>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tighter">{{ formatCurrency(summary.total_overdue) }}</h2>
                </div>
            </div>

            <!-- Total Invoices -->
            <div class="group relative overflow-hidden bg-white/80 backdrop-blur-xl rounded-[2.5rem] p-8 border border-white shadow-xl shadow-slate-200/50 transition-all hover:-translate-y-1 hover:shadow-2xl">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                            <span class="material-symbols-outlined text-3xl">description</span>
                        </div>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Invoices</p>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tighter">{{ summary.total_count }}</h2>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-slate-100 overflow-hidden flex flex-col">
            <!-- Toolbar -->
            <div class="p-8 border-b border-slate-100 flex flex-wrap xl:flex-nowrap justify-between items-center gap-4">
                
                <div class="flex items-center gap-4 w-full xl:w-auto">
                    <div>
                        <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight">Export Records</h3>
                        <p class="text-sm text-slate-400 font-medium mt-0.5">{{ sales.length }} records found</p>
                    </div>

                    <div class="h-8 w-px bg-slate-200 mx-2 hidden sm:block"></div>

                    <select v-model="selectedStatus" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-xs font-bold text-slate-600 outline-none focus:ring-2 focus:ring-indigo-400 cursor-pointer w-40">
                        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                </div>
                
                <div class="flex flex-1 max-w-md relative">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                    <input 
                        v-model="search"
                        @keyup.enter="handleSearch"
                        type="text" 
                        placeholder="Search invoice, customer, container..." 
                        class="w-full pl-12 pr-4 py-3 bg-slate-50 rounded-2xl border border-slate-200 focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-400 text-sm font-medium transition-all"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <button @click="showAddModal = true" class="flex items-center gap-2 px-6 py-3 bg-indigo-50 text-indigo-600 border border-indigo-100 rounded-2xl text-xs font-black uppercase tracking-widest shadow-sm hover:bg-indigo-100 transition-all active:scale-95">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        Add EXP Invoice
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 text-lg font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                            <th class="py-6 px-8">Invoice #</th>
                            <th class="py-6 px-8">Invoice Date</th>
                            <th class="py-6 px-8">Customer</th>
                            <th class="py-6 px-8">Container / Shipment</th>
                            <th class="py-6 px-8">Total Amount</th>
                            <th class="py-6 px-8">Paid Amount</th>
                            <th class="py-6 px-8 text-right">Remaining Due</th>
                            <th class="py-6 px-8">Status</th>
                            <th class="py-6 px-8 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="sale in sales" :key="sale.id" class="group hover:bg-slate-50/50 transition-colors">
                            <td class="py-6 px-8 text-xl font-bold text-slate-900 whitespace-nowrap">{{ sale.invoice_number }}</td>
                            <td class="py-6 px-8 text-xl font-bold text-slate-500 whitespace-nowrap">{{ sale.date }}</td>
                            <td class="py-6 px-8 text-xl font-medium text-slate-600 whitespace-nowrap">{{ sale.customer_name }}</td>
                            <td class="py-6 px-8 whitespace-nowrap">
                                <div class="flex items-center gap-2 text-xl font-bold text-slate-600" v-if="sale.container_number">
                                    <span class="material-symbols-outlined text-[18px] text-indigo-400">directions_boat</span>
                                    {{ sale.container_number }}
                                </div>
                                <span v-else class="text-base text-slate-400 uppercase tracking-widest">N/A</span>
                            </td>
                            <td class="py-6 px-8 text-2xl font-black text-slate-900 whitespace-nowrap">{{ formatCurrency(sale.amount).replace('AED', '') }}</td>
                            <td class="py-6 px-8 text-2xl font-bold text-emerald-600 whitespace-nowrap">{{ formatCurrency(sale.paid_amount).replace('AED', '') }}</td>
                            <td class="py-6 px-8 text-2xl font-bold text-right whitespace-nowrap" :class="sale.due_amount > 0 ? 'text-rose-600' : 'text-slate-300'">
                                {{ formatCurrency(sale.due_amount).replace('AED', '') }}
                            </td>
                            <td class="py-6 px-8 whitespace-nowrap">
                                <span class="text-base font-black uppercase tracking-widest px-4 py-2 rounded-md"
                                      :class="{
                                          'bg-emerald-50 text-emerald-600': sale.status === 'paid',
                                          'bg-orange-50 text-orange-600': sale.status === 'partial',
                                          'bg-rose-50 text-rose-600': sale.status === 'pending' || sale.status === 'unpaid'
                                      }">
                                    {{ sale.status }}
                                </span>
                            </td>
                            <td class="py-5 px-8 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click="router.delete(`/sales/${sale.id}`)" class="w-8 h-8 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all shadow-sm" title="Delete">
                                        <span class="material-symbols-outlined text-[16px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="sales.length === 0">
                            <td colspan="9" class="py-20 text-center text-slate-400 italic text-sm">
                                <span class="material-symbols-outlined text-4xl block mb-2 opacity-50">search_off</span>
                                No export invoices found for the selected filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
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
                        <div class="relative">
                            <input 
                                list="local-invoices-list" 
                                v-model="form.invoice_number" 
                                placeholder="Select or type..." 
                                class="w-full bg-slate-50 text-slate-900 placeholder:text-slate-400 text-sm font-medium rounded-xl border border-slate-200 px-4 py-2.5 outline-none focus:ring-2 focus:ring-indigo-400 focus:bg-white transition-all"
                            />
                            <datalist id="local-invoices-list">
                                <option v-for="inv in local_invoices" :key="inv.id" :value="inv.invoice_number"></option>
                            </datalist>
                        </div>
                    </FormField>
                </div>

                <FormField label="Customer Name" :error="form.errors.customer_name" required>
                    <SelectInput 
                        v-model="form.customer_name" 
                        :options="customers.map(c => ({label: c.name, value: c.name}))" 
                        placeholder="Select Customer..." 
                    />
                </FormField>

                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Container / Shipment # (Optional)" :error="form.errors.container_number">
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

                <FormField label="Bank/Account (Optional)" :error="form.errors.bank_id">
                    <SelectInput 
                        v-model="form.bank_id" 
                        :options="[{label: 'None / Cash', value: ''}, ...banks.map(b => ({ label: b.name, value: b.id }))]" 
                    />
                </FormField>

                <div class="pt-6 flex justify-end gap-3 border-t border-slate-100 mt-6">
                    <SecondaryButton @click="showAddModal = false" type="button">Cancel</SecondaryButton>
                    <PrimaryButton :loading="form.processing" :disabled="form.processing">
                        Create EXP Invoice
                    </PrimaryButton>
                </div>
            </form>
        </SideModal>
    </div>
</template>

<style scoped>
.font-black { font-weight: 900; }
.tracking-tighter { letter-spacing: -0.05em; }
</style>
