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
    expenses: Array,
    employees: Array,
    banks: Array,
    summary: Object,
    filters: Object,
});

const showAddModal = ref(false);
const showFilters = ref(false);
const search = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category || 'All');

const form = useForm({
    date: new Date().toISOString().substr(0, 10),
    employee_id: '',
    expense_number: '',
    description: '',
    category: 'Office',
    supplier_person: '',
    amount: '',
    payment_method: 'Cash',
    status: 'Paid',
    bank_id: '',
});

const submit = () => {
    form.post('/expenses', {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset();
        },
    });
};

const handleSearch = () => {
    router.get('/expenses', { 
        search: search.value,
        category: selectedCategory.value,
        filter: props.filters.filter
    }, { preserveState: true, preserveScroll: true });
};

watch(selectedCategory, () => handleSearch());

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-AE', { style: 'currency', currency: 'AED' }).format(value);
};

const getCategoryVariant = (cat) => {
    switch (cat) {
        case 'Shipping': return 'primary';
        case 'Office': return 'success';
        case 'Transport': return 'orange';
        case 'Salary': return 'purple';
        case 'Bank': return 'info';
        default: return 'neutral';
    }
};

const getStatusVariant = (status) => {
    switch (status) {
        case 'Paid': return 'success';
        case 'Partial': return 'warning';
        case 'Unpaid': return 'error';
        default: return 'neutral';
    }
};

const categories = ['All', 'Office', 'Shipping', 'Transport', 'Salary', 'Bank', 'Other'];
const paymentMethods = ['Cash', 'Bank Transfer'];
const statuses = ['Paid', 'Partial', 'Unpaid'];
</script>

