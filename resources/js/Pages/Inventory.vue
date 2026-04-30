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
    inventory: Array,
    summary: Object,
    movements: Array,
});

const showAddModal = ref(false);
const showTransferModal = ref(false);
const selectedItem = ref(null);
const activeTab = ref('inventory'); // 'inventory' or 'history'
const locationTab = ref('all'); // 'all', 'shop', 'warehouse', 'remote'

const form = useForm({
    name: '',
    category: '',
    cost_price: 0,
    selling_price: 0,
    shop_quantity: 0,
    warehouse_quantity: 0,
    remote_quantity: 0,
    low_stock_threshold: 10,
});

const transferForm = useForm({
    quantity: 1,
    from: 'warehouse',
    to: 'shop',
    notes: '',
});

const openAddModal = () => {
    selectedItem.value = null;
    form.reset();
    showAddModal.value = true;
};

const openEditModal = (item) => {
    selectedItem.value = item;
    form.name = item.name;
    form.category = item.category;
    form.cost_price = item.cost_price;
    form.selling_price = item.selling_price;
    form.shop_quantity = item.shop_quantity;
    form.warehouse_quantity = item.warehouse_quantity;
    form.remote_quantity = item.remote_quantity;
    form.low_stock_threshold = item.low_stock_threshold;
    showAddModal.value = true;
};

const openTransferModal = (item) => {
    selectedItem.value = item;
    transferForm.reset();
    showTransferModal.value = true;
};

