<script setup>
import { ref } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import FormField from '@/Components/FormField.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import SideModal from '@/Components/SideModal.vue';
import Badge from '@/Components/Badge.vue';

defineOptions({ layout: MainLayout });

const props = defineProps({ shipment: Object });

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-AE', { style: 'currency', currency: 'AED' }).format(value);
};
const fmtDate = d => d ? new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—';

const showItemForm = ref(false);
const itemForm = useForm({
  product_name: '',
  quantity: 1,
  cost: '',
  currency: 'USD'
});

const currencies = [
    { label: 'USD ($)', value: 'USD' },
    { label: 'AED (د.إ)', value: 'AED' },
    { label: 'IQD (ع.د)', value: 'IQD' },
];

const showPaymentForm = ref(false);
const paymentForm = useForm({
  amount: 0,
  payment_date: new Date().toISOString().split('T')[0],
  payment_method: 'Bank Transfer',
  note: ''
});

const addItem = () => itemForm.post(`/shipping/${props.shipment.id}/items`, {
  onSuccess: () => { showItemForm.value = false; itemForm.reset(); }
});

const deleteItem = id => confirm('Remove item?') && router.delete(`/shipping/items/${id}`);

const addPayment = () => paymentForm.post(`/shipping/${props.shipment.id}/payments`, {
  onSuccess: () => { showPaymentForm.value = false; paymentForm.reset(); }
});

const deletePayment = id => confirm('Remove payment?') && router.delete(`/shipping/payments/${id}`);

const getStatusColor = (s) => {
    switch (s) {
        case 'On Board': return 'text-primary bg-primary/10';
        case 'In Transit': return 'text-orange-500 bg-orange-500/10';
        case 'Delivered': return 'text-emerald-600 bg-emerald-600/10';
        case 'Completed': return 'text-outline bg-surface-container-high';
        default: return 'text-outline bg-surface-container-low';
    }
};

const exportPDF = async () => {
  const { jsPDF } = await import('jspdf');
  const doc = new jsPDF();
  let y = 15;
  const W = doc.internal.pageSize.getWidth();
  const s = props.shipment;

  doc.setFillColor(30, 41, 59); doc.rect(0, 0, W, 35, 'F');
  doc.setFontSize(18); doc.setTextColor(255, 255, 255); doc.setFont('helvetica', 'bold');
  doc.text('SHIPMENT ANALYSIS', 14, 18);
  doc.setFontSize(9); doc.setTextColor(148, 163, 184);
  doc.text(`Container: ${s.container_number}  |  Vessel: ${s.vessel_name || 'N/A'}`, 14, 26);

  y = 45;
  const row = (label, val, bold = false) => {
    doc.setFontSize(10); doc.setTextColor(100, 116, 139); doc.setFont('helvetica', 'normal'); doc.text(label, 14, y);
    doc.setTextColor(30, 41, 59); doc.setFont('helvetica', bold ? 'bold' : 'normal'); doc.text(String(val), 90, y); y += 8;
  };

  row('Supplier', s.supplier_name || '—', true);
  row('Status', s.status, true);
  row('Departure', fmtDate(s.departure_date));
  row('Arrival (ETA)', fmtDate(s.arrival_date));
  y += 5;
  row('Invoice Amount', formatCurrency(s.invoice_amount), true);
  row('Balance Due', formatCurrency(s.balance_due), true);
  
  y += 10;
  doc.setFontSize(12); doc.setTextColor(0, 0, 0); doc.text('PACKING LIST', 14, y); y += 10;
  (s.items || []).forEach(item => {
    doc.text(`${item.product_name} x ${item.quantity} (${item.cost || '0'} ${item.currency || 'USD'})`, 14, y); y += 7;
  });

  doc.save(`shipment-${s.container_number}.pdf`);
};
</script>