<template>
    <Head title="Expenses" />

    <div class="space-y-6 animate-in fade-in duration-500">
        <!-- Breadcrumbs / Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-headline font-bold text-on-surface tracking-tight">Expenses</h1>
                <p class="text-sm text-outline font-label">Track and manage all company expenses</p>
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

        <!-- KPI Cards (Top Rows) -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <!-- Card 1 -->
            <div class="bg-surface-container-lowest border border-outline-variant/20 p-5 rounded-2xl shadow-sm">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 bg-primary/10 rounded-xl">
                        <span class="material-symbols-outlined text-primary">account_balance_wallet</span>
                    </div>
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-outline uppercase tracking-widest">Total Expenses</p>
                    <h3 class="text-xl font-headline font-black text-on-surface">{{ formatCurrency(summary.total) }}</h3>
                    <p class="text-[10px] text-emerald-500 font-bold flex items-center gap-1">
                        <span class="material-symbols-outlined text-[12px]">trending_up</span>
                        8.3% from last week
                    </p>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="bg-surface-container-lowest border border-outline-variant/20 p-5 rounded-2xl shadow-sm">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 bg-emerald-500/10 rounded-xl">
                        <span class="material-symbols-outlined text-emerald-500">business</span>
                    </div>
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-outline uppercase tracking-widest">Office Expenses</p>
                    <h3 class="text-xl font-headline font-black text-on-surface">{{ formatCurrency(summary.office) }}</h3>
                    <p class="text-[10px] text-outline font-bold">45.7% of total</p>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="bg-surface-container-lowest border border-outline-variant/20 p-5 rounded-2xl shadow-sm">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 bg-purple-500/10 rounded-xl">
                        <span class="material-symbols-outlined text-purple-500">local_shipping</span>
                    </div>
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-outline uppercase tracking-widest">Shipping Expenses</p>
                    <h3 class="text-xl font-headline font-black text-on-surface">{{ formatCurrency(summary.shipping) }}</h3>
                    <p class="text-[10px] text-outline font-bold">40.2% of total</p>
                </div>
            </div>

            <!-- Card 4 -->
            <div class="bg-surface-container-lowest border border-outline-variant/20 p-5 rounded-2xl shadow-sm">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 bg-orange-500/10 rounded-xl">
                        <span class="material-symbols-outlined text-orange-500">groups</span>
                    </div>
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-outline uppercase tracking-widest">Employee Expenses</p>
                    <h3 class="text-xl font-headline font-black text-on-surface">{{ formatCurrency(summary.employee) }}</h3>
                    <p class="text-[10px] text-outline font-bold">14.1% of total</p>
                </div>
            </div>

            <!-- Card 5 -->
            <div class="bg-surface-container-lowest border border-outline-variant/20 p-5 rounded-2xl shadow-sm">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 bg-sky-500/10 rounded-xl">
                        <span class="material-symbols-outlined text-sky-500">assignment</span>
                    </div>
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-outline uppercase tracking-widest">This Month</p>
                    <h3 class="text-xl font-headline font-black text-on-surface">{{ summary.this_month_count }}</h3>
                    <p class="text-[10px] text-outline font-bold">Total Expenses</p>
                </div>
            </div>
        </div>

        <!-- Table & Filters Container -->
        <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl shadow-sm overflow-hidden transition-all">
            <!-- Toolbar -->
            <div class="p-4 border-b border-outline-variant/20 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-2">
                    <SelectInput v-model="form.filter" :options="[{label: 'This Month', value: 'month'}]" class="!w-32 !py-1.5" />
                    <SelectInput v-model="selectedCategory" :options="categories.map(c => ({label: c, value: c}))" class="!w-40 !py-1.5" />
                </div>
                
                <div class="flex flex-1 max-w-md relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                    <input 
                        v-model="search"
                        @keyup.enter="handleSearch"
                        type="text" 
                        placeholder="Search expense, supplier, note..." 
                        class="w-full pl-10 pr-4 py-2 bg-surface-container-low rounded-xl border border-outline-variant/20 focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm font-label transition-all"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <button @click="showFilters = !showFilters" class="p-2 border border-outline-variant/20 rounded-lg hover:bg-surface-container-high transition-colors flex items-center gap-2 text-sm font-bold">
                        <span class="material-symbols-outlined text-[18px]">tune</span>
                        Filters
                    </button>
                    <PrimaryButton @click="showAddModal = true" class="flex items-center gap-2 !py-2">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        Add Expense
                    </PrimaryButton>
                    <button class="p-2 border border-outline-variant/20 rounded-lg hover:bg-surface-container-high transition-colors flex items-center gap-2 text-sm font-bold">
                        <span class="material-symbols-outlined text-[18px]">ios_share</span>
                        Export
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low/50 text-[11px] font-bold text-outline uppercase tracking-wider">
                            <th class="px-6 py-4">Date <span class="material-symbols-outlined text-[14px] align-middle">unfold_more</span></th>
                            <th class="px-6 py-4">Expense #</th>
                            <th class="px-6 py-4">Description</th>
                            <th class="px-6 py-4">Category</th>
                            <th class="px-6 py-4">Supplier / Person</th>
                            <th class="px-6 py-4">Amount (AED)</th>
                            <th class="px-6 py-4">Payment Method</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <tr v-for="expense in expenses" :key="expense.id" class="hover:bg-surface-container-low/30 transition-colors group">
                            <td class="px-6 py-4 text-xs font-bold text-on-surface-variant">{{ expense.date }}</td>
                            <td class="px-6 py-4 text-xs font-bold text-on-surface">{{ expense.expense_number || 'EXP-' + (1000 + expense.id) }}</td>
                            <td class="px-6 py-4 text-xs text-on-surface-variant">{{ expense.description }}</td>
                            <td class="px-6 py-4">
                                <Badge :variant="getCategoryVariant(expense.category)">{{ expense.category }}</Badge>
                            </td>
                            <td class="px-6 py-4 text-xs font-bold text-on-surface">{{ expense.supplier_person || expense.employee?.name }}</td>
                            <td class="px-6 py-4 text-xs font-black text-on-surface">{{ formatCurrency(expense.amount).replace('AED', '') }}</td>
                            <td class="px-6 py-4">
                                <span class="text-[10px] font-bold text-primary px-3 py-1 bg-primary/5 rounded-md border border-primary/10">
                                    {{ expense.payment_method }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <Badge :variant="getStatusVariant(expense.status)">{{ expense.status }}</Badge>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button class="p-1.5 text-outline hover:text-primary transition-colors"><span class="material-symbols-outlined text-[18px]">visibility</span></button>
                                    <button class="p-1.5 text-outline hover:text-emerald-500 transition-colors"><span class="material-symbols-outlined text-[18px]">edit</span></button>
                                    <button class="p-1.5 text-outline hover:text-error transition-colors"><span class="material-symbols-outlined text-[18px]">delete</span></button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="expenses.length === 0">
                            <td colspan="9" class="px-6 py-12 text-center text-outline italic text-sm">No expenses found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-4 bg-surface-container-low/30 flex justify-between items-center border-t border-outline-variant/20">
                <p class="text-[10px] font-bold text-outline uppercase tracking-widest">Showing 1 to {{ expenses.length }} of {{ expenses.length }} expenses</p>
                <div class="flex items-center gap-2">
                    <button class="p-1 border border-outline-variant/30 rounded-md disabled:opacity-30" disabled><span class="material-symbols-outlined text-[18px]">chevron_left</span></button>
                    <button class="px-3 py-1 bg-primary text-on-primary text-xs font-bold rounded-md">1</button>
                    <button class="px-3 py-1 text-xs font-bold text-on-surface hover:bg-surface-container-high rounded-md">2</button>
                    <button class="p-1 border border-outline-variant/30 rounded-md"><span class="material-symbols-outlined text-[18px]">chevron_right</span></button>
                    
                    <div class="ml-4 flex items-center gap-2">
                        <span class="text-[10px] font-bold text-outline">10 / page</span>
                        <span class="material-symbols-outlined text-[16px] text-outline">expand_more</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts (Simulated for Now) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-surface-container-lowest border border-outline-variant/20 p-6 rounded-2xl shadow-sm">
                <h3 class="text-sm font-headline font-bold text-on-surface mb-6">Expenses by Category</h3>
                <div class="flex items-center justify-center py-8">
                    <div class="w-48 h-48 rounded-full border-[16px] border-emerald-500 relative flex items-center justify-center">
                        <div class="text-center">
                            <p class="text-[10px] font-bold text-outline uppercase">AED</p>
                            <p class="text-xl font-headline font-black text-on-surface">9,200</p>
                            <p class="text-[10px] font-bold text-outline">Total</p>
                        </div>
                    </div>
                </div>
                <div class="space-y-2 mt-4">
                    <div class="flex justify-between items-center text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span class="text-outline font-medium">Office</span>
                        </div>
                        <div class="font-bold">AED 4,200 <span class="text-outline text-[10px] ml-1">45.7%</span></div>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-primary"></span>
                            <span class="text-outline font-medium">Shipping</span>
                        </div>
                        <div class="font-bold">AED 3,700 <span class="text-outline text-[10px] ml-1">40.2%</span></div>
                    </div>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/20 p-6 rounded-2xl shadow-sm md:col-span-2">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-sm font-headline font-bold text-on-surface">Monthly Trend</h3>
                    <SelectInput :options="[{label: 'This Year', value: 'year'}]" class="!w-24 !py-1 !text-[10px]" />
                </div>
                <div class="h-64 flex items-end justify-between gap-2 pb-6 px-4 relative">
                    <!-- Simple CSS Line Chart Simulation -->
                    <div v-for="h in [40, 60, 45, 75, 55, 80]" :key="h" class="flex-1 bg-primary/10 rounded-t-lg relative group transition-all hover:bg-primary/20" :style="{height: h + '%'}">
                        <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-on-surface text-surface text-[10px] px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity">AED {{ h }}k</div>
                    </div>
                    <div class="absolute bottom-0 left-0 w-full flex justify-between px-4 text-[10px] font-bold text-outline uppercase tracking-tight">
                        <span>Nov</span><span>Dec</span><span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Expense Modal -->
        <SideModal :show="showAddModal" title="Add New Expense" @close="showAddModal = false">
            <form @submit.prevent="submit" class="space-y-5 p-2">
                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Date" :error="form.errors.date" required>
                        <TextInput v-model="form.date" type="date" />
                    </FormField>
                    <FormField label="Expense #" :error="form.errors.expense_number">
                        <TextInput v-model="form.expense_number" placeholder="EXP-1000" />
                    </FormField>
                </div>

                <FormField label="Description" :error="form.errors.description" required>
                    <TextInput v-model="form.description" placeholder="e.g. Monthly Rent, Office Supplies" />
                </FormField>

                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Category" :error="form.errors.category" required>
                        <SelectInput v-model="form.category" :options="categories.filter(c => c !== 'All').map(c => ({label: c, value: c}))" />
                    </FormField>
                    <FormField label="Supplier / Person" :error="form.errors.supplier_person">
                        <TextInput v-model="form.supplier_person" placeholder="e.g. Amazon, Employee Name" />
                    </FormField>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Amount (AED)" :error="form.errors.amount" required>
                        <TextInput v-model="form.amount" type="number" step="0.01" prefix="AED" placeholder="0.00" />
                    </FormField>
                    <FormField label="Employee Responsible" :error="form.errors.employee_id" required>
                        <SelectInput v-model="form.employee_id" :options="employees.map(e => ({label: e.name, value: e.id}))" />
                    </FormField>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Payment Method" :error="form.errors.payment_method" required>
                        <SelectInput v-model="form.payment_method" :options="paymentMethods.map(m => ({label: m, value: m}))" />
                    </FormField>
                    <FormField label="Status" :error="form.errors.status" required>
                        <SelectInput v-model="form.status" :options="statuses.map(s => ({label: s, value: s}))" />
                    </FormField>
                </div>

                <FormField label="Bank Account (Optional)" :error="form.errors.bank_id">
                    <SelectInput v-model="form.bank_id" :options="[{label: 'None', value: ''}, ...banks.map(b => ({label: b.name, value: b.id}))]" />
                </FormField>

                <div class="pt-6 flex justify-end gap-3 border-t border-outline-variant/10 mt-6">
                    <SecondaryButton @click="showAddModal = false" type="button">Cancel</SecondaryButton>
                    <PrimaryButton :loading="form.processing" :disabled="form.processing">
                        Create Expense
                    </PrimaryButton>
                </div>
            </form>
        </SideModal>
    </div>
</template>

<style scoped>
.font-black { font-weight: 900; }
</style>
