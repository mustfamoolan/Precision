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
import Pagination from '@/Components/Pagination.vue';
import { jsPDF } from 'jspdf';

defineOptions({ layout: MainLayout });

const props = defineProps({
    expenses: Object,
    employees: Array,
    banks: Array,
    summary: Object,
    filters: Object,
});

const showAddModal = ref(false);
const search = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category || 'All');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

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
    // Determine payment method string for backend from selected bank
    const selectedBank = props.banks.find(b => b.id === form.bank_id);
    form.payment_method = selectedBank ? selectedBank.name : 'Cash';

    form.post('/expenses', {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset();
            form.date = new Date().toISOString().substr(0, 10);
            form.payment_method = 'Cash';
            form.status = 'Paid';
        },
    });
};

const confirmDelete = (id) => {
    if (confirm('Are you sure you want to delete this expense?')) {
        router.delete(`/expenses/${id}`);
    }
};

const handleSearch = () => {
    router.get('/expenses', { 
        search: search.value,
        category: selectedCategory.value,
        start_date: startDate.value,
        end_date: endDate.value
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

const exportPDF = () => {
    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
    const W = doc.internal.pageSize.getWidth();
    let y = 15;

    const addTitle = (text, size = 14, color = [30, 41, 59]) => {
        doc.setFontSize(size);
        doc.setTextColor(...color);
        doc.setFont('helvetica', 'bold');
        doc.text(text, 14, y);
        y += size * 0.6;
    };
    
    const addLine = () => { doc.setDrawColor(220, 220, 220); doc.line(14, y, W - 14, y); y += 5; };
    
    const addRow = (cols, widths, isBold = false) => {
        doc.setFontSize(9);
        doc.setFont('helvetica', isBold ? 'bold' : 'normal');
        doc.setTextColor(50, 50, 80);
        let x = 14;
        cols.forEach((col, i) => { doc.text(String(col), x, y); x += widths[i]; });
        y += 7;
        if (y > 190) { doc.addPage(); y = 20; }
    };

    // Header
    doc.setFillColor(30, 41, 59);
    doc.rect(0, 0, W, 30, 'F');
    doc.setFontSize(18);
    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'bold');
    doc.text('EXPENSES REPORT', 14, 18);
    
    doc.setFontSize(9);
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(148, 163, 184);
    if(startDate.value && endDate.value) {
        doc.text(`Period: ${startDate.value} to ${endDate.value}`, 14, 25);
    }
    doc.text(`Generated: ${new Date().toLocaleDateString('en-AE')}`, W - 55, 25);
    y = 42;

    const cols = ['Date', 'Expense #', 'Description', 'Category', 'Supplier/Person', 'Amount', 'Payment', 'Status'];
    const widths = [25, 25, 75, 25, 45, 25, 25, 25];

    addRow(cols, widths, true); 
    addLine();
    
    props.expenses.data.forEach(expense => {
        const desc = expense.description.length > 40 ? expense.description.substring(0, 37) + '...' : expense.description;
        const supp = (expense.supplier_person || expense.employee?.name || '').substring(0, 20);
        
        addRow([
            expense.date, 
            expense.expense_number || expense.id, 
            desc, 
            expense.category, 
            supp, 
            formatCurrency(expense.amount).replace('AED', '').trim(), 
            expense.payment_method, 
            expense.status
        ], widths);
    });

    doc.save(`expenses-report-${new Date().getTime()}.pdf`);
};

const categories = ['All', 'Office', 'Shipping', 'Transport', 'Salary', 'Bank', 'Other'];
const paymentMethods = ['Cash', 'Bank Transfer'];
const statuses = ['Paid', 'Partial', 'Unpaid'];
</script>

<template>
    <Head title="Expenses" />

    <div class="min-h-screen bg-[#f8fafc] pb-20 px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="py-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tight">Expenses</h1>
                <p class="mt-1 text-slate-500 font-medium">Track and manage all company expenses</p>
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

                <!-- Export PDF -->
                <button @click="exportPDF"
                    class="flex items-center gap-2 px-6 py-2.5 bg-slate-900 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl hover:bg-slate-800 transition-all active:scale-95 ml-2"
                >
                    <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                    Export PDF
                </button>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-12">
            <!-- Total Expenses -->
            <div class="group relative overflow-hidden bg-white/80 backdrop-blur-xl rounded-[2.5rem] p-8 border border-white shadow-xl shadow-slate-200/50 transition-all hover:-translate-y-1 hover:shadow-2xl">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-600">
                            <span class="material-symbols-outlined text-3xl">account_balance_wallet</span>
                        </div>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Expenses</p>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tighter">{{ formatCurrency(summary.total) }}</h2>
                </div>
            </div>

            <!-- Office Expenses -->
            <div class="group relative overflow-hidden bg-white/80 backdrop-blur-xl rounded-[2.5rem] p-8 border border-white shadow-xl shadow-slate-200/50 transition-all hover:-translate-y-1 hover:shadow-2xl">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                            <span class="material-symbols-outlined text-3xl">business</span>
                        </div>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Office</p>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tighter">{{ formatCurrency(summary.office) }}</h2>
                </div>
            </div>

            <!-- Shipping Expenses -->
            <div class="group relative overflow-hidden bg-white/80 backdrop-blur-xl rounded-[2.5rem] p-8 border border-white shadow-xl shadow-slate-200/50 transition-all hover:-translate-y-1 hover:shadow-2xl">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-14 h-14 rounded-2xl bg-purple-50 flex items-center justify-center text-purple-600">
                            <span class="material-symbols-outlined text-3xl">local_shipping</span>
                        </div>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Shipping</p>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tighter">{{ formatCurrency(summary.shipping) }}</h2>
                </div>
            </div>

            <!-- Employee Expenses -->
            <div class="group relative overflow-hidden bg-white/80 backdrop-blur-xl rounded-[2.5rem] p-8 border border-white shadow-xl shadow-slate-200/50 transition-all hover:-translate-y-1 hover:shadow-2xl">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-14 h-14 rounded-2xl bg-orange-50 flex items-center justify-center text-orange-600">
                            <span class="material-symbols-outlined text-3xl">groups</span>
                        </div>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Employee</p>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tighter">{{ formatCurrency(summary.employee) }}</h2>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-slate-100 overflow-hidden flex flex-col">
            <!-- Toolbar -->
            <div class="p-8 border-b border-slate-100 flex flex-wrap xl:flex-nowrap justify-between items-center gap-4">
                
                <div class="flex items-center gap-4 w-full xl:w-auto">
                    <div>
                        <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight">Transactions</h3>
                        <p class="text-sm text-slate-400 font-medium mt-0.5">{{ expenses.total }} records found</p>
                    </div>

                    <div class="h-8 w-px bg-slate-200 mx-2 hidden sm:block"></div>

                    <select v-model="selectedCategory" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-xs font-bold text-slate-600 outline-none focus:ring-2 focus:ring-indigo-400 cursor-pointer w-40">
                        <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                    </select>
                </div>
                
                <div class="flex flex-1 max-w-md relative">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                    <input 
                        v-model="search"
                        @keyup.enter="handleSearch"
                        type="text" 
                        placeholder="Search expense, supplier, note..." 
                        class="w-full pl-12 pr-4 py-3 bg-slate-50 rounded-2xl border border-slate-200 focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-400 text-sm font-medium transition-all"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <button @click="showAddModal = true" class="flex items-center gap-2 px-6 py-3 bg-indigo-50 text-indigo-600 border border-indigo-100 rounded-2xl text-xs font-black uppercase tracking-widest shadow-sm hover:bg-indigo-100 transition-all active:scale-95">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        Add Expense
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 text-lg font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                            <th class="py-6 px-8">Date</th>
                            <th class="py-6 px-8">Expense #</th>
                            <th class="py-6 px-8">Description</th>
                            <th class="py-6 px-8">Category</th>
                            <th class="py-6 px-8">Supplier / Person</th>
                            <th class="py-6 px-8">Amount (AED)</th>
                            <th class="py-6 px-8">Payment</th>
                            <th class="py-6 px-8 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="expense in expenses.data" :key="expense.id" class="group hover:bg-slate-50/50 transition-colors">
                            <td class="py-6 px-8 text-xl font-bold text-slate-500 whitespace-nowrap">{{ expense.date }}</td>
                            <td class="py-6 px-8 text-xl font-bold text-slate-900 whitespace-nowrap">{{ expense.expense_number || expense.id }}</td>
                            <td class="py-6 px-8 text-xl font-medium text-slate-600 min-w-[200px]">{{ expense.description }}</td>
                            <td class="py-6 px-8 whitespace-nowrap">
                                <span class="px-5 py-2 rounded-full text-base font-black uppercase tracking-widest bg-slate-100 text-slate-600">{{ expense.category }}</span>
                            </td>
                            <td class="py-6 px-8 text-xl font-bold text-slate-900 whitespace-nowrap">{{ expense.supplier_person || expense.employee?.name }}</td>
                            <td class="py-6 px-8 text-2xl font-black text-slate-900 whitespace-nowrap">{{ formatCurrency(expense.amount).replace('AED', '') }}</td>
                            <td class="py-6 px-8 whitespace-nowrap">
                                <div class="flex flex-col gap-1 items-start">
                                    <span class="text-base font-black px-3 py-1 rounded border border-slate-200 text-slate-500 uppercase tracking-wider">{{ expense.payment_method }}</span>
                                    <span class="text-base font-black uppercase" :class="expense.status === 'Paid' ? 'text-emerald-500' : 'text-rose-500'">{{ expense.status }}</span>
                                </div>
                            </td>
                            <td class="py-5 px-8 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click="confirmDelete(expense.id)" class="w-8 h-8 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all shadow-sm">
                                        <span class="material-symbols-outlined text-[16px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="expenses.data.length === 0">
                            <td colspan="8" class="py-20 text-center text-slate-400 italic text-sm">
                                <span class="material-symbols-outlined text-4xl block mb-2 opacity-50">search_off</span>
                                No expenses found for the selected filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-6 border-t border-slate-100">
                <Pagination :links="expenses.links" :meta="expenses" />
            </div>
        </div>

        <!-- Add Expense Modal -->
        <SideModal :show="showAddModal" title="Add New Expense" @close="showAddModal = false">
            <form @submit.prevent="submit" class="space-y-5 p-2">
                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Date" :error="form.errors.date" required>
                        <TextInput v-model="form.date" type="date" />
                    </FormField>
                    <div class="flex flex-col justify-center">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 block">Expense #</span>
                        <div class="px-4 py-3 bg-slate-50 border border-dashed border-slate-300 rounded-2xl text-xs font-bold text-slate-400 italic">
                            Auto-generated
                        </div>
                    </div>
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
                    <FormField label="Payment Method" :error="form.errors.bank_id" required>
                        <SelectInput 
                            v-model="form.bank_id" 
                            :options="banks.map(b => ({label: b.name, value: b.id}))" 
                            placeholder="Select Payment Source..."
                        />
                    </FormField>
                    <FormField label="Status" :error="form.errors.status" required>
                        <SelectInput v-model="form.status" :options="statuses.map(s => ({label: s, value: s}))" />
                    </FormField>
                </div>

                <div class="pt-6 flex justify-end gap-3 border-t border-slate-100 mt-6">
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
.tracking-tighter { letter-spacing: -0.05em; }
</style>
