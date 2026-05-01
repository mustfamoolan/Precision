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
const activeSection = ref('transactions');
const chequeTab = ref('incoming');
const isChequeModalOpen = ref(false);
const isExpenseModalOpen = ref(false);
const isReceiveModalOpen = ref(false);
const isAdjustmentModalOpen = ref(false);
const isAddBankModalOpen = ref(false);
const selectedCheque = ref(null);

// Filters
const bankFilter = ref('all');
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

const adjustmentForm = useForm({
    bank_id: '',
    amount: '',
    type: 'deposit', // 'deposit' or 'withdrawal'
    description: '',
    date: new Date().toISOString().substr(0, 10),
});

const bankForm = useForm({
    name: '',
    balance: 0,
});

// Pagination config
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
const paginatedTransactions = computed(() => filteredTransactions.value.slice((txPage.value - 1) * txPerPage.value, txPage.value * txPerPage.value));

const cashPage = ref(1);
const cashPerPage = ref(10);
const cashTotalPages = computed(() => Math.max(1, Math.ceil(props.cash_log.length / cashPerPage.value)));
const paginatedCashLog = computed(() => props.cash_log.slice((cashPage.value - 1) * cashPerPage.value, cashPage.value * cashPerPage.value));

const chequePage = ref(1);
const chequePerPage = ref(10);
const activeCheques = computed(() => chequeTab.value === 'incoming' ? props.incoming_cheques : props.outgoing_cheques);
const chequeTotalPages = computed(() => Math.max(1, Math.ceil(activeCheques.value.length / chequePerPage.value)));
const paginatedCheques = computed(() => activeCheques.value.slice((chequePage.value - 1) * chequePerPage.value, chequePage.value * chequePerPage.value));

// Actions
const openAdjustmentModal = (bank = null) => {
    adjustmentForm.reset();
    if (bank) adjustmentForm.bank_id = bank.id;
    isAdjustmentModalOpen.value = true;
};

const openAddBankModal = () => {
    bankForm.reset();
    isAddBankModalOpen.value = true;
};

const submitAdjustment = () => {
    adjustmentForm.post('/banks/adjust', {
        onSuccess: () => {
            isAdjustmentModalOpen.value = false;
        }
    });
};

const submitBank = () => {
    bankForm.post('/banks', {
        onSuccess: () => {
            isAddBankModalOpen.value = false;
        }
    });
};

const submitCheque = () => chequeForm.post('/cheques', { onSuccess: () => isChequeModalOpen.value = false });
const submitBankExpense = () => bankExpenseForm.post('/banks/expense', { onSuccess: () => isExpenseModalOpen.value = false });
const submitReceive = () => receiveForm.post(`/cheques/${selectedCheque.value.id}/receive`, { onSuccess: () => isReceiveModalOpen.value = false });

const formatDate = (date) => new Date(date).toLocaleDateString('en-AE', { day: '2-digit', month: 'short', year: 'numeric' });
const formatPrice = (amount) => new Intl.NumberFormat('en-AE', { style: 'currency', currency: 'AED' }).format(amount || 0);

function pageRange(current, total) {
    const delta = 2;
    const range = [];
    for (let i = Math.max(1, current - delta); i <= Math.min(total, current + delta); i++) range.push(i);
    return range;
}
</script>

