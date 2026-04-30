<script setup>
import { ref } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import SideModal from '@/Components/SideModal.vue';
import FormField from '@/Components/FormField.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Badge from '@/Components/Badge.vue';

defineOptions({ layout: MainLayout });

const props = defineProps({ shipments: Array, summary: Object, filters: Object });

const search   = ref(props.filters?.search || '');
const statusF  = ref(props.filters?.status || '');
const showForm = ref(false);
const editing  = ref(null);
const statuses = ['On Board', 'In Transit', 'Delivered', 'Completed'];

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-AE', { style: 'currency', currency: 'AED' }).format(value);
};
const fmtDate = d => d ? new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—';

const getStatusColor = (s) => {
    switch (s) {
        case 'On Board': return 'text-primary bg-primary/10';
        case 'In Transit': return 'text-orange-500 bg-orange-500/10';
        case 'Delivered': return 'text-emerald-600 bg-emerald-600/10';
        case 'Completed': return 'text-outline bg-surface-container-high';
        default: return 'text-outline bg-surface-container-low';
    }
};

const form = useForm({
  container_number:'', vessel_name:'', origin:'', destination:'',
  departure_date:'', arrival_date:'', status:'On Board',
  shipping_cost:0, import_tax:0, clearance_fees:0, other_costs:0,
  supplier_name:'', invoice_amount:0, paid_amount:0, notes:'',
});

const openAdd = () => { editing.value=null; form.reset(); form.status='On Board'; showForm.value=true; };
const openEdit = s => {
  editing.value=s;
  Object.keys(form.data()).forEach(k => { if(s[k]!==undefined) form[k]=s[k]; });
  showForm.value=true;
};
const submit = () => {
  const opts = { onSuccess: () => { showForm.value=false; form.reset(); } };
  editing.value ? form.put(`/shipping/${editing.value.id}`, opts) : form.post('/shipping', opts);
};
const del = id => confirm('Delete this shipment?') && router.delete(`/shipping/${id}`);

const doSearch = () => router.get('/shipping', { search: search.value, status: statusF.value }, { preserveState:true });
</script>

