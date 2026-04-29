<script setup>
import { ref, computed } from 'vue';
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
    shipments: Array,
    summary: Object,
    filters: Object,
});

const showAddModal = ref(false);
const editingShipment = ref(null);
const search = ref(props.filters.search || '');

const form = useForm({
    container_number: '',
    vessel_name: '',
    origin: '',
    destination: '',
    departure_date: '',
    arrival_date: '',
    status: 'On Board',
    shipping_cost: 0,
    import_tax: 0,
    clearance_fees: 0,
    other_costs: 0,
    notes: '',
});

const openAddModal = () => {
    editingShipment.value = null;
    form.reset();
    showAddModal.value = true;
};

const openEditModal = (shipment) => {
    editingShipment.value = shipment;
    form.container_number = shipment.container_number;
    form.vessel_name = shipment.vessel_name;
    form.origin = shipment.origin;
    form.destination = shipment.destination;
    form.departure_date = shipment.departure_date;
    form.arrival_date = shipment.arrival_date;
    form.status = shipment.status;
    form.shipping_cost = shipment.shipping_cost;
    form.import_tax = shipment.import_tax;
    form.clearance_fees = shipment.clearance_fees;
    form.other_costs = shipment.other_costs;
    form.notes = shipment.notes;
    showAddModal.value = true;
};