<template>
    <Head title="Bank System" />

    <div class="min-h-screen bg-[#f8fafc] pb-20 px-4 sm:px-6 lg:px-8">
        <!-- Modern Header -->
        <div class="py-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tight">Financial Hub</h1>
                <p class="mt-2 text-slate-500 font-medium">Global transaction tracking and cash management</p>
            </div>
            <div class="flex items-center gap-3">
                <button 
                    @click="openAddBankModal"
                    class="bg-white text-slate-700 px-6 py-3 rounded-2xl font-bold border border-slate-200 shadow-sm hover:bg-slate-50 transition-all flex items-center gap-2 active:scale-95"
                >
                    <span class="material-symbols-outlined text-xl">add_business</span>
                    New Account
                </button>
                <button 
                    @click="isChequeModalOpen = true"
                    class="bg-indigo-600 text-white px-6 py-3 rounded-2xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition-all flex items-center gap-2 active:scale-95"
                >
                    <span class="material-symbols-outlined text-xl">add_card</span>
                    New Cheque
                </button>
            </div>
        </div>

        <!-- Accounts Overview (3 Cards) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <!-- Bank Cards -->
            <div v-for="bank in [bank1, bank2, cash]" :key="bank?.id" class="group relative overflow-hidden bg-white/70 backdrop-blur-xl rounded-[2.5rem] p-8 border border-white shadow-xl shadow-slate-200/50 transition-all hover:-translate-y-1 hover:shadow-2xl">
                <div class="relative z-10 flex flex-col h-full">
                    <div class="flex items-center justify-between mb-8">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-inner" :class="bank?.name === 'Cash' ? 'bg-emerald-50 text-emerald-600' : 'bg-indigo-50 text-indigo-600'">
                            <span class="material-symbols-outlined text-3xl">{{ bank?.name === 'Cash' ? 'payments' : 'account_balance' }}</span>
                        </div>
                        <div class="flex gap-2">
                            <button @click="openAdjustmentModal(bank)" class="w-8 h-8 rounded-lg bg-white border border-slate-100 flex items-center justify-center text-slate-400 hover:text-indigo-600 hover:border-indigo-100 transition-all" title="Adjust Balance">
                                <span class="material-symbols-outlined text-[18px]">currency_exchange</span>
                            </button>
                            <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest" :class="bank?.name === 'Cash' ? 'bg-emerald-50 text-emerald-600' : 'bg-indigo-50 text-indigo-600'">
                                {{ bank?.name || 'Account' }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Available Balance</p>
                        <h2 class="text-3xl lg:text-4xl font-black text-slate-900 tracking-tighter">{{ formatPrice(bank?.balance) }}</h2>
                    </div>
                    <div class="mt-6 flex gap-2">
                        <button @click="openAdjustmentModal(bank)" class="text-[10px] font-black uppercase tracking-widest text-indigo-600 hover:underline">Manual Entry</button>
                        <span class="text-slate-300">•</span>
                        <button @click="isExpenseModalOpen = true; bankExpenseForm.bank_id = bank.id" class="text-[10px] font-black uppercase tracking-widest text-rose-500 hover:underline">Record Outflow</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Workspace -->
        <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            <!-- Tabs -->
            <div class="flex items-center px-10 border-b border-slate-100 bg-slate-50/30">
                <button v-for="tab in [{id:'transactions', label:'Transactions Log'}, {id:'cheques', label:'Cheques Management'}, {id:'cash_log', label:'Cash Ledger'}]" 
                    :key="tab.id"
                    @click="activeSection = tab.id"
                    class="px-8 py-6 text-xs font-black uppercase tracking-[0.2em] transition-all border-b-4 relative"
                    :class="activeSection === tab.id ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                >
                    {{ tab.label }}
                    <div v-if="activeSection === tab.id" class="absolute -bottom-1 left-0 right-0 h-1 bg-indigo-600 rounded-full blur-[2px]"></div>
                </button>
            </div>

            <div class="p-10">
                <!-- Transactions Tab -->
                <div v-if="activeSection === 'transactions'" class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
                        <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight shrink-0">Master Movement Log</h3>
                        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                            <select v-model="bankFilter" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-600 outline-none">
                                <option value="all">All Accounts</option>
                                <option v-for="bank in banks" :key="bank.id" :value="bank.name">{{ bank.name }}</option>
                            </select>
                            <input type="date" v-model="dateFrom" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-xs font-bold text-slate-600 outline-none" />
                            <input type="date" v-model="dateTo" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-xs font-bold text-slate-600 outline-none" />
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-100 overflow-hidden">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-lg font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                                    <th class="py-6 px-8">Date</th>
                                    <th class="py-6 px-8">Details</th>
                                    <th class="py-6 px-8">Impact</th>
                                    <th class="py-6 px-8 text-right">Account</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="tx in paginatedTransactions" :key="tx.id" class="group hover:bg-slate-50/50 transition-colors">
                                    <td class="py-6 px-8 text-xl font-bold text-slate-900">{{ formatDate(tx.date) }}</td>
                                    <td class="py-6 px-8">
                                        <div class="text-xl font-black text-slate-900">{{ tx.description }}</div>
                                        <div class="text-xs font-black text-indigo-500 uppercase tracking-widest mt-1">{{ tx.reference_type }}</div>
                                    </td>
                                    <td class="py-6 px-8 whitespace-nowrap">
                                        <div :class="tx.type === 'deposit' ? 'text-emerald-600' : 'text-rose-600'" class="text-2xl font-black">
                                            {{ tx.type === 'deposit' ? '+' : '-' }} {{ formatPrice(tx.amount) }}
                                        </div>
                                    </td>
                                    <td class="py-6 px-8 text-right">
                                        <span class="inline-block px-5 py-2 rounded-full text-base font-black uppercase tracking-widest border border-slate-100 bg-slate-50 text-slate-600">{{ tx.bank?.name || 'N/A' }}</span>
                                    </td>
                                </tr>
                                <tr v-if="filteredTransactions.length === 0">
                                    <td colspan="4" class="py-20 text-center italic text-slate-400 font-bold">No entries found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Cheques and Cash Log (Simplified for brevity, assuming standard table) -->
                <div v-if="activeSection === 'cash_log'" class="space-y-8">
                     <div class="rounded-3xl border border-slate-100 overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr class="text-lg font-black text-slate-400 uppercase tracking-widest">
                                    <th class="py-6 px-8">Date</th>
                                    <th class="py-6 px-8">Description</th>
                                    <th class="py-6 px-8 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="tx in paginatedCashLog" :key="tx.id" class="border-b border-slate-50">
                                    <td class="py-6 px-8 text-xl font-bold">{{ formatDate(tx.date) }}</td>
                                    <td class="py-6 px-8 text-xl text-slate-600 font-medium">{{ tx.description }}</td>
                                    <td class="py-6 px-8 text-right text-2xl font-black" :class="tx.type === 'deposit' ? 'text-emerald-600' : 'text-rose-600'">
                                        {{ tx.type === 'deposit' ? '+' : '-' }} {{ formatPrice(tx.amount) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                     </div>
                </div>
                <!-- ... existing cheques tab logic ... -->
            </div>
        </div>

        <!-- Modals -->
        <!-- Manual Adjustment Modal -->
        <SideModal :show="isAdjustmentModalOpen" title="Manual Balance Adjustment" @close="isAdjustmentModalOpen = false">
            <form @submit.prevent="submitAdjustment" class="space-y-6 p-2">
                <FormField label="Target Account" required>
                    <SelectInput v-model="adjustmentForm.bank_id" :options="banks.map(b => ({label: b.name + ' (' + formatPrice(b.balance) + ')', value: b.id}))" />
                </FormField>
                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Adjustment Type" required>
                        <SelectInput v-model="adjustmentForm.type" :options="[{label:'Deposit (+)', value:'deposit'}, {label:'Withdrawal (-)', value:'withdrawal'}]" />
                    </FormField>
                    <FormField label="Date" required>
                        <TextInput v-model="adjustmentForm.date" type="date" />
                    </FormField>
                </div>
                <FormField label="Amount (AED)" required :error="adjustmentForm.errors.amount">
                    <TextInput v-model="adjustmentForm.amount" type="number" step="0.01" />
                </FormField>
                <FormField label="Description / Reason" required :error="adjustmentForm.errors.description">
                    <TextArea v-model="adjustmentForm.description" placeholder="e.g. Opening Balance, Cash Top-up, Correction..." />
                </FormField>

                <div class="pt-6 flex justify-end gap-3 border-t">
                    <SecondaryButton @click="isAdjustmentModalOpen = false" type="button">Cancel</SecondaryButton>
                    <PrimaryButton :loading="adjustmentForm.processing" class="!bg-indigo-600">Submit Adjustment</PrimaryButton>
                </div>
            </form>
        </SideModal>

        <!-- Add Bank Modal -->
        <SideModal :show="isAddBankModalOpen" title="Register New Account" @close="isAddBankModalOpen = false">
            <form @submit.prevent="submitBank" class="space-y-6 p-2">
                <FormField label="Account / Bank Name" required :error="bankForm.errors.name">
                    <TextInput v-model="bankForm.name" placeholder="e.g. Emirates NBD, Office Safe" />
                </FormField>
                <FormField label="Initial / Opening Balance (AED)" required :error="bankForm.errors.balance">
                    <TextInput v-model="bankForm.balance" type="number" step="0.01" />
                </FormField>

                <div class="pt-6 flex justify-end gap-3 border-t">
                    <SecondaryButton @click="isAddBankModalOpen = false" type="button">Cancel</SecondaryButton>
                    <PrimaryButton :loading="bankForm.processing">Create Account</PrimaryButton>
                </div>
            </form>
        </SideModal>

        <!-- Other existing modals (Expense, Cheque) -->
        <SideModal :show="isExpenseModalOpen" title="Record Outflow" @close="isExpenseModalOpen = false">
             <form @submit.prevent="submitBankExpense" class="space-y-6 p-2">
                <FormField label="Source Account" required><SelectInput v-model="bankExpenseForm.bank_id" :options="banks.map(b => ({label: b.name, value: b.id}))" /></FormField>
                <FormField label="Responsible Personnel" required><SelectInput v-model="bankExpenseForm.employee_id" :options="employees.map(e => ({label: e.name, value: e.id}))" /></FormField>
                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Amount" required><TextInput v-model="bankExpenseForm.amount" type="number" /></FormField>
                    <FormField label="Date" required><TextInput v-model="bankExpenseForm.date" type="date" /></FormField>
                </div>
                <FormField label="Description" required><TextArea v-model="bankExpenseForm.description" /></FormField>
                <div class="pt-6 flex justify-end gap-3 border-t"><SecondaryButton @click="isExpenseModalOpen = false" type="button">Cancel</SecondaryButton><PrimaryButton :loading="bankExpenseForm.processing" class="!bg-rose-600">Confirm Outflow</PrimaryButton></div>
             </form>
        </SideModal>

        <SideModal :show="isChequeModalOpen" title="Register Cheque" @close="isChequeModalOpen = false">
            <form @submit.prevent="submitCheque" class="space-y-6 p-2">
                <FormField label="Cheque Number" required><TextInput v-model="chequeForm.cheque_number" /></FormField>
                <FormField label="Party Name" required><TextInput v-model="chequeForm.party_name" /></FormField>
                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Amount" required><TextInput v-model="chequeForm.amount" type="number" /></FormField>
                    <FormField label="Due Date" required><TextInput v-model="chequeForm.due_date" type="date" /></FormField>
                </div>
                <div class="pt-6 flex justify-end gap-3 border-t"><SecondaryButton @click="isChequeModalOpen = false" type="button">Cancel</SecondaryButton><PrimaryButton :loading="chequeForm.processing">Save Cheque</PrimaryButton></div>
            </form>
        </SideModal>
    </div>
</template>

<style scoped>
.font-black { font-weight: 900; }
.tracking-tight { letter-spacing: -0.025em; }
.backdrop-blur-xl { backdrop-filter: blur(24px); }
</style>
