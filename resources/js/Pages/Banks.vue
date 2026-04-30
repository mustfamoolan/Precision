<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import SideModal from '@/Components/SideModal.vue';
import FormField from '@/Components/FormField.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextArea from '@/Components/TextArea.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    banks: Array,
    bank1: Object,
    bank2: Object,
    cash: Object,
    transactions: Array,
    cash_log: Array,
    incoming_cheques: Array,
    outgoing_cheques: Array,
    received_cheques: Array,
    employees: Array,
});

// State
const activeSection = ref('transactions'); // Default: open on Transactions tab
const chequeTab = ref('incoming'); // 'incoming', 'outgoing'
const isChequeModalOpen = ref(false);
const isExpenseModalOpen = ref(false);
const isReceiveModalOpen = ref(false);
const selectedCheque = ref(null);

// Filters
const bankFilter = ref('all');
const timeFilter = ref('all');
const dateFrom = ref('');
const dateTo = ref('');

// Forms
const chequeForm = useForm({
    cheque_number: '',
    party_name: '',
    amount: '',
    due_date: new Date().toISOString().substr(0, 10),
    type: 'incoming',
});

const bankExpenseForm = useForm({
    bank_id: '',
    employee_id: '',
    amount: '',
    description: '',
    date: new Date().toISOString().substr(0, 10),
});

const receiveForm = useForm({
    bank_id: '',
});

// Pagination config
const PAGE_SIZES = [5, 10, 25, 50, 100];

// Transactions pagination
const txPage = ref(1);
const txPerPage = ref(10);
const filteredTransactions = computed(() => {
    let txs = props.transactions;
    if (bankFilter.value !== 'all') txs = txs.filter(t => t.bank?.name === bankFilter.value);
    if (dateFrom.value) txs = txs.filter(t => t.date >= dateFrom.value);
    if (dateTo.value)   txs = txs.filter(t => t.date <= dateTo.value);
    return txs;
});
const txTotalPages = computed(() => Math.max(1, Math.ceil(filteredTransactions.value.length / txPerPage.value)));
const paginatedTransactions = computed(() => {
    const start = (txPage.value - 1) * txPerPage.value;
    return filteredTransactions.value.slice(start, start + txPerPage.value);
});
watch(bankFilter, () => { txPage.value = 1; });
watch(txPerPage, () => { txPage.value = 1; });

// Cheques pagination
const chequePage = ref(1);
const chequePerPage = ref(10);
const activeCheques = computed(() => chequeTab.value === 'incoming' ? props.incoming_cheques : props.outgoing_cheques);
const chequeTotalPages = computed(() => Math.max(1, Math.ceil(activeCheques.value.length / chequePerPage.value)));
const paginatedCheques = computed(() => {
    const start = (chequePage.value - 1) * chequePerPage.value;
    return activeCheques.value.slice(start, start + chequePerPage.value);
});
watch(chequeTab, () => { chequePage.value = 1; });
watch(chequePerPage, () => { chequePage.value = 1; });

// Cash Log pagination
const cashPage = ref(1);
const cashPerPage = ref(10);
const cashTotalPages = computed(() => Math.max(1, Math.ceil(props.cash_log.length / cashPerPage.value)));
const paginatedCashLog = computed(() => {
    const start = (cashPage.value - 1) * cashPerPage.value;
    return props.cash_log.slice(start, start + cashPerPage.value);
});
watch(cashPerPage, () => { cashPage.value = 1; });

// Page range helper (shows at most 5 page buttons)
function pageRange(current, total) {
    const delta = 2;
    const range = [];
    for (let i = Math.max(1, current - delta); i <= Math.min(total, current + delta); i++) range.push(i);
    return range;
}

// Actions
const openChequeModal = (type = 'incoming') => {
    chequeForm.reset();
    chequeForm.type = type;
    isChequeModalOpen.value = true;
};

const openExpenseModal = () => {
    bankExpenseForm.reset();
    if (props.banks.length > 0) bankExpenseForm.bank_id = props.banks[0].id;
    if (props.employees.length > 0) bankExpenseForm.employee_id = props.employees[0].id;
    isExpenseModalOpen.value = true;
};