const submit = () => {
    if (selectedItem.value) {
        form.put(`/inventory/${selectedItem.value.id}`, {
            onSuccess: () => {
                showAddModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post('/inventory', {
            onSuccess: () => {
                showAddModal.value = false;
                form.reset();
            },
        });
    }
};

const submitTransfer = () => {
    transferForm.post(`/inventory/${selectedItem.value.id}/transfer`, {
        onSuccess: () => {
            showTransferModal.value = false;
            transferForm.reset();
        },
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-AE', { style: 'currency', currency: 'AED' }).format(value);
};

const locations = [
    { label: 'Shop', value: 'shop' },
    { label: 'Main Warehouse', value: 'warehouse' },
    { label: 'Remote Warehouse', value: 'remote' },
];

const filteredInventory = computed(() => {
    if (locationTab.value === 'all') return props.inventory;
    
    return props.inventory.filter(item => {
        if (locationTab.value === 'shop') return item.shop_quantity > 0;
        if (locationTab.value === 'warehouse') return item.warehouse_quantity > 0;
        if (locationTab.value === 'remote') return item.remote_quantity > 0;
        return true;
    });
});
</script>

<template>
    <Head title="Inventory Management" />

    <div class="space-y-6 animate-in fade-in duration-500">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-headline font-bold text-on-surface tracking-tight">Inventory</h1>
                <p class="text-sm text-outline font-label">Manage products across 3 storage locations</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="bg-surface-container-low p-1 rounded-xl flex gap-1 border border-outline-variant/10">
                    <button 
                        @click="activeTab = 'inventory'"
                        :class="[activeTab === 'inventory' ? 'bg-primary text-on-primary shadow-sm' : 'text-outline hover:text-on-surface']"
                        class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all"
                    >
                        Inventory List
                    </button>
                    <button 
                        @click="activeTab = 'history'"
                        :class="[activeTab === 'history' ? 'bg-primary text-on-primary shadow-sm' : 'text-outline hover:text-on-surface']"
                        class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all"
                    >
                        Stock History
                    </button>
                </div>
                <PrimaryButton @click="openAddModal" class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Add Product
                </PrimaryButton>
            </div>
        </div>

        <!-- Summary Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-surface-container-lowest border border-outline-variant/20 p-5 rounded-2xl shadow-sm flex items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl text-primary">
                    <span class="material-symbols-outlined text-2xl">inventory_2</span>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-outline uppercase tracking-widest">Total Products</p>
                    <h3 class="text-xl font-headline font-black text-on-surface">{{ summary.total_items }}</h3>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/20 p-5 rounded-2xl shadow-sm flex items-center gap-4">
                <div class="p-3 bg-emerald-500/10 rounded-xl text-emerald-500">
                    <span class="material-symbols-outlined text-2xl">payments</span>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-outline uppercase tracking-widest">Stock Valuation (Cost)</p>
                    <h3 class="text-xl font-headline font-black text-on-surface">{{ formatCurrency(summary.total_valuation) }}</h3>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/20 p-5 rounded-2xl shadow-sm flex items-center gap-4">
                <div class="p-3 bg-error/10 rounded-xl text-error">
                    <span class="material-symbols-outlined text-2xl">warning</span>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-outline uppercase tracking-widest">Low Stock Alerts</p>
                    <h3 class="text-xl font-headline font-black text-on-surface">{{ summary.low_stock_items }}</h3>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div v-if="activeTab === 'inventory'" class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl shadow-sm overflow-hidden flex flex-col">
            
            <!-- Location Tabs -->
            <div class="p-4 border-b border-outline-variant/20 flex gap-2 overflow-x-auto">
                <button @click="locationTab = 'all'" :class="locationTab === 'all' ? 'bg-primary/10 text-primary border-primary/20' : 'bg-surface-container-low text-outline border-outline-variant/20 hover:text-on-surface'" class="px-5 py-2 rounded-lg text-xs font-black uppercase tracking-widest border transition-all">All Locations</button>
                <button @click="locationTab = 'shop'" :class="locationTab === 'shop' ? 'bg-primary/10 text-primary border-primary/20' : 'bg-surface-container-low text-outline border-outline-variant/20 hover:text-on-surface'" class="px-5 py-2 rounded-lg text-xs font-black uppercase tracking-widest border transition-all">Shop</button>
                <button @click="locationTab = 'warehouse'" :class="locationTab === 'warehouse' ? 'bg-primary/10 text-primary border-primary/20' : 'bg-surface-container-low text-outline border-outline-variant/20 hover:text-on-surface'" class="px-5 py-2 rounded-lg text-xs font-black uppercase tracking-widest border transition-all">Main Warehouse</button>
                <button @click="locationTab = 'remote'" :class="locationTab === 'remote' ? 'bg-primary/10 text-primary border-primary/20' : 'bg-surface-container-low text-outline border-outline-variant/20 hover:text-on-surface'" class="px-5 py-2 rounded-lg text-xs font-black uppercase tracking-widest border transition-all">Remote Warehouse</button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low/50 text-lg font-bold text-outline uppercase tracking-wider">
                            <th class="px-6 py-5">Product Info</th>
                            <th v-if="locationTab === 'all' || locationTab === 'shop'" class="px-6 py-5">Shop</th>
                            <th v-if="locationTab === 'all' || locationTab === 'warehouse'" class="px-6 py-5">Warehouse</th>
                            <th v-if="locationTab === 'all' || locationTab === 'remote'" class="px-6 py-5">Remote</th>
                            <th class="px-6 py-5">Total</th>
                            <th class="px-6 py-5">Prices (AED)</th>
                            <th class="px-6 py-5 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <tr v-for="item in filteredInventory" :key="item.id" class="hover:bg-surface-container-low/30 transition-colors group">
                            <td class="px-6 py-6">
                                <div class="flex flex-col">
                                    <span class="text-xl font-bold text-on-surface">{{ item.name }}</span>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span v-if="item.category" class="text-base text-primary font-bold">{{ item.category }}</span>
                                    </div>
                                </div>
                            </td>
                            <td v-if="locationTab === 'all' || locationTab === 'shop'" class="px-6 py-6 text-xl font-bold" :class="item.shop_quantity <= 2 ? 'text-error' : 'text-on-surface'">{{ item.shop_quantity }}</td>
                            <td v-if="locationTab === 'all' || locationTab === 'warehouse'" class="px-6 py-6 text-xl font-bold text-on-surface">{{ item.warehouse_quantity }}</td>
                            <td v-if="locationTab === 'all' || locationTab === 'remote'" class="px-6 py-6 text-xl font-bold text-on-surface">{{ item.remote_quantity }}</td>
                            <td class="px-6 py-6">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl font-black" :class="item.total_quantity <= item.low_stock_threshold ? 'text-error' : 'text-on-surface'">{{ item.total_quantity }}</span>
                                    <span v-if="item.total_quantity <= item.low_stock_threshold" class="material-symbols-outlined text-error text-[20px]">priority_high</span>
                                </div>
                            </td>
                            <td class="px-6 py-6">
                                <div class="flex flex-col text-sm">
                                    <span class="text-outline">Cost: {{ formatCurrency(item.cost_price) }}</span>
                                    <span class="text-primary font-bold">Sell: {{ formatCurrency(item.selling_price) }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click="openTransferModal(item)" class="p-1.5 text-outline hover:text-orange-500 transition-colors" title="Transfer"><span class="material-symbols-outlined text-[18px]">swap_horiz</span></button>
                                    <button @click="openEditModal(item)" class="p-1.5 text-outline hover:text-primary transition-colors"><span class="material-symbols-outlined text-[18px]">edit</span></button>
                                    <button @click="router.delete(`/inventory/${item.id}`)" class="p-1.5 text-outline hover:text-error transition-colors"><span class="material-symbols-outlined text-[18px]">delete</span></button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filteredInventory.length === 0">
                            <td colspan="7" class="py-12 text-center text-outline text-xs">
                                No products found in this location.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- History Tab -->
        <div v-else class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low/50 text-lg font-bold text-outline uppercase tracking-wider">
                            <th class="px-6 py-5">Date</th>
                            <th class="px-6 py-5">Product</th>
                            <th class="px-6 py-5">Type</th>
                            <th class="px-6 py-5">Quantity</th>
                            <th class="px-6 py-5">Route</th>
                            <th class="px-6 py-5">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <tr v-for="move in movements" :key="move.id" class="text-lg hover:bg-surface-container-low/30 transition-colors">
                            <td class="px-6 py-5 text-outline">{{ new Date(move.created_at).toLocaleString() }}</td>
                            <td class="px-6 py-5 font-bold">{{ move.inventory?.name || 'Unknown' }}</td>
                            <td class="px-6 py-5">
                                <Badge :variant="move.type === 'in' ? 'success' : (move.type === 'transfer' ? 'warning' : 'error')" class="text-base px-3 py-1.5">
                                    {{ move.type.toUpperCase() }}
                                </Badge>
                            </td>
                            <td class="px-6 py-5 font-black text-xl">{{ move.quantity }}</td>
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-1 text-base">
                                    <span class="capitalize">{{ move.from_location || '-' }}</span>
                                    <span v-if="move.to_location" class="material-symbols-outlined text-[16px]">arrow_forward</span>
                                    <span class="capitalize">{{ move.to_location || '' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-5 text-outline">{{ move.notes }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <SideModal :show="showAddModal" :title="selectedItem ? 'Edit Product' : 'Add Product'" @close="showAddModal = false">
            <form @submit.prevent="submit" class="space-y-4 p-2">
                <FormField label="Product Name" :error="form.errors.name" required>
                    <TextInput v-model="form.name" placeholder="Item Name" />
                </FormField>
                <FormField label="Category" :error="form.errors.category">
                    <TextInput v-model="form.category" placeholder="Electronics" />
                </FormField>
                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Cost Price (AED)" :error="form.errors.cost_price">
                        <TextInput v-model="form.cost_price" type="number" step="0.01" />
                    </FormField>
                    <FormField label="Selling Price (AED)" :error="form.errors.selling_price">
                        <TextInput v-model="form.selling_price" type="number" step="0.01" />
                    </FormField>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <FormField label="Shop Qty" :error="form.errors.shop_quantity">
                        <TextInput v-model="form.shop_quantity" type="number" />
                    </FormField>
                    <FormField label="Wh. Qty" :error="form.errors.warehouse_quantity">
                        <TextInput v-model="form.warehouse_quantity" type="number" />
                    </FormField>
                    <FormField label="Remote Qty" :error="form.errors.remote_quantity">
                        <TextInput v-model="form.remote_quantity" type="number" />
                    </FormField>
                </div>
                <FormField label="Low Stock Threshold" :error="form.errors.low_stock_threshold">
                    <TextInput v-model="form.low_stock_threshold" type="number" />
                </FormField>

                <div class="pt-6 flex justify-end gap-3 border-t border-outline-variant/10 mt-6">
                    <SecondaryButton @click="showAddModal = false" type="button">Cancel</SecondaryButton>
                    <PrimaryButton :loading="form.processing">Save Product</PrimaryButton>
                </div>
            </form>
        </SideModal>

        <!-- Transfer Modal -->
        <SideModal :show="showTransferModal" title="Stock Transfer" @close="showTransferModal = false">
            <div v-if="selectedItem" class="p-2 space-y-6">
                <div class="bg-primary/5 p-4 rounded-xl border border-primary/10">
                    <p class="text-[10px] font-bold text-primary uppercase mb-1">Transferring</p>
                    <p class="text-sm font-bold">{{ selectedItem.name }}</p>
                    <div class="flex gap-4 mt-2 text-[10px]">
                        <span>Shop: {{ selectedItem.shop_quantity }}</span>
                        <span>Warehouse: {{ selectedItem.warehouse_quantity }}</span>
                        <span>Remote: {{ selectedItem.remote_quantity }}</span>
                    </div>
                </div>

                <form @submit.prevent="submitTransfer" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <FormField label="From" required>
                            <SelectInput v-model="transferForm.from" :options="locations" />
                        </FormField>
                        <FormField label="To" required>
                            <SelectInput v-model="transferForm.to" :options="locations" />
                        </FormField>
                    </div>
                    <FormField label="Quantity" required :error="transferForm.errors.quantity">
                        <TextInput v-model="transferForm.quantity" type="number" />
                    </FormField>
                    <FormField label="Notes">
                        <TextInput v-model="transferForm.notes" placeholder="Reason for transfer" />
                    </FormField>

                    <div class="pt-6 flex justify-end gap-3 border-t border-outline-variant/10 mt-6">
                        <SecondaryButton @click="showTransferModal = false" type="button">Cancel</SecondaryButton>
                        <PrimaryButton :loading="transferForm.processing" class="!bg-orange-600 hover:!bg-orange-700">
                            Transfer Now
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </SideModal>
    </div>
</template>