<template>
    <Head title="Shipping Management" />

    <div class="space-y-6 animate-in fade-in duration-700">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-headline font-bold text-on-surface tracking-tight">Logistics Overview</h1>
                <p class="text-sm text-outline font-label">Container Tracking & Financials</p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="openAdd" class="bg-primary text-on-primary px-6 py-2.5 rounded-xl font-bold flex items-center gap-2 hover:bg-primary/90 transition-all active:scale-95 shadow-lg shadow-primary/20">
                    <span class="material-symbols-outlined text-[20px]">add_box</span>
                    New Shipment
                </button>
            </div>
        </div>

        <!-- KPI Grid - Matching Dashboard.vue -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-primary border border-primary-container p-5 rounded-2xl shadow-lg shadow-primary/10 text-on-primary">
                <p class="text-[10px] font-bold opacity-80 uppercase tracking-widest mb-1">Total Balance Due</p>
                <h3 class="text-2xl font-headline font-black">{{ formatCurrency(summary.total_balance_due) }}</h3>
                <p class="text-[10px] font-bold mt-2 opacity-70">Total outstanding payments</p>
            </div>

            <div v-for="kpi in [
                { label: 'Total Shipments', val: summary.total_shipments, sub: 'Global containers', color: 'text-on-surface' },
                { label: 'In Transit', val: summary.in_transit, sub: 'On Board / Sea', color: 'text-orange-500' },
                { label: 'Arrived', val: summary.arrived, sub: 'Delivered / Completed', color: 'text-emerald-600' }
            ]" :key="kpi.label" class="bg-surface-container-lowest border border-outline-variant/20 p-5 rounded-2xl shadow-sm">
                <p class="text-[10px] font-bold text-outline uppercase tracking-widest mb-1">{{ kpi.label }}</p>
                <h3 class="text-2xl font-headline font-black" :class="kpi.color">{{ kpi.val }}</h3>
                <p class="text-[10px] text-outline font-bold mt-2">{{ kpi.sub }}</p>
            </div>
        </div>

        <!-- Table View -->
        <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl shadow-sm overflow-hidden flex flex-col">
            <div class="p-5 border-b border-outline-variant/20 flex flex-col sm:flex-row justify-between items-center gap-4">
                <h3 class="text-sm font-headline font-bold text-on-surface uppercase tracking-widest">Active Containers Board</h3>
                
                <div class="flex gap-3 w-full sm:w-auto">
                    <div class="relative flex-1 sm:min-w-[250px]">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                        <input v-model="search" @keyup.enter="doSearch" type="text" placeholder="Container, Vessel, Supplier..."
                            class="w-full pl-10 pr-4 py-2 bg-surface-container-low border border-outline-variant/20 rounded-lg text-xs font-medium text-on-surface outline-none focus:ring-1 focus:ring-primary transition-all" />
                    </div>
                    <select v-model="statusF" @change="doSearch" class="bg-surface-container-low border border-outline-variant/20 rounded-lg px-4 py-2 text-xs font-bold text-on-surface outline-none cursor-pointer">
                        <option value="">All Status</option>
                        <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-surface-container-low/30 text-[10px] font-bold text-outline uppercase tracking-widest border-b border-outline-variant/20">
                            <th class="py-4 px-6">Container / Vessel</th>
                            <th class="py-4 px-6">Supplier</th>
                            <th class="py-4 px-6">ETA</th>
                            <th class="py-4 px-6">Status</th>
                            <th class="py-4 px-6">Balance Due</th>
                            <th class="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <tr v-for="s in shipments" :key="s.id" class="group hover:bg-surface-container-low/50 transition-colors">
                            <td class="py-4 px-6">
                                <Link :href="`/shipping/${s.id}`" class="block group/link">
                                    <p class="text-sm font-bold text-on-surface group-hover/link:text-primary transition-colors">{{ s.container_number }}</p>
                                    <p class="text-[10px] text-outline font-medium">{{ s.vessel_name || 'No Vessel' }}</p>
                                </Link>
                            </td>
                            <td class="py-4 px-6 text-xs font-medium text-on-surface-variant">{{ s.supplier_name || '—' }}</td>
                            <td class="py-4 px-6 text-xs font-bold text-on-surface-variant">{{ fmtDate(s.arrival_date) }}</td>
                            <td class="py-4 px-6">
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest" :class="getStatusColor(s.status)">
                                    {{ s.status }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <p class="text-sm font-black" :class="s.balance_due > 0 ? 'text-error' : 'text-emerald-600'">
                                    {{ formatCurrency(s.balance_due) }}
                                </p>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <Link :href="`/shipping/${s.id}`" class="p-1.5 text-outline hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-[20px]">visibility</span>
                                    </Link>
                                    <button @click="openEdit(s)" class="p-1.5 text-outline hover:text-orange-500 transition-colors">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </button>
                                    <button @click="del(s.id)" class="p-1.5 text-outline hover:text-error transition-colors">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="shipments.length === 0">
                            <td colspan="6" class="py-20 text-center text-outline text-xs italic">No shipments found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <SideModal :show="showForm" :title="editing ? 'Edit Shipment' : 'New Shipment'" @close="showForm=false">
            <form @submit.prevent="submit" class="space-y-6 p-4">
                <div class="space-y-4">
                    <h4 class="text-[10px] font-black text-primary uppercase tracking-widest border-b border-outline-variant/30 pb-2">Logistics</h4>
                    <FormField label="Container Number" :error="form.errors.container_number" required>
                        <TextInput v-model="form.container_number" placeholder="MSKU-123456-7" />
                    </FormField>
                    <FormField label="Supplier Name"><TextInput v-model="form.supplier_name" /></FormField>
                    <FormField label="Vessel Name"><TextInput v-model="form.vessel_name" /></FormField>
                    <div class="grid grid-cols-2 gap-4">
                        <FormField label="Origin"><TextInput v-model="form.origin" /></FormField>
                        <FormField label="Destination"><TextInput v-model="form.destination" /></FormField>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <FormField label="Departure"><TextInput v-model="form.departure_date" type="date" /></FormField>
                        <FormField label="ETA"><TextInput v-model="form.arrival_date" type="date" /></FormField>
                    </div>
                    <FormField label="Status" required>
                        <SelectInput v-model="form.status" :options="statuses.map(s=>({label:s,value:s}))" />
                    </FormField>
                </div>

                <div class="space-y-4">
                    <h4 class="text-[10px] font-black text-primary uppercase tracking-widest border-b border-outline-variant/30 pb-2">Financials (AED)</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <FormField label="Invoice Amount"><TextInput v-model="form.invoice_amount" type="number" step="0.01" /></FormField>
                        <FormField label="Initial Paid"><TextInput v-model="form.paid_amount" type="number" step="0.01" /></FormField>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <FormField label="Shipping Cost"><TextInput v-model="form.shipping_cost" type="number" step="0.01" /></FormField>
                        <FormField label="Import Tax"><TextInput v-model="form.import_tax" type="number" step="0.01" /></FormField>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <FormField label="Clearance Fees"><TextInput v-model="form.clearance_fees" type="number" step="0.01" /></FormField>
                        <FormField label="Misc Costs"><TextInput v-model="form.other_costs" type="number" step="0.01" /></FormField>
                    </div>
                </div>

                <FormField label="Notes">
                    <textarea v-model="form.notes" class="w-full bg-surface-container-low border border-outline-variant/20 rounded-lg p-3 text-sm text-on-surface outline-none focus:ring-1 focus:ring-primary min-h-[100px]"></textarea>
                </FormField>

                <div class="flex justify-end gap-3 pt-4">
                    <SecondaryButton @click="showForm=false" type="button">Cancel</SecondaryButton>
                    <PrimaryButton :loading="form.processing">{{ editing ? 'Update' : 'Create' }}</PrimaryButton>
                </div>
            </form>
        </SideModal>
    </div>
</template>
