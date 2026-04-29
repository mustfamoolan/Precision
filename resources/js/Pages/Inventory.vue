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

const form = useForm({
    name: '',
    category: '',
    sku: '',
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
    form.sku = item.sku;
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
                <div class="bg-surface-container-low p-1 rounded-xl flex gap-1">
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
        <div v-if="activeTab === 'inventory'" class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low/50 text-[11px] font-bold text-outline uppercase tracking-wider">
                            <th class="px-6 py-4">Product Info</th>
                            <th class="px-6 py-4">Shop</th>
                            <th class="px-6 py-4">Warehouse</th>
                            <th class="px-6 py-4">Remote</th>
                            <th class="px-6 py-4">Total</th>
                            <th class="px-6 py-4">Prices (AED)</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <tr v-for="item in inventory" :key="item.id" class="hover:bg-surface-container-low/30 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-on-surface">{{ item.name }}</span>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-[10px] bg-surface-container-high px-1.5 py-0.5 rounded text-outline uppercase">{{ item.sku || 'No SKU' }}</span>
                                        <span v-if="item.category" class="text-[10px] text-primary font-bold">{{ item.category }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs font-bold" :class="item.shop_quantity <= 2 ? 'text-error' : 'text-on-surface'">{{ item.shop_quantity }}</td>
                            <td class="px-6 py-4 text-xs font-bold text-on-surface">{{ item.warehouse_quantity }}</td>
                            <td class="px-6 py-4 text-xs font-bold text-on-surface">{{ item.remote_quantity }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-black" :class="item.total_quantity <= item.low_stock_threshold ? 'text-error' : 'text-on-surface'">{{ item.total_quantity }}</span>
                                    <span v-if="item.total_quantity <= item.low_stock_threshold" class="material-symbols-outlined text-error text-[16px]">priority_high</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col text-[10px]">
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
                    </tbody>
                </table>
            </div>
        </div>

        <!-- History Tab -->
        <div v-else class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low/50 text-[11px] font-bold text-outline uppercase tracking-wider">
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4">Product</th>
                            <th class="px-6 py-4">Type</th>
                            <th class="px-6 py-4">Quantity</th>
                            <th class="px-6 py-4">Route</th>
                            <th class="px-6 py-4">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <tr v-for="move in movements" :key="move.id" class="text-xs">
                            <td class="px-6 py-4 text-outline">{{ new Date(move.created_at).toLocaleString() }}</td>
                            <td class="px-6 py-4 font-bold">{{ move.inventory.name }}</td>
                            <td class="px-6 py-4">
                                <Badge :variant="move.type === 'in' ? 'success' : (move.type === 'transfer' ? 'warning' : 'error')">
                                    {{ move.type.toUpperCase() }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4 font-black">{{ move.quantity }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-1 text-[10px]">
                                    <span class="capitalize">{{ move.from_location || '-' }}</span>
                                    <span v-if="move.to_location" class="material-symbols-outlined text-[12px]">arrow_forward</span>
                                    <span class="capitalize">{{ move.to_location || '' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-outline">{{ move.notes }}</td>
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
                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Category" :error="form.errors.category">
                        <TextInput v-model="form.category" placeholder="Electronics" />
                    </FormField>
                    <FormField label="SKU" :error="form.errors.sku">
                        <TextInput v-model="form.sku" placeholder="SKU-000" />
                    </FormField>
                </div>
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