const openReceiveModal = (cheque) => {
    selectedCheque.value = cheque;
    receiveForm.reset();
    if (props.banks.length > 0) receiveForm.bank_id = props.banks[0].id;
    isReceiveModalOpen.value = true;
};

const submitCheque = () => {
    chequeForm.post('/cheques', {
        onSuccess: () => {
            isChequeModalOpen.value = false;
        }
    });
};

const submitBankExpense = () => {
    bankExpenseForm.post('/banks/expense', {
        onSuccess: () => {
            isExpenseModalOpen.value = false;
        }
    });
};

const submitReceive = () => {
    receiveForm.post(`/cheques/${selectedCheque.value.id}/receive`, {
        onSuccess: () => {
            isReceiveModalOpen.value = false;
        }
    });
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-AE', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatPrice = (amount) => {
    return new Intl.NumberFormat('en-AE', { style: 'currency', currency: 'AED' }).format(amount);
};

const isDueSoon = (dueDate) => {
    const due = new Date(dueDate);
    const today = new Date();
    const diffTime = due - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays >= 0 && diffDays <= 5;
};
</script>

<template>
    <Head title="Bank System" />

    <div class="min-h-screen bg-[#f8fafc] pb-20 px-4 sm:px-6 lg:px-8">
        <!-- Modern Header -->
        <div class="py-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tight">Bank System</h1>
                <p class="mt-2 text-slate-500 font-medium">Expert financial management & transaction tracking</p>
            </div>
            <div class="flex items-center gap-3">
                <button 
                    @click="openChequeModal('incoming')"
                    class="bg-indigo-600 text-white px-6 py-3 rounded-2xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition-all flex items-center gap-2 active:scale-95"
                >
                    <span class="material-symbols-outlined text-xl">add_card</span>
                    New Cheque
                </button>
                <button 
                    @click="openExpenseModal"
                    class="bg-white text-slate-700 px-6 py-3 rounded-2xl font-bold border border-slate-200 shadow-sm hover:bg-slate-50 transition-all flex items-center gap-2 active:scale-95"
                >
                    <span class="material-symbols-outlined text-xl">account_balance_wallet</span>
                    Record Outflow
                </button>
            </div>
        </div>

        <!-- Accounts Overview (3 Cards) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <!-- Bank 1 - Glassmorphism -->
            <div class="group relative overflow-hidden bg-white/70 backdrop-blur-xl rounded-[2.5rem] p-8 border border-white shadow-xl shadow-slate-200/50 transition-all hover:-translate-y-1 hover:shadow-2xl">
                <div class="relative z-10 flex flex-col h-full">
                    <div class="flex items-center justify-between mb-8">
                        <div class="w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 shadow-inner">
                            <span class="material-symbols-outlined text-3xl">account_balance</span>
                        </div>
                        <span class="px-4 py-1.5 rounded-full bg-indigo-50 text-indigo-600 text-[10px] font-black uppercase tracking-widest">Bank Account</span>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Bank 1 Balance</p>
                        <h2 class="text-3xl lg:text-4xl font-black text-slate-900 tracking-tighter">{{ formatPrice(bank1?.balance || 0) }}</h2>
                    </div>
                </div>
                <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-indigo-500/5 rounded-full blur-3xl group-hover:bg-indigo-500/10 transition-all duration-700"></div>
            </div>

            <!-- Bank 2 - Glassmorphism -->
            <div class="group relative overflow-hidden bg-white/70 backdrop-blur-xl rounded-[2.5rem] p-8 border border-white shadow-xl shadow-slate-200/50 transition-all hover:-translate-y-1 hover:shadow-2xl">
                <div class="relative z-10 flex flex-col h-full">
                    <div class="flex items-center justify-between mb-8">
                        <div class="w-14 h-14 rounded-2xl bg-violet-50 flex items-center justify-center text-violet-600 shadow-inner">
                            <span class="material-symbols-outlined text-3xl">account_balance</span>
                        </div>
                        <span class="px-4 py-1.5 rounded-full bg-violet-50 text-violet-600 text-[10px] font-black uppercase tracking-widest">Bank Account</span>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Bank 2 Balance</p>
                        <h2 class="text-3xl lg:text-4xl font-black text-slate-900 tracking-tighter">{{ formatPrice(bank2?.balance || 0) }}</h2>
                    </div>
                </div>
                <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-violet-500/5 rounded-full blur-3xl group-hover:bg-violet-500/10 transition-all duration-700"></div>
            </div>

            <!-- Cash - Glassmorphism -->
            <div class="group relative overflow-hidden bg-white/70 backdrop-blur-xl rounded-[2.5rem] p-8 border border-white shadow-xl shadow-slate-200/50 transition-all hover:-translate-y-1 hover:shadow-2xl">
                <div class="relative z-10 flex flex-col h-full">
                    <div class="flex items-center justify-between mb-8">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600 shadow-inner">
                            <span class="material-symbols-outlined text-3xl">payments</span>
                        </div>
                        <span class="px-4 py-1.5 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-widest">Cash Log</span>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Cash in Hand</p>
                        <h2 class="text-3xl lg:text-4xl font-black text-slate-900 tracking-tighter">{{ formatPrice(cash?.balance || 0) }}</h2>
                    </div>
                </div>
                <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-emerald-500/5 rounded-full blur-3xl group-hover:bg-emerald-500/10 transition-all duration-700"></div>
            </div>
        </div>

        <!-- Main Workspace -->
        <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            <!-- Professional Tabs -->
            <div class="flex items-center px-10 border-b border-slate-100 bg-slate-50/30">
                <button 
                    @click="activeSection = 'transactions'"
                    class="px-8 py-6 text-xs font-black uppercase tracking-[0.2em] transition-all border-b-4 relative"
                    :class="activeSection === 'transactions' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                >
                    Transactions Log
                    <div v-if="activeSection === 'transactions'" class="absolute -bottom-1 left-0 right-0 h-1 bg-indigo-600 rounded-full blur-[2px]"></div>
                </button>
                <button 
                    @click="activeSection = 'cheques'"
                    class="px-8 py-6 text-xs font-black uppercase tracking-[0.2em] transition-all border-b-4 relative"
                    :class="activeSection === 'cheques' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                >
                    Cheques Management
                    <div v-if="activeSection === 'cheques'" class="absolute -bottom-1 left-0 right-0 h-1 bg-indigo-600 rounded-full blur-[2px]"></div>
                </button>
                <button 
                    @click="activeSection = 'cash_log'"
                    class="px-8 py-6 text-xs font-black uppercase tracking-[0.2em] transition-all border-b-4 relative"
                    :class="activeSection === 'cash_log' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                >
                    Cash Log
                    <div v-if="activeSection === 'cash_log'" class="absolute -bottom-1 left-0 right-0 h-1 bg-indigo-600 rounded-full blur-[2px]"></div>
                </button>
            </div>

            <!-- Content Area -->
            <div class="p-10">
                <!-- Transactions Tab -->
                <div v-if="activeSection === 'transactions'" class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
                        <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight shrink-0">Ledger Movements</h3>
                        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                            <!-- Account filter -->
                            <select v-model="bankFilter" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-600 focus:ring-2 focus:ring-indigo-400 outline-none cursor-pointer">
                                <option value="all">All Accounts</option>
                                <option v-for="bank in banks" :key="bank.id" :value="bank.name">{{ bank.name }}</option>
                            </select>
                            <!-- Date From -->
                            <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">From</span>
                                <input type="date" v-model="dateFrom" class="bg-transparent text-xs font-bold text-slate-600 outline-none cursor-pointer" />
                            </div>
                            <!-- Date To -->
                            <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">To</span>
                                <input type="date" v-model="dateTo" class="bg-transparent text-xs font-bold text-slate-600 outline-none cursor-pointer" />
                            </div>
                            <!-- Clear filters -->
                            <button 
                                v-if="bankFilter !== 'all' || dateFrom || dateTo"
                                @click="bankFilter = 'all'; dateFrom = ''; dateTo = ''"
                                class="flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-rose-50 text-rose-600 text-[10px] font-black uppercase tracking-widest hover:bg-rose-100 transition-all active:scale-95"
                            >
                                <span class="material-symbols-outlined text-sm">filter_alt_off</span>
                                Clear
                            </button>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-100 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="sticky top-0 z-10">
                                    <tr class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                                        <th class="py-5 px-8">Date</th>
                                        <th class="py-5 px-8">Description</th>
                                        <th class="py-5 px-8">Amount</th>
                                        <th class="py-5 px-8 text-right">Destination</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr v-for="tx in paginatedTransactions" :key="tx.id" class="group hover:bg-slate-50/50 transition-colors">
                                        <td class="py-5 px-8 text-sm font-bold text-slate-900 whitespace-nowrap">{{ formatDate(tx.date) }}</td>
                                        <td class="py-5 px-8">
                                            <div class="text-sm font-black text-slate-900">{{ tx.description }}</div>
                                            <div class="text-[9px] font-black text-indigo-500 uppercase tracking-widest mt-1">{{ tx.reference_type }}</div>
                                        </td>
                                        <td class="py-5 px-8 whitespace-nowrap">
                                            <div :class="tx.type === 'deposit' ? 'text-emerald-600' : 'text-rose-600'" class="text-base font-black">
                                                {{ tx.type === 'deposit' ? '+' : '-' }} {{ formatPrice(tx.amount) }}
                                            </div>
                                        </td>
                                        <td class="py-5 px-8 text-right">
                                            <span class="inline-block px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest border"
                                                :class="{
                                                    'bg-indigo-50 text-indigo-600 border-indigo-100': tx.bank?.name === 'Bank 1',
                                                    'bg-violet-50 text-violet-600 border-violet-100': tx.bank?.name === 'Bank 2',
                                                    'bg-emerald-50 text-emerald-600 border-emerald-100': tx.bank?.name === 'Cash',
                                                    'bg-slate-50 text-slate-500 border-slate-100': !['Bank 1','Bank 2','Cash'].includes(tx.bank?.name)
                                                }"
                                            >{{ tx.bank?.name || 'N/A' }}</span>
                                        </td>
                                    </tr>
                                    <tr v-if="filteredTransactions.length === 0">
                                        <td colspan="4" class="py-20 text-center"><p class="text-sm font-bold text-slate-400 italic">No movements recorded.</p></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination: Transactions -->
                        <div v-if="txTotalPages > 1 || filteredTransactions.length > 5" class="flex items-center justify-between px-8 py-4 border-t border-slate-100 bg-slate-50/50">
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-bold text-slate-400">Rows per page:</span>
                                <select v-model.number="txPerPage" class="bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-600 outline-none focus:ring-2 focus:ring-indigo-400 cursor-pointer">
                                    <option v-for="s in PAGE_SIZES" :key="s" :value="s">{{ s }}</option>
                                </select>
                                <span class="text-xs text-slate-400">{{ filteredTransactions.length }} total</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <button @click="txPage = Math.max(1, txPage - 1)" :disabled="txPage === 1" class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:bg-slate-100 disabled:opacity-30 transition-all">&lsaquo;</button>
                                <button v-for="p in pageRange(txPage, txTotalPages)" :key="p" @click="txPage = p"
                                    class="w-8 h-8 rounded-xl text-xs font-black transition-all"
                                    :class="p === txPage ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'text-slate-500 hover:bg-slate-100'"
                                >{{ p }}</button>
                                <button @click="txPage = Math.min(txTotalPages, txPage + 1)" :disabled="txPage === txTotalPages" class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:bg-slate-100 disabled:opacity-30 transition-all">&rsaquo;</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cheques Tab -->
                <div v-if="activeSection === 'cheques'" class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <div class="flex flex-col sm:flex-row gap-6 items-center justify-between">
                        <div class="flex bg-slate-100 p-1.5 rounded-2xl w-full sm:w-auto">
                            <button 
                                @click="chequeTab = 'incoming'"
                                class="flex-1 sm:flex-none px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all"
                                :class="chequeTab === 'incoming' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                            >
                                Incoming
                            </button>
                            <button 
                                @click="chequeTab = 'outgoing'"
                                class="flex-1 sm:flex-none px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all"
                                :class="chequeTab === 'outgoing' ? 'bg-white text-rose-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                            >
                                Outgoing
                            </button>
                        </div>
                        <button @click="openChequeModal(chequeTab)" class="w-full sm:w-auto px-6 py-3 bg-slate-900 text-white rounded-2xl font-bold text-sm shadow-xl active:scale-95 transition-all">
                            + Add {{ chequeTab === 'incoming' ? 'Incoming' : 'Outgoing' }}
                        </button>
                    </div>

                    <div class="rounded-3xl border border-slate-100 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="sticky top-0 z-10">
                                    <tr class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                                        <th class="py-5 px-8">Party Name</th>
                                        <th class="py-5 px-8">Cheque #</th>
                                        <th class="py-5 px-8">Amount</th>
                                        <th class="py-5 px-8">Status</th>
                                        <th class="py-5 px-8 text-right">Due Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr v-for="cheque in paginatedCheques" :key="cheque.id" class="group hover:bg-slate-50/50 transition-colors">
                                        <td class="py-5 px-8 text-sm font-bold text-slate-600">{{ cheque.party_name }}</td>
                                        <td class="py-5 px-8 text-sm font-black text-slate-900">#{{ cheque.cheque_number }}</td>
                                        <td class="py-5 px-8 text-base font-black text-slate-900 whitespace-nowrap">{{ formatPrice(cheque.amount) }}</td>
                                        <td class="py-5 px-8">
                                            <div class="flex items-center gap-3">
                                                <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest"
                                                    :class="cheque.status === 'received' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'"
                                                >{{ cheque.status === 'received' ? 'Received' : 'Pending' }}</span>
                                                <button v-if="cheque.status === 'pending'" @click="openReceiveModal(cheque)"
                                                    class="px-4 py-2 bg-indigo-50 text-indigo-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-100 transition-all active:scale-95"
                                                >Receive</button>
                                            </div>
                                        </td>
                                        <td class="py-5 px-8 text-right">
                                            <div class="flex items-center justify-end gap-2 text-sm font-bold text-slate-600 whitespace-nowrap">
                                                {{ formatDate(cheque.due_date) }}
                                                <span v-if="cheque.status === 'pending' && isDueSoon(cheque.due_date)" class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="activeCheques.length === 0">
                                        <td colspan="5" class="py-20 text-center"><p class="text-sm font-bold text-slate-400 italic">No {{ chequeTab }} cheques found.</p></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination: Cheques -->
                        <div v-if="chequeTotalPages > 1 || activeCheques.length > 5" class="flex items-center justify-between px-8 py-4 border-t border-slate-100 bg-slate-50/50">
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-bold text-slate-400">Rows per page:</span>
                                <select v-model.number="chequePerPage" class="bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-600 outline-none focus:ring-2 focus:ring-indigo-400 cursor-pointer">
                                    <option v-for="s in PAGE_SIZES" :key="s" :value="s">{{ s }}</option>
                                </select>
                                <span class="text-xs text-slate-400">{{ activeCheques.length }} total</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <button @click="chequePage = Math.max(1, chequePage - 1)" :disabled="chequePage === 1" class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:bg-slate-100 disabled:opacity-30 transition-all">&lsaquo;</button>
                                <button v-for="p in pageRange(chequePage, chequeTotalPages)" :key="p" @click="chequePage = p"
                                    class="w-8 h-8 rounded-xl text-xs font-black transition-all"
                                    :class="p === chequePage ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'text-slate-500 hover:bg-slate-100'"
                                >{{ p }}</button>
                                <button @click="chequePage = Math.min(chequeTotalPages, chequePage + 1)" :disabled="chequePage === chequeTotalPages" class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:bg-slate-100 disabled:opacity-30 transition-all">&rsaquo;</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cash Log Tab -->
                <div v-if="activeSection === 'cash_log'" class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight">Physical Cash Flow</h3>
                    <div class="rounded-3xl border border-slate-100 overflow-hidden bg-slate-50/20">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="sticky top-0 z-10">
                                    <tr class="bg-white text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                                        <th class="py-5 px-8">Date</th>
                                        <th class="py-5 px-8">Description</th>
                                        <th class="py-5 px-8">Type</th>
                                        <th class="py-5 px-8 text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr v-for="tx in paginatedCashLog" :key="tx.id" class="hover:bg-white transition-colors">
                                        <td class="py-5 px-8 text-sm font-bold text-slate-900 whitespace-nowrap">{{ formatDate(tx.date) }}</td>
                                        <td class="py-5 px-8 text-sm font-medium text-slate-600">{{ tx.description }}</td>
                                        <td class="py-5 px-8">
                                            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest"
                                                :class="tx.type === 'deposit' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'"
                                            >{{ tx.type }}</span>
                                        </td>
                                        <td class="py-5 px-8 text-right font-black whitespace-nowrap" :class="tx.type === 'deposit' ? 'text-emerald-600' : 'text-rose-600'">
                                            {{ tx.type === 'deposit' ? '+' : '-' }} {{ formatPrice(tx.amount) }}
                                        </td>
                                    </tr>
                                    <tr v-if="cash_log.length === 0">
                                        <td colspan="4" class="py-20 text-center italic text-slate-400 font-bold">Cash vault history is empty.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination: Cash Log -->
                        <div v-if="cashTotalPages > 1 || cash_log.length > 5" class="flex items-center justify-between px-8 py-4 border-t border-slate-100 bg-white">
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-bold text-slate-400">Rows per page:</span>
                                <select v-model.number="cashPerPage" class="bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-600 outline-none focus:ring-2 focus:ring-emerald-400 cursor-pointer">
                                    <option v-for="s in PAGE_SIZES" :key="s" :value="s">{{ s }}</option>
                                </select>
                                <span class="text-xs text-slate-400">{{ cash_log.length }} total</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <button @click="cashPage = Math.max(1, cashPage - 1)" :disabled="cashPage === 1" class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:bg-slate-100 disabled:opacity-30 transition-all">&lsaquo;</button>
                                <button v-for="p in pageRange(cashPage, cashTotalPages)" :key="p" @click="cashPage = p"
                                    class="w-8 h-8 rounded-xl text-xs font-black transition-all"
                                    :class="p === cashPage ? 'bg-emerald-600 text-white shadow-md shadow-emerald-200' : 'text-slate-500 hover:bg-slate-100'"
                                >{{ p }}</button>
                                <button @click="cashPage = Math.min(cashTotalPages, cashPage + 1)" :disabled="cashPage === cashTotalPages" class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:bg-slate-100 disabled:opacity-30 transition-all">&rsaquo;</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Integration Note Card -->
        <div class="mt-12 p-8 bg-indigo-900 rounded-[2.5rem] shadow-2xl relative overflow-hidden group">
            <div class="relative z-10 flex flex-col md:flex-row items-center gap-8">
                <div class="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center text-white shrink-0">
                    <span class="material-symbols-outlined text-4xl">integration_instructions</span>
                </div>
                <div>
                    <h4 class="text-white text-xl font-black uppercase tracking-widest">Automated Financial Integration</h4>
                    <p class="text-indigo-200 mt-1 font-medium leading-relaxed">
                        Whenever a new invoice is created and marked as <span class="text-white font-bold">PAID</span>, the system will prompt for the target vault (Bank 1, Bank 2, or Cash) to automatically update balances and record the entry.
                    </p>
                </div>
            </div>
            <!-- Decorative circle -->
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/5 rounded-full group-hover:scale-150 transition-all duration-1000"></div>
        </div>

        <!-- Modals -->
        <SideModal :show="isChequeModalOpen" :title="'New ' + chequeForm.type + ' Cheque'" @close="isChequeModalOpen = false">
            <div class="space-y-6">
                <FormField label="Cheque Number" :error="chequeForm.errors.cheque_number" required>
                    <TextInput v-model="chequeForm.cheque_number" placeholder="Enter cheque serial" autofocus />
                </FormField>
                <FormField label="Party / Client Name" :error="chequeForm.errors.party_name" required>
                    <TextInput v-model="chequeForm.party_name" placeholder="Who is the counterparty?" />
                </FormField>
                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Amount (AED)" :error="chequeForm.errors.amount" required>
                        <TextInput v-model="chequeForm.amount" type="number" step="0.01" />
                    </FormField>
                    <FormField label="Due Date" :error="chequeForm.errors.due_date" required>
                        <TextInput v-model="chequeForm.due_date" type="date" />
                    </FormField>
                </div>
            </div>
            <template #footer>
                <SecondaryButton @click="isChequeModalOpen = false">Cancel</SecondaryButton>
                <PrimaryButton @click="submitCheque" :loading="chequeForm.processing" class="bg-indigo-600 shadow-indigo-100">Register Cheque</PrimaryButton>
            </template>
        </SideModal>

        <SideModal :show="isReceiveModalOpen" title="Clear Financial Instrument" @close="isReceiveModalOpen = false">
            <div class="space-y-6">
                <div class="p-6 rounded-3xl bg-slate-50 border border-slate-100 text-center">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Clearing Amount</p>
                    <h5 class="text-3xl font-black text-slate-900">{{ formatPrice(selectedCheque?.amount) }}</h5>
                    <p class="text-xs font-bold text-indigo-600 mt-1">{{ selectedCheque?.party_name }}</p>
                </div>
                <FormField label="Destination Ledger Account" :error="receiveForm.errors.bank_id" required>
                    <SelectInput 
                        v-model="receiveForm.bank_id" 
                        :options="banks.map(b => ({label: b.name + ' (Balance: ' + formatPrice(b.balance) + ')', value: b.id}))" 
                    />
                </FormField>
            </div>
            <template #footer>
                <SecondaryButton @click="isReceiveModalOpen = false">Abort</SecondaryButton>
                <PrimaryButton @click="submitReceive" :loading="receiveForm.processing" class="bg-indigo-600">Confirm Clearing</PrimaryButton>
            </template>
        </SideModal>

        <SideModal :show="isExpenseModalOpen" title="Direct Ledger Outflow" @close="isExpenseModalOpen = false">
            <div class="space-y-6">
                <FormField label="Source Account" :error="bankExpenseForm.errors.bank_id" required>
                    <SelectInput 
                        v-model="bankExpenseForm.bank_id" 
                        :options="banks.map(b => ({label: b.name + ' (Balance: ' + formatPrice(b.balance) + ')', value: b.id}))" 
                    />
                </FormField>
                <FormField label="Responsible Personnel" :error="bankExpenseForm.errors.employee_id" required>
                    <SelectInput 
                        v-model="bankExpenseForm.employee_id" 
                        :options="employees.map(e => ({label: e.name, value: e.id}))" 
                    />
                </FormField>
                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Amount (AED)" :error="bankExpenseForm.errors.amount" required>
                        <TextInput v-model="bankExpenseForm.amount" type="number" step="0.01" />
                    </FormField>
                    <FormField label="Transaction Date" :error="bankExpenseForm.errors.date" required>
                        <TextInput v-model="bankExpenseForm.date" type="date" />
                    </FormField>
                </div>
                <FormField label="Description / Voucher Note" :error="bankExpenseForm.errors.description" required>
                    <TextArea v-model="bankExpenseForm.description" placeholder="Explain the purpose of this outflow..." />
                </FormField>
            </div>
            <template #footer>
                <SecondaryButton @click="isExpenseModalOpen = false">Cancel</SecondaryButton>
                <PrimaryButton @click="submitBankExpense" :loading="bankExpenseForm.processing" class="bg-rose-600 shadow-rose-100">Deduct & Record</PrimaryButton>
            </template>
        </SideModal>
    </div>
</template>

<style scoped>
/* Glassmorphism utility */
.backdrop-blur-xl {
    backdrop-filter: blur(24px);
}
/* Premium font settings */
.tracking-tighter {
    letter-spacing: -0.05em;
}
/* Responsive adjustments */
@media (max-width: 640px) {
    .rounded-\[2\.5rem\] {
        border-radius: 1.5rem;
    }
}
</style>
