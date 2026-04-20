<script setup>
import { ref, computed } from 'vue';
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
    pending_cheques: Array,
    received_cheques: Array,
    bank_expenses: Array,
});

// State
const activeSection = ref('cheques'); // 'cheques', 'expenses'
const isChequeModalOpen = ref(false);
const isExpenseModalOpen = ref(false);
const isReceiveModalOpen = ref(false);
const selectedCheque = ref(null);

// Forms
const chequeForm = useForm({
    cheque_number: '',
    party_name: '',
    amount: '',
    due_date: new Date().toISOString().substr(0, 10),
});

const bankExpenseForm = useForm({
    bank_id: '',
    amount: '',
    description: '',
    date: new Date().toISOString().substr(0, 10),
});

const receiveForm = useForm({
    bank_id: '',
});

// Actions
const openChequeModal = () => {
    chequeForm.reset();
    isChequeModalOpen.value = true;
};

const openExpenseModal = () => {
    bankExpenseForm.reset();
    if (props.banks.length > 0) bankExpenseForm.bank_id = props.banks[0].id;
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

const deleteCheque = (id) => {
    if (confirm('Are you sure you want to delete this cheque record?')) {
        router.delete(`/cheques/${id}`);
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-AE', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatPrice = (amount) => {
    return new Intl.NumberFormat('en-AE', { style: 'currency', currency: 'AED' }).format(amount);
};

// Check if cheque is due soon (<= 5 days)
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

    <div class="space-y-10 animate-in fade-in duration-700 pb-20">
        <!-- Page Header -->
        <div class="flex flex-col lg:flex-row lg:justify-between lg:items-end gap-6">
            <div class="max-w-2xl px-4 sm:px-0">
                <h1 class="font-headline text-3xl lg:text-5xl font-bold text-on-surface tracking-tight mb-2">Bank System</h1>
                <p class="font-body text-on-surface-variant text-sm lg:text-base">Consolidated management of corporate accounts, incoming cheques, and bank-direct outflows.</p>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-4 px-4 sm:px-0">
                <button 
                    @click="openChequeModal"
                    class="bg-on-surface text-surface rounded-2xl px-6 py-4 font-label font-bold text-xs uppercase tracking-widest shadow-lg flex items-center justify-center gap-2 hover:opacity-90 transition-all active:scale-95"
                >
                    <span class="material-symbols-outlined text-[20px]">payments</span>
                    New Cheque
                </button>
                <button 
                    @click="openExpenseModal"
                    class="bg-primary text-on-primary rounded-2xl px-6 py-4 font-label font-bold text-xs uppercase tracking-widest shadow-lg flex items-center justify-center gap-2 hover:opacity-90 transition-all active:scale-95"
                >
                    <span class="material-symbols-outlined text-[20px]">account_balance_wallet</span>
                    Record Outflow
                </button>
            </div>
        </div>

        <!-- Bank Balance Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 px-4 sm:px-0">
            <div 
                v-for="bank in banks" 
                :key="bank.id"
                class="relative overflow-hidden bg-surface-container-lowest rounded-3xl p-8 border border-outline-variant/10 shadow-ambient group"
            >
                <div class="relative z-10 flex flex-col h-full">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                                <span class="material-symbols-outlined">account_balance</span>
                            </div>
                            <span class="font-headline font-black text-on-surface uppercase tracking-widest text-xs">{{ bank.name }}</span>
                        </div>
                        <div class="px-3 py-1 rounded-full bg-surface-container-high text-[9px] font-black uppercase tracking-widest text-outline">Active Account</div>
                    </div>
                    
                    <div class="mt-auto">
                        <p class="text-[10px] font-black text-outline uppercase tracking-[0.2em] mb-1">Available Ledger Balance</p>
                        <h2 class="text-4xl lg:text-5xl font-headline font-black text-on-surface tracking-tighter">{{ formatPrice(bank.balance) }}</h2>
                        <div class="flex items-center gap-2 mt-4 text-[10px] font-bold text-primary">
                            <span class="material-symbols-outlined text-sm">verified</span>
                            <span>Synchronized with central vault</span>
                        </div>
                    </div>
                </div>

                <!-- Abstract graphic background -->
                <div class="absolute -right-8 -bottom-8 w-48 h-48 bg-primary/5 rounded-full blur-3xl group-hover:bg-primary/10 transition-all duration-700"></div>
            </div>
        </div>

        <!-- Management Hub -->
        <div class="bg-surface-container-lowest sm:rounded-3xl shadow-ambient border-y sm:border border-outline-variant/10 overflow-hidden">
            <!-- Tabs -->
            <div class="flex border-b border-outline-variant/10 overflow-x-auto">
                <button 
                    @click="activeSection = 'cheques'"
                    class="px-8 py-5 font-label text-xs font-black uppercase tracking-widest whitespace-nowrap transition-all border-b-2"
                    :class="activeSection === 'cheques' ? 'border-primary text-primary bg-primary/[0.02]' : 'border-transparent text-outline hover:text-on-surface'"
                >
                    Incoming Cheques
                </button>
                <button 
                    @click="activeSection = 'expenses'"
                    class="px-8 py-5 font-label text-xs font-black uppercase tracking-widest whitespace-nowrap transition-all border-b-2"
                    :class="activeSection === 'expenses' ? 'border-primary text-primary bg-primary/[0.02]' : 'border-transparent text-outline hover:text-on-surface'"
                >
                    Bank Outflows (Expenses)
                </button>
            </div>

            <!-- Cheques Section -->
            <div v-if="activeSection === 'cheques'" class="divide-y divide-outline-variant/10">
                <!-- Pending List -->
                <div class="p-8">
                    <h3 class="font-headline font-black text-on-surface text-lg uppercase tracking-tight mb-6 flex items-center gap-3">
                        Pending Clearing
                        <span class="px-2 py-0.5 rounded-md bg-secondary/10 text-secondary text-[10px]">{{ pending_cheques.length }}</span>
                    </h3>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-[9px] font-black text-outline uppercase tracking-widest border-b border-outline-variant/5">
                                    <th class="py-4 pr-6">Due Date</th>
                                    <th class="py-4 px-6">Cheque #</th>
                                    <th class="py-4 px-6">Origin / Party</th>
                                    <th class="py-4 px-6 text-center">Amount</th>
                                    <th class="py-4 pl-6 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/5">
                                <tr v-for="cheque in pending_cheques" :key="cheque.id" class="group hover:bg-surface-container-low/50 transition-colors">
                                    <td class="py-5 pr-6">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-on-surface">{{ cheque.due_date }}</span>
                                            <span v-if="isDueSoon(cheque.due_date)" class="w-2 h-2 rounded-full bg-error animate-pulse shadow-[0_0_8px_rgba(186,26,26,0.5)]"></span>
                                        </div>
                                    </td>
                                    <td class="py-5 px-6 font-label font-black text-xs text-outline">{{ cheque.cheque_number }}</td>
                                    <td class="py-5 px-6 font-bold text-on-surface">{{ cheque.party_name }}</td>
                                    <td class="py-5 px-6 text-center font-headline font-black text-on-surface text-base">{{ formatPrice(cheque.amount) }}</td>
                                    <td class="py-5 pl-6 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button 
                                                @click="openReceiveModal(cheque)"
                                                class="bg-secondary/10 text-secondary py-2 px-4 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-secondary/20 transition-all active:scale-95"
                                            >
                                                Receive
                                            </button>
                                            <button @click="deleteCheque(cheque.id)" class="p-2 rounded-xl text-outline-variant hover:text-error hover:bg-error/10">
                                                <span class="material-symbols-outlined text-[20px]">delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="pending_cheques.length === 0">
                                    <td colspan="5" class="py-12 text-center text-outline text-xs italic font-medium">No pending cheques in current cycle.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recently Received -->
                <div class="p-8 bg-surface-container-low/20">
                    <h3 class="font-headline font-black text-on-surface-variant text-sm uppercase tracking-[0.2em] mb-6">Recently Cleared</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="cheque in received_cheques" :key="cheque.id" class="p-4 bg-surface-container-lowest rounded-2xl border border-outline-variant/10 flex items-center justify-between">
                            <div class="min-w-0 pr-4">
                                <p class="text-[9px] font-black text-outline uppercase tracking-widest mb-1">{{ cheque.party_name }}</p>
                                <p class="text-sm font-bold text-on-surface truncate">#{{ cheque.cheque_number }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-black text-secondary">{{ formatPrice(cheque.amount) }}</p>
                                <div class="flex items-center justify-end gap-1 text-[8px] font-black text-outline-variant uppercase">
                                    <span class="material-symbols-outlined text-[10px]">check</span>
                                    Cleared
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expenses Section -->
            <div v-if="activeSection === 'expenses'" class="divide-y divide-outline-variant/10">
                <div class="p-8">
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="font-headline font-black text-on-surface text-lg uppercase tracking-tight">Direct Ledger Outflows</h3>
                        <p class="text-xs text-outline font-medium">History of payments debited directly from bank accounts.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-[9px] font-black text-outline uppercase tracking-widest border-b border-outline-variant/5">
                                    <th class="py-4 pr-6">Date</th>
                                    <th class="py-4 px-6">Source Bank</th>
                                    <th class="py-4 px-6">Description</th>
                                    <th class="py-4 pl-6 text-right">Debit Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/5">
                                <tr v-for="expense in bank_expenses" :key="expense.id" class="hover:bg-surface-container-low/50 transition-colors">
                                    <td class="py-5 pr-6 font-bold text-on-surface">{{ expense.date }}</td>
                                    <td class="py-5 px-6">
                                        <span class="px-3 py-1 rounded-lg bg-primary/5 text-primary text-[10px] font-black uppercase tracking-widest">{{ expense.bank?.name }}</span>
                                    </td>
                                    <td class="py-5 px-6 text-on-surface-variant text-sm font-medium">{{ expense.description }}</td>
                                    <td class="py-5 pl-6 text-right font-headline font-black text-error text-base">{{ formatPrice(expense.amount) }}</td>
                                </tr>
                                <tr v-if="bank_expenses.length === 0">
                                    <td colspan="4" class="py-20 text-center">
                                        <div class="flex flex-col items-center gap-3 text-outline">
                                            <span class="material-symbols-outlined text-4xl">account_balance_wallet</span>
                                            <p class="text-xs font-medium">No recorded bank expenses.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODALS -->

        <!-- ADD CHEQUE MODAL -->
        <SideModal :show="isChequeModalOpen" title="New Incoming Cheque" @close="isChequeModalOpen = false">
            <div class="space-y-6">
                <FormField label="Cheque Number" :error="chequeForm.errors.cheque_number" required>
                    <TextInput v-model="chequeForm.cheque_number" placeholder="Enter cheque ID" autofocus />
                </FormField>
                <FormField label="Origin / Party Name" :error="chequeForm.errors.party_name" required>
                    <TextInput v-model="chequeForm.party_name" placeholder="Who issued this cheque?" />
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
                <PrimaryButton @click="submitCheque" :loading="chequeForm.processing">Register Cheque</PrimaryButton>
            </template>
        </SideModal>

        <!-- RECEIVE CHEQUE MODAL -->
        <SideModal :show="isReceiveModalOpen" title="Deposit Cheque" @close="isReceiveModalOpen = false" maxWidth="sm:w-[420px]">
            <div class="space-y-8">
                <div class="text-center p-6 bg-secondary/5 rounded-3xl border border-secondary/10">
                    <p class="text-[9px] font-black text-secondary uppercase tracking-[0.2em] mb-2">Clearing Protocol</p>
                    <h4 class="text-2xl font-headline font-black text-on-surface">{{ formatPrice(selectedCheque?.amount) }}</h4>
                    <p class="text-xs font-bold text-outline mt-1">{{ selectedCheque?.party_name }}</p>
                </div>

                <FormField label="Deposit into Account" :error="receiveForm.errors.bank_id" required>
                    <SelectInput 
                        v-model="receiveForm.bank_id" 
                        :options="banks.map(b => ({label: b.name + ' (Current: ' + formatPrice(b.balance) + ')', value: b.id}))" 
                    />
                </FormField>

                <div class="p-6 rounded-2xl bg-surface-container-low border border-outline-variant/10 text-center">
                    <p class="text-[10px] text-on-surface-variant leading-relaxed font-bold italic">Wait: Capital will be added to the ledger immediately upon confirmation.</p>
                </div>
            </div>
            <template #footer>
                <SecondaryButton @click="isReceiveModalOpen = false">Abort</SecondaryButton>
                <PrimaryButton @click="submitReceive" :loading="receiveForm.processing" class="bg-secondary border-secondary">Confirm Reception</PrimaryButton>
            </template>
        </SideModal>

        <!-- BANK EXPENSE MODAL -->
        <SideModal :show="isExpenseModalOpen" title="Record Bank Outflow" @close="isExpenseModalOpen = false">
            <div class="space-y-6">
                <FormField label="Source Account" :error="bankExpenseForm.errors.bank_id" required>
                    <SelectInput 
                        v-model="bankExpenseForm.bank_id" 
                        :options="banks.map(b => ({label: b.name + ' (Balance: ' + formatPrice(b.balance) + ')', value: b.id}))" 
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

                <FormField label="Voucher Description / Note" :error="bankExpenseForm.errors.description" required>
                    <TextArea v-model="bankExpenseForm.description" placeholder="e.g. Monthly Rent, Utility Payment, Vendor Settlement" rows="4" />
                </FormField>
            </div>
            <template #footer>
                <SecondaryButton @click="isExpenseModalOpen = false">Cancel</SecondaryButton>
                <PrimaryButton @click="submitBankExpense" :loading="bankExpenseForm.processing">Deduct & Record</PrimaryButton>
            </template>
        </SideModal>
    </div>
</template>

<style scoped>
.shadow-ambient {
    box-shadow: 0 20px 40px -10px rgba(25, 28, 30, 0.08);
}
.fill-1 {
    font-variation-settings: 'FILL' 1;
}
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type=number] {
  -moz-appearance: textfield;
}
</style>