const submit = () => {
    if (editingShipment.value) {
        form.put(`/shipping/${editingShipment.value.id}`, {
            onSuccess: () => {
                showAddModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post('/shipping', {
            onSuccess: () => {
                showAddModal.value = false;
                form.reset();
            },
        });
    }
};

const deleteShipment = (id) => {
    if (confirm('Are you sure you want to delete this shipment?')) {
        router.delete(`/shipping/${id}`);
    }
};

const handleSearch = () => {
    router.get('/shipping', { search: search.value }, { preserveState: true, preserveScroll: true });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-AE', { style: 'currency', currency: 'AED' }).format(value);
};

const getStatusVariant = (status) => {
    switch (status) {
        case 'Completed': return 'success';
        case 'Delivered': return 'info';
        case 'In Transit': return 'orange';
        case 'On Board': return 'primary';
        default: return 'neutral';
    }
};

const statuses = ['On Board', 'In Transit', 'Delivered', 'Completed'];
</script>

<template>
    <Head title="Shipping & Logistics" />

    <div class="space-y-6 animate-in fade-in duration-500">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-headline font-bold text-on-surface tracking-tight">Shipping & Logistics</h1>
                <p class="text-sm text-outline font-label">Track containers, vessels, and logistics costs</p>
            </div>
            <div class="flex items-center gap-3">
                <PrimaryButton @click="openAddModal" class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    New Shipment
                </PrimaryButton>
            </div>
        </div>

        <!-- Summary Row -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-surface-container-lowest border border-outline-variant/20 p-5 rounded-2xl shadow-sm">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-2 bg-primary/10 rounded-lg text-primary">
                        <span class="material-symbols-outlined">directions_boat</span>
                    </div>
                    <Badge variant="primary">Total</Badge>
                </div>
                <p class="text-[10px] font-bold text-outline uppercase tracking-widest">Total Shipments</p>
                <h3 class="text-2xl font-headline font-black text-on-surface">{{ summary.total_shipments }}</h3>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/20 p-5 rounded-2xl shadow-sm">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-2 bg-orange-500/10 rounded-lg text-orange-500">
                        <span class="material-symbols-outlined">schedule</span>
                    </div>
                    <Badge variant="warning">Active</Badge>
                </div>
                <p class="text-[10px] font-bold text-outline uppercase tracking-widest">Active Containers</p>
                <h3 class="text-2xl font-headline font-black text-on-surface">{{ summary.active_shipments }}</h3>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/20 p-5 rounded-2xl shadow-sm">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-2 bg-emerald-500/10 rounded-lg text-emerald-500">
                        <span class="material-symbols-outlined">payments</span>
                    </div>
                </div>
                <p class="text-[10px] font-bold text-outline uppercase tracking-widest">Total Shipping Costs</p>
                <h3 class="text-xl font-headline font-black text-on-surface">{{ formatCurrency(summary.total_shipping_costs) }}</h3>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/20 p-5 rounded-2xl shadow-sm">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-2 bg-purple-500/10 rounded-lg text-purple-500">
                        <span class="material-symbols-outlined">account_balance_wallet</span>
                    </div>
                </div>
                <p class="text-[10px] font-bold text-outline uppercase tracking-widest">Tax & Clearance</p>
                <h3 class="text-xl font-headline font-black text-on-surface">{{ formatCurrency(summary.total_tax_clearance) }}</h3>
            </div>
        </div>

        <!-- Table Section -->
        <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-outline-variant/20 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex-1 max-w-md relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                    <input 
                        v-model="search"
                        @keyup.enter="handleSearch"
                        type="text" 
                        placeholder="Search container or vessel..." 
                        class="w-full pl-10 pr-4 py-2 bg-surface-container-low rounded-xl border border-outline-variant/20 focus:outline-none focus:ring-2 focus:ring-primary/20 text-sm font-label transition-all"
                    />
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low/50 text-[11px] font-bold text-outline uppercase tracking-wider">
                            <th class="px-6 py-4">Container #</th>
                            <th class="px-6 py-4">Vessel / Route</th>
                            <th class="px-6 py-4">Dates</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Costs (AED)</th>
                            <th class="px-6 py-4">Invoices</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <tr v-for="shipment in shipments" :key="shipment.id" class="hover:bg-surface-container-low/30 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-on-surface">{{ shipment.container_number }}</span>
                                    <span class="text-[10px] text-outline">ID: #SHP-{{ shipment.id }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-on-surface">{{ shipment.vessel_name || 'N/A' }}</span>
                                    <span class="text-[10px] text-primary flex items-center gap-1">
                                        {{ shipment.origin || '?' }} 
                                        <span class="material-symbols-outlined text-[12px]">arrow_forward</span> 
                                        {{ shipment.destination || '?' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col text-[10px]">
                                    <span class="flex items-center gap-1"><span class="text-outline">DEP:</span> {{ shipment.departure_date || '-' }}</span>
                                    <span class="flex items-center gap-1"><span class="text-outline">ARR:</span> {{ shipment.arrival_date || '-' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <Badge :variant="getStatusVariant(shipment.status)">{{ shipment.status }}</Badge>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col text-[10px] font-bold">
                                    <span class="text-emerald-600">Ship: {{ formatCurrency(shipment.shipping_cost) }}</span>
                                    <span class="text-purple-600">Tax/Clr: {{ formatCurrency(Number(shipment.import_tax) + Number(shipment.clearance_fees)) }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px] text-outline">description</span>
                                    <span class="text-xs font-bold">{{ shipment.sales_count }} sales</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click="openEditModal(shipment)" class="p-1.5 text-outline hover:text-primary transition-colors"><span class="material-symbols-outlined text-[18px]">edit</span></button>
                                    <button @click="deleteShipment(shipment.id)" class="p-1.5 text-outline hover:text-error transition-colors"><span class="material-symbols-outlined text-[18px]">delete</span></button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="shipments.length === 0">
                            <td colspan="7" class="px-6 py-10 text-center text-outline font-label">No shipments found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <SideModal :show="showAddModal" :title="editingShipment ? 'Edit Shipment' : 'New Shipment'" @close="showAddModal = false">
            <form @submit.prevent="submit" class="space-y-5 p-2">
                <div class="space-y-4">
                    <h4 class="text-[11px] font-bold text-primary uppercase tracking-widest border-b border-primary/10 pb-2">Basic Info</h4>
                    <FormField label="Container Number" :error="form.errors.container_number" required>
                        <TextInput v-model="form.container_number" placeholder="CNTR-1234567" />
                    </FormField>
                    <FormField label="Vessel Name" :error="form.errors.vessel_name">
                        <TextInput v-model="form.vessel_name" placeholder="Ever Given" />
                    </FormField>
                    <div class="grid grid-cols-2 gap-4">
                        <FormField label="Origin" :error="form.errors.origin">
                            <TextInput v-model="form.origin" placeholder="Shanghai" />
                        </FormField>
                        <FormField label="Destination" :error="form.errors.destination">
                            <TextInput v-model="form.destination" placeholder="Dubai Jebel Ali" />
                        </FormField>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <FormField label="Departure Date" :error="form.errors.departure_date">
                            <TextInput v-model="form.departure_date" type="date" />
                        </FormField>
                        <FormField label="Arrival Date" :error="form.errors.arrival_date">
                            <TextInput v-model="form.arrival_date" type="date" />
                        </FormField>
                    </div>
                    <FormField label="Status" :error="form.errors.status" required>
                        <SelectInput v-model="form.status" :options="statuses.map(s => ({label: s, value: s}))" />
                    </FormField>
                </div>

                <div class="space-y-4 pt-4">
                    <h4 class="text-[11px] font-bold text-primary uppercase tracking-widest border-b border-primary/10 pb-2">Financials (AED)</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <FormField label="Shipping Cost" :error="form.errors.shipping_cost">
                            <TextInput v-model="form.shipping_cost" type="number" step="0.01" />
                        </FormField>
                        <FormField label="Import Tax" :error="form.errors.import_tax">
                            <TextInput v-model="form.import_tax" type="number" step="0.01" />
                        </FormField>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <FormField label="Clearance Fees" :error="form.errors.clearance_fees">
                            <TextInput v-model="form.clearance_fees" type="number" step="0.01" />
                        </FormField>
                        <FormField label="Other Costs" :error="form.errors.other_costs">
                            <TextInput v-model="form.other_costs" type="number" step="0.01" />
                        </FormField>
                    </div>
                </div>

                <FormField label="Notes" :error="form.errors.notes">
                    <textarea v-model="form.notes" class="w-full bg-surface-container-low border border-outline-variant/20 rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 min-h-[100px]"></textarea>
                </FormField>

                <div class="pt-6 flex justify-end gap-3 border-t border-outline-variant/10 mt-6">
                    <SecondaryButton @click="showAddModal = false" type="button">Cancel</SecondaryButton>
                    <PrimaryButton :loading="form.processing" :disabled="form.processing">
                        {{ editingShipment ? 'Update Shipment' : 'Create Shipment' }}
                    </PrimaryButton>
                </div>
            </form>
        </SideModal>
    </div>
</template>
