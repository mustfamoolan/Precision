<script setup>
import { ref, computed, watch } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import SideModal from '@/Components/SideModal.vue';
import FormField from '@/Components/FormField.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    expenses: Array,
    employees: Array,
    banks: Array,
    total_expenses_period: Number,
    total_expenses_month: Number,
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
    employee_id: '',
    description: '',
    amount: '',
});

const submit = () => {
    form.post('/expenses', {
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
    router.get('/expenses', { filter: filterType }, { preserveState: true, preserveScroll: true });
};

const applyCustomRange = () => {
    if (customRange.value.start && customRange.value.end) {
        router.get('/expenses', { 
            filter: 'custom', 
            start_date: customRange.value.start, 
            end_date: customRange.value.end 
        }, { preserveState: true, preserveScroll: true });
    }
};

const resetFilters = () => {
    search.value = '';
    showCustomRange.value = false;
    router.get('/expenses', {}, { preserveState: true, preserveScroll: true });
};

const handleSearch = () => {
    router.get('/expenses', { 
        search: search.value,
        filter: props.filters.filter,
        start_date: props.filters.start_date,
        end_date: props.filters.end_date
    }, { preserveState: true, preserveScroll: true });
};

const employeeOptions = computed(() => 
    props.employees.map(e => ({ label: e.name, value: e.id }))
);

const getInitials = (name) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().substr(0, 2);
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
};
</script>

<template>
    <Head title="Expenses Management" />

    <div class="space-y-8 animate-in fade-in duration-500">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-4">
            <div class="space-y-2 max-w-xl">
                <h1 class="text-3xl sm:text-4xl font-headline font-extrabold tracking-tight text-on-background">Expenses Management</h1>
                <p class="text-on-surface-variant font-body text-base">Track organizational spending and manage employee reimbursements effortlessly.</p>
            </div>
            
            <PrimaryButton @click="showAddModal = true" class="w-fit flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">add</span>
                Log Expense
            </PrimaryButton>
        </div>

        <!-- Filters & Search Bar -->
        <div class="flex flex-col gap-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-surface-container-lowest p-2 rounded-xl border border-outline-variant/10 shadow-sm transition-colors">
                <div class="flex bg-surface-container-low rounded-lg p-1">
                    <button 
                        @click="applyFilter('week')"
                        class="px-4 py-2 text-sm font-label font-medium rounded-md transition-colors"
                        :class="[filters.filter === 'week' ? 'bg-surface-container-lowest text-tertiary shadow-sm font-bold' : 'text-on-surface-variant hover:text-on-surface']"
                    >
                        This Week
                    </button>
                    <button 
                        @click="applyFilter('month')"
                        class="px-4 py-2 text-sm font-label font-medium rounded-md transition-colors"
                        :class="[filters.filter === 'month' ? 'bg-surface-container-lowest text-tertiary shadow-sm font-bold' : 'text-on-surface-variant hover:text-on-surface']"
                    >
                        This Month
                    </button>
                    <button 
                        @click="applyFilter('custom')"
                        class="px-4 py-2 text-sm font-label font-medium rounded-md transition-colors flex items-center gap-1"
                        :class="[showCustomRange || filters.filter === 'custom' ? 'bg-surface-container-lowest text-tertiary shadow-sm font-bold' : 'text-on-surface-variant hover:text-on-surface']"
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
                            placeholder="Search expenses..." 
                            class="w-full pl-10 pr-4 py-2 bg-surface-container-high rounded-lg border border-outline-variant/10 focus:outline-none focus:border-tertiary focus:ring-4 focus:ring-tertiary/10 text-sm font-body transition-all"
                        />
                    </div>
                    <button @click="resetFilters" class="p-2 text-outline hover:text-error transition-colors" title="Clear All Filters">
                        <span class="material-symbols-outlined">filter_alt_off</span>
                    </button>
                </div>
            </div>

            <!-- Custom Range Inputs -->
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="transform -translate-y-2 opacity-0"
                enter-to-class="transform translate-y-0 opacity-100"
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
                    <PrimaryButton @click="applyCustomRange" class="w-full sm:w-auto !py-2.5 !px-6 bg-tertiary border-tertiary hover:bg-tertiary/90">
                        Apply Range
                    </PrimaryButton>
                </div>
            </Transition>
        </div>

        <!-- Expenses Table -->
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/20 overflow-hidden shadow-sm transition-colors">
            <div class="p-6 pb-2">
                <h2 class="text-lg font-headline font-bold text-on-background">Activity Stream</h2>
            </div>
            
            <div class="w-full px-6">
                <!-- Table Header -->
                <div class="grid grid-cols-12 gap-4 pb-3 border-b border-surface-container-high text-[11px] font-label font-bold text-on-surface-variant uppercase tracking-wider">
                    <div class="col-span-3 sm:col-span-2">Date</div>
                    <div class="col-span-5 sm:col-span-4">Employee</div>
                    <div class="hidden sm:block col-span-4">Description</div>
                    <div class="col-span-4 sm:col-span-2 text-right">Amount</div>
                </div>

                <!-- Table Rows -->
                <div class="flex flex-col gap-1 py-4 mb-2">
                    <div 
                        v-for="(expense, index) in expenses" 
                        :key="expense.id"
                        class="grid grid-cols-12 gap-4 items-center p-3 rounded-lg hover:bg-surface-container-high/50 transition-colors cursor-pointer group"
                        :class="{ 'bg-surface-container-low/30': index % 2 !== 0 }"
                    >
                        <div class="col-span-3 sm:col-span-2 text-sm font-body text-on-surface">{{ expense.date }}</div>
                        <div class="col-span-5 sm:col-span-4 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-tertiary-container flex items-center justify-center text-on-tertiary-container text-[10px] font-bold font-headline shrink-0">
                                {{ getInitials(expense.employee.name) }}
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="text-sm font-body text-on-background font-bold truncate">{{ expense.employee.name }}</span>
                                <span class="sm:hidden text-[10px] text-outline truncate">{{ expense.description }}</span>
                            </div>
                        </div>
                        <div class="hidden sm:block col-span-4 text-sm font-body text-on-surface-variant truncate">
                            {{ expense.description }}
                        </div>
                        <div class="col-span-4 sm:col-span-2 text-right text-sm font-body text-error font-bold">
                            -{{ formatCurrency(expense.amount) }}
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="expenses.length === 0" class="p-12 text-center text-outline">
                No expenses found for the selected filter.
            </div>
        </div>

        <!-- Summary Section -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4">
            <div class="md:col-span-2 bg-gradient-to-br from-tertiary to-[#ff6b3d] rounded-2xl p-8 text-on-tertiary shadow-xl relative overflow-hidden group">
                <div class="absolute -top-12 -right-12 w-48 h-48 bg-white/10 rounded-full blur-2xl group-hover:scale-110 transition-transform duration-700"></div>
                <div class="relative z-10 flex flex-col justify-between h-full">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h3 class="text-on-tertiary-container font-label text-xs uppercase tracking-widest font-bold mb-1">Total Expenses</h3>
                            <p class="text-white/70 text-[10px] font-body">Spending for the selected period</p>
                        </div>
                        <div class="p-3 bg-white/20 rounded-xl backdrop-blur-md">
                            <span class="material-symbols-outlined text-on-tertiary" style="font-variation-settings: 'FILL' 1;">trending_down</span>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-4 mt-auto">
                        <span class="text-4xl sm:text-5xl font-headline font-extrabold tracking-tighter">{{ formatCurrency(total_expenses_period) }}</span>
                        <span class="text-[10px] font-bold bg-white/10 px-2 py-1 rounded-md backdrop-blur-sm uppercase">Expenditure Logged</span>
                    </div>
                </div>
            </div>

            <div class="bg-surface-container-lowest rounded-2xl p-6 border border-outline-variant/20 shadow-sm flex flex-col justify-between transition-colors">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-on-surface-variant font-label text-[10px] uppercase font-bold tracking-widest">Monthly Totals</h3>
                    <span class="material-symbols-outlined text-outline text-[20px]">payments</span>
                </div>
                <div>
                    <div class="text-3xl font-headline font-bold text-on-background mb-1">{{ formatCurrency(total_expenses_month) }}</div>
                    <div class="text-xs font-body text-outline font-bold flex items-center gap-1 uppercase tracking-tight">
                        <span class="w-1.5 h-1.5 rounded-full bg-outline"></span>
                        Current Month Spending
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Expense Side Drawer -->
        <SideModal 
            :show="showAddModal" 
            title="Log Expense" 
            @close="showAddModal = false"
        >
            <form @submit.prevent="submit" class="space-y-6">
                <FormField label="Employee" :error="form.errors.employee_id" required>
                    <SelectInput v-model="form.employee_id" :options="employeeOptions" placeholder="Select employee..." />
                </FormField>

                <FormField label="Description" :error="form.errors.description" required>
                    <TextInput v-model="form.description" placeholder="e.g. Office Supplies, Fuel, Rent" />
                </FormField>

                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Date" :error="form.errors.date" required>
                        <TextInput v-model="form.date" type="date" />
                    </FormField>

                    <FormField label="Amount" :error="form.errors.amount" required>
                        <TextInput v-model="form.amount" type="number" step="0.01" prefix="$" placeholder="0.00" />
                    </FormField>
                </div>

                <!-- Footer -->
                <div class="pt-6 flex justify-end gap-3 mt-8">
                    <SecondaryButton @click="showAddModal = false" type="button">Cancel</SecondaryButton>
                    <PrimaryButton :loading="form.processing" :disabled="form.processing" class="bg-tertiary border-tertiary hover:bg-tertiary/90">
                        Save Expense
                    </PrimaryButton>
                </div>
            </form>
        </SideModal>
    </div>
</template>