<template>
    <Head :title="'Shipment - ' + shipment.container_number" />

    <div class="space-y-6 animate-in fade-in duration-700">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-4">
                <Link href="/shipping" class="w-10 h-10 rounded-xl bg-surface-container-low border border-outline-variant/30 flex items-center justify-center text-outline hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                </Link>
                <div>
                    <h1 class="text-2xl font-headline font-bold text-on-surface tracking-tight">{{ shipment.container_number }}</h1>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest" :class="getStatusColor(shipment.status)">
                            {{ shipment.status }}
                        </span>
                        <p class="text-[10px] text-outline font-bold uppercase tracking-widest">Analysis Module</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button @click="exportPDF" class="bg-surface-container-lowest border border-outline-variant/30 px-6 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-surface-container-high transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span>
                    Export PDF Analysis
                </button>
            </div>
        </div>

        <!-- Financial Pulse Row -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-primary border border-primary-container p-6 rounded-2xl shadow-lg shadow-primary/10 text-on-primary">
                <div class="flex justify-between items-center mb-4">
                    <p class="text-[10px] font-bold opacity-80 uppercase tracking-widest">Balance Due</p>
                    <span class="material-symbols-outlined text-[18px] opacity-80">account_balance_wallet</span>
                </div>
                <h3 class="text-3xl font-headline font-black">{{ formatCurrency(shipment.balance_due) }}</h3>
                <div class="mt-4 pt-4 border-t border-on-primary/10 flex justify-between items-center text-[10px] font-bold">
                    <span class="opacity-70 uppercase tracking-widest">Payment Status</span>
                    <span class="px-2 py-0.5 rounded-md bg-white/10">{{ shipment.payment_status }}</span>
                </div>
            </div>

            <div v-for="fin in [
                { label: 'Invoice Total', val: formatCurrency(shipment.invoice_amount), sub: 'Supplier Cost', color: 'text-on-surface' },
                { label: 'Settled Amount', val: formatCurrency(shipment.paid_amount), sub: 'Confirmed Payments', color: 'text-emerald-600' },
                { label: 'Logistics Costs', val: formatCurrency(shipment.total_costs), sub: 'Shipping + Tax + Fees', color: 'text-primary' }
            ]" :key="fin.label" class="bg-surface-container-lowest border border-outline-variant/20 p-6 rounded-2xl shadow-sm flex flex-col justify-between">
                <div>
                    <p class="text-[10px] font-bold text-outline uppercase tracking-widest mb-1">{{ fin.label }}</p>
                    <h3 class="text-xl font-headline font-black" :class="fin.color">{{ fin.val }}</h3>
                </div>
                <p class="text-[10px] text-outline font-bold mt-2">{{ fin.sub }}</p>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Column: Packing & Payments -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Packing List -->
                <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl shadow-sm flex flex-col">
                    <div class="p-5 border-b border-outline-variant/20 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                                <span class="material-symbols-outlined text-[20px]">inventory_2</span>
                            </div>
                            <div>
                                <h3 class="text-sm font-headline font-bold text-on-surface uppercase tracking-widest">Packing List</h3>
                                <p class="text-[10px] text-outline font-bold">Contents and Quantities</p>
                            </div>
                        </div>
                        <button @click="showItemForm = true" class="w-8 h-8 rounded-lg bg-surface-container-low border border-outline-variant/30 flex items-center justify-center text-primary hover:bg-primary hover:text-on-primary transition-all">
                            <span class="material-symbols-outlined text-[18px]">add</span>
                        </button>
                    </div>
                    <div class="p-0 overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-surface-container-low/30 text-lg font-bold text-outline uppercase tracking-widest border-b border-outline-variant/20">
                                    <th class="py-5 px-6">Product Description</th>
                                    <th class="py-5 px-6">Qty</th>
                                    <th class="py-5 px-6">Cost</th>
                                    <th class="py-5 px-6">Currency</th>
                                    <th class="py-5 px-6 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/10">
                                <tr v-for="item in shipment.items" :key="item.id" class="hover:bg-surface-container-low/30 transition-colors">
                                    <td class="py-6 px-6 text-xl font-bold text-on-surface">{{ item.product_name }}</td>
                                    <td class="py-6 px-6 text-2xl font-black text-on-surface">{{ item.quantity }}</td>
                                    <td class="py-6 px-6 text-xl font-bold text-primary">{{ item.cost || '0.00' }}</td>
                                    <td class="py-6 px-6">
                                        <span class="px-4 py-1.5 rounded bg-surface-container-low text-sm font-black text-outline uppercase tracking-widest">{{ item.currency || 'USD' }}</span>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <button @click="deleteItem(item.id)" class="text-outline hover:text-error transition-colors">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!shipment.items.length">
                                    <td colspan="5" class="py-12 text-center text-outline text-xs italic">Packing list is currently empty.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Payment Milestones -->
                <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl shadow-sm flex flex-col">
                    <div class="p-5 border-b border-outline-variant/20 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-emerald-500/10 rounded-xl flex items-center justify-center text-emerald-600">
                                <span class="material-symbols-outlined text-[20px]">payments</span>
                            </div>
                            <div>
                                <h3 class="text-sm font-headline font-bold text-on-surface uppercase tracking-widest">Payment Milestones</h3>
                                <p class="text-[10px] text-outline font-bold">Transaction History</p>
                            </div>
                        </div>
                        <button @click="showPaymentForm = true" class="px-4 py-2 bg-emerald-600 text-on-primary text-[10px] font-black uppercase tracking-widest rounded-lg hover:bg-emerald-700 transition-colors">
                            New Payment
                        </button>
                    </div>
                    <div class="p-0 overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-surface-container-low/30 text-lg font-bold text-outline uppercase tracking-widest border-b border-outline-variant/20">
                                    <th class="py-5 px-6">Date</th>
                                    <th class="py-5 px-6">Amount</th>
                                    <th class="py-5 px-6">Method</th>
                                    <th class="py-5 px-6 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/10">
                                <tr v-for="pay in shipment.payments" :key="pay.id" class="hover:bg-surface-container-low/30 transition-colors">
                                    <td class="py-6 px-6 text-xl font-bold text-on-surface-variant">{{ fmtDate(pay.payment_date) }}</td>
                                    <td class="py-6 px-6 text-2xl font-black text-emerald-600">{{ formatCurrency(pay.amount) }}</td>
                                    <td class="py-6 px-6 text-sm font-bold text-outline uppercase">{{ pay.payment_method }}</td>
                                    <td class="py-4 px-6 text-right">
                                        <button @click="deletePayment(pay.id)" class="text-outline hover:text-error transition-colors">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!shipment.payments.length">
                                    <td colspan="4" class="py-12 text-center text-outline text-xs italic">No payments recorded.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Column: Logistics & Timeline -->
            <div class="space-y-6">
                <!-- Supplier Info -->
                <div class="bg-surface-container-lowest border border-outline-variant/20 p-6 rounded-2xl shadow-sm">
                    <h3 class="text-xs font-headline font-bold text-outline uppercase tracking-widest mb-6">Logistics Profile</h3>
                    <div class="space-y-6">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-surface-container-low rounded-xl flex items-center justify-center text-primary">
                                <span class="material-symbols-outlined">factory</span>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-outline uppercase tracking-widest">Supplier</p>
                                <p class="text-sm font-black text-on-surface">{{ shipment.supplier_name || '—' }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-surface-container-low rounded-xl flex items-center justify-center text-orange-500">
                                <span class="material-symbols-outlined">directions_boat</span>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-outline uppercase tracking-widest">Vessel</p>
                                <p class="text-sm font-black text-on-surface">{{ shipment.vessel_name || '—' }}</p>
                            </div>
                        </div>

                        <div class="p-4 bg-surface-container-low rounded-xl border border-outline-variant/10">
                            <div class="flex justify-between items-center mb-4">
                                <div class="text-center flex-1">
                                    <p class="text-[10px] font-black text-primary uppercase">{{ shipment.origin || '?' }}</p>
                                    <p class="text-xs font-bold text-on-surface-variant">{{ fmtDate(shipment.departure_date) }}</p>
                                </div>
                                <span class="material-symbols-outlined text-outline">trending_flat</span>
                                <div class="text-center flex-1">
                                    <p class="text-[10px] font-black text-primary uppercase">{{ shipment.destination || '?' }}</p>
                                    <p class="text-xs font-bold text-on-surface-variant">{{ fmtDate(shipment.arrival_date) }}</p>
                                </div>
                            </div>
                            <div class="w-full bg-outline-variant/20 rounded-full h-1 relative overflow-hidden">
                                <div class="absolute inset-y-0 left-0 bg-primary h-full rounded-full" style="width: 65%"></div>
                            </div>
                        </div>

                        <div v-if="shipment.notes" class="pt-4 border-t border-outline-variant/10">
                            <p class="text-[10px] font-bold text-outline uppercase tracking-widest mb-2">Internal Notes</p>
                            <p class="text-xs text-on-surface-variant bg-surface-container-low p-3 rounded-lg leading-relaxed italic">"{{ shipment.notes }}"</p>
                        </div>
                    </div>
                </div>

                <!-- Cost Distribution -->
                <div class="bg-surface-container-lowest border border-outline-variant/20 p-6 rounded-2xl shadow-sm">
                    <h3 class="text-xs font-headline font-bold text-outline uppercase tracking-widest mb-6">Cost Breakdown</h3>
                    <div class="space-y-4">
                        <div v-for="cost in [
                            { label: 'Freight', val: shipment.shipping_cost },
                            { label: 'Import Tax', val: shipment.import_tax },
                            { label: 'Clearance', val: shipment.clearance_fees },
                            { label: 'Other', val: shipment.other_costs }
                        ]" :key="cost.label" class="flex justify-between items-center text-xs">
                            <span class="font-bold text-outline uppercase tracking-widest text-[9px]">{{ cost.label }}</span>
                            <span class="font-black text-on-surface">{{ formatCurrency(cost.val) }}</span>
                        </div>
                        <div class="pt-4 border-t border-outline-variant/20 flex justify-between items-center">
                            <span class="text-[10px] font-black text-primary uppercase tracking-widest">Total Logistics</span>
                            <span class="text-sm font-black text-primary">{{ formatCurrency(shipment.total_costs) }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Modals -->
        <SideModal :show="showItemForm" title="Add Container Item" @close="showItemForm = false">
            <form @submit.prevent="addItem" class="space-y-6 p-4">
                <FormField label="Product Name" required>
                    <TextInput v-model="itemForm.product_name" placeholder="e.g. Sharp 55' LED TV" />
                </FormField>
                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Quantity" required>
                        <TextInput v-model="itemForm.quantity" type="number" min="1" />
                    </FormField>
                    <FormField label="Cost">
                        <TextInput v-model="itemForm.cost" type="number" step="0.01" placeholder="0.00" />
                    </FormField>
                </div>
                <FormField label="Currency">
                    <SelectInput v-model="itemForm.currency" :options="currencies" />
                </FormField>
                <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/10 mt-6">
                    <SecondaryButton @click="showItemForm = false" type="button">Cancel</SecondaryButton>
                    <PrimaryButton :loading="itemForm.processing">Add to Packing List</PrimaryButton>
                </div>
            </form>
        </SideModal>

        <SideModal :show="showPaymentForm" title="Record Payment" @close="showPaymentForm = false">
            <form @submit.prevent="addPayment" class="space-y-6 p-4">
                <FormField label="Amount (AED)" required>
                    <TextInput v-model="paymentForm.amount" type="number" step="0.01" min="0.01" />
                </FormField>
                <FormField label="Payment Date" required>
                    <TextInput v-model="paymentForm.payment_date" type="date" />
                </FormField>
                <FormField label="Method">
                    <SelectInput v-model="paymentForm.payment_method" :options="[
                        {label:'Bank Transfer', value:'Bank Transfer'},
                        {label:'Cash', value:'Cash'},
                        {label:'Cheque', value:'Cheque'}
                    ]" />
                </FormField>
                <FormField label="Notes">
                    <TextInput v-model="paymentForm.note" />
                </FormField>
                <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/10 mt-6">
                    <SecondaryButton @click="showPaymentForm = false" type="button">Cancel</SecondaryButton>
                    <PrimaryButton :loading="paymentForm.processing" class="!bg-emerald-600">Confirm Payment</PrimaryButton>
                </div>
            </form>
        </SideModal>
    </div>
</template>

<style scoped>
.font-black { font-weight: 900; }
.tracking-tight { letter-spacing: -0.025em; }
</style>
