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

defineOptions({ layout: MainLayout });

const props = defineProps({
    inventory: Object,
    brands: Array,
    customers: Array,
    summary: Object,
    movements: Object,
    filters: Object,
});

const activeTab = ref('inventory'); // 'inventory' or 'history'
const viewMode = ref(props.filters?.brand_id ? 'products' : 'brands'); // 'brands' or 'products'
const selectedBrandId = ref(props.filters?.brand_id || null);
const locationTab = ref('all'); // 'all', 'shop', 'warehouse', 'remote'
const search = ref(props.filters?.search || '');

watch(search, (val) => {
    router.get('/inventory', { search: val, brand_id: selectedBrandId.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
});

const showAddModal = ref(false);
const showBrandModal = ref(false);
const showTransferModal = ref(false);
const showDeductModal = ref(false);
const showAdjustModal = ref(false);
const showItemHistoryModal = ref(false);
const selectedItem = ref(null);
const itemHistory = ref([]);
const loadingHistory = ref(false);

const form = useForm({
    name: '',
    brand_id: '',
    category: '',
    cost_price: 0,
    selling_price: 0,
    shop_quantity: 0,
    warehouse_quantity: 0,
    remote_quantity: 0,
    low_stock_threshold: 10,
});

const brandForm = useForm({
    name: '',
});

const transferForm = useForm({
    quantity: 1,
    from: 'warehouse',
    to: 'shop',
    notes: '',
});

const deductForm = useForm({
    customer_id: '',
    quantity: 1,
    location: 'shop',
    notes: '',
});

const adjustForm = useForm({
    quantity: 1,
    location: 'shop',
    type: 'in', // 'in' for increase, 'out' for decrease
    notes: '',
});

const openAddModal = () => {
    selectedItem.value = null;
    form.reset();
    if (selectedBrandId.value) form.brand_id = selectedBrandId.value;
    showAddModal.value = true;
};

const openEditModal = (item) => {
    selectedItem.value = item;
    form.name = item.name;
    form.brand_id = item.brand_id || '';
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

const openDeductModal = (item) => {
    selectedItem.value = item;
    deductForm.reset();
    showDeductModal.value = true;
};

const openAdjustModal = (item) => {
    selectedItem.value = item;
    adjustForm.reset();
    showAdjustModal.value = true;
};

const openItemHistoryModal = (item) => {
    selectedItem.value = item;
    itemHistory.value = [];
    loadingHistory.value = true;
    showItemHistoryModal.value = true;
    
    fetch(`/inventory/${item.id}/history`)
        .then(res => res.json())
        .then(data => {
            itemHistory.value = data.movements;
            loadingHistory.value = false;
        });
};

const submitBrand = () => {
    brandForm.post('/brands', {
        onSuccess: () => {
            showBrandModal.value = false;
            brandForm.reset();
        }
    });
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

const submitDeduction = () => {
    deductForm.post(`/inventory/${selectedItem.value.id}/deduct`, {
        onSuccess: () => {
            showDeductModal.value = false;
            deductForm.reset();
        }
    });
};

const submitAdjustment = () => {
    adjustForm.post(`/inventory/${selectedItem.value.id}/adjust`, {
        onSuccess: () => {
            showAdjustModal.value = false;
            adjustForm.reset();
        },
    });
};

const confirmDelete = (id) => {
    if (confirm('Are you sure you want to delete this product?')) {
        router.delete(`/inventory/${id}`);
    }
};

const confirmDeleteBrand = (id) => {
    if (confirm('Are you sure you want to delete this brand? This will delete all products in this brand.')) {
        router.delete(`/brands/${id}`);
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-AE', { style: 'currency', currency: 'AED' }).format(value || 0);
};

const locations = [
    { label: 'Shop', value: 'shop' },
    { label: 'Main Warehouse', value: 'warehouse' },
    { label: 'Remote Warehouse', value: 'remote' },
];

const filteredInventory = computed(() => {
    let items = props.inventory.data;
    
    if (locationTab.value === 'all') return items;
    
    return items.filter(item => {
        if (locationTab.value === 'shop') return item.shop_quantity > 0;
        if (locationTab.value === 'warehouse') return item.warehouse_quantity > 0;
        if (locationTab.value === 'remote') return item.remote_quantity > 0;
        return true;
    });
});

const selectBrand = (brandId) => {
    selectedBrandId.value = brandId;
    viewMode.value = 'products';
    router.get('/inventory', { brand_id: brandId, search: search.value }, { preserveState: true, preserveScroll: true });
};

const goBackToBrands = () => {
    selectedBrandId.value = null;
    viewMode.value = 'brands';
    router.get('/inventory', { search: search.value }, { preserveState: true, preserveScroll: true });
};

const getBrandName = (id) => {
    const b = props.brands.find(b => b.id === id);
    return b ? b.name : 'No Brand';
};
</script>

<template>
    <Head title="Inventory Management" />

    <div class="space-y-6 animate-in fade-in duration-500">
        <!-- Page Header -->
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
            <div class="flex-1">
                <div class="flex items-center gap-4">
                    <button v-if="viewMode === 'products' && selectedBrandId" @click="goBackToBrands" class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all active:scale-95 shadow-sm">
                        <span class="material-symbols-outlined text-slate-600">arrow_back</span>
                    </button>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">
                            {{ viewMode === 'brands' ? 'Inventory Hub' : getBrandName(selectedBrandId) }}
                        </h1>
                        <p class="text-sm text-slate-500 font-medium">Tracking assets across 3 strategic locations</p>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                <div class="relative group flex-1 lg:flex-none lg:w-64">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors">search</span>
                    <input 
                        v-model="search"
                        type="text" 
                        placeholder="Search items..." 
                        class="w-full bg-white border border-slate-200 rounded-2xl pl-12 pr-4 py-3 text-sm font-bold text-slate-900 outline-none focus:ring-4 focus:ring-indigo-50 transition-all placeholder:text-slate-400"
                    />
                </div>

                <div class="bg-slate-100/50 p-1 rounded-2xl flex gap-1 border border-slate-200 shadow-inner">
                    <button 
                        @click="activeTab = 'inventory'"
                        :class="[activeTab === 'inventory' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-400 hover:text-slate-600']"
                        class="px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all"
                    >
                        Live List
                    </button>
                    <button 
                        @click="activeTab = 'history'"
                        :class="[activeTab === 'history' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-400 hover:text-slate-600']"
                        class="px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all"
                    >
                        Stock Log
                    </button>
                </div>
                
                <PrimaryButton v-if="activeTab === 'inventory' && $page.props.auth.user.role !== 'viewer'" @click="openAddModal" class="!bg-indigo-600 h-[46px] px-6 rounded-2xl shadow-lg shadow-indigo-100 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">add_circle</span>
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
        <div v-if="activeTab === 'inventory'">
            
            <!-- BRANDS VIEW -->
            <div v-if="viewMode === 'brands'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="brand in brands" :key="brand.id" 
                    @click="selectBrand(brand.id)"
                    class="group bg-surface-container-lowest border border-outline-variant/20 p-8 rounded-[2rem] shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all cursor-pointer text-center"
                >
                    <div class="w-20 h-20 bg-primary/5 rounded-3xl flex items-center justify-center text-primary mx-auto mb-6 group-hover:bg-primary group-hover:text-on-primary transition-all duration-500">
                        <span class="material-symbols-outlined text-4xl">branding_watermark</span>
                    </div>
                    <h3 class="text-2xl font-headline font-black text-on-surface mb-2">{{ brand.name }}</h3>
                    <p class="text-xs font-bold text-outline uppercase tracking-widest">{{ brand.products_count }} Items</p>
                </div>

                <!-- Empty State -->
                <div v-if="brands.length === 0" class="col-span-full py-20 text-center bg-surface-container-low/20 rounded-[2rem] border-2 border-dashed border-outline-variant/20">
                    <span class="material-symbols-outlined text-6xl text-outline mb-4">branding_watermark</span>
                    <h3 class="text-xl font-headline font-bold text-outline">No brands added yet</h3>
                    <PrimaryButton v-if="$page.props.auth.user.role !== 'viewer'" @click="showBrandModal = true" class="mt-4">Add Your First Brand</PrimaryButton>
                </div>
            </div>

            <!-- PRODUCTS VIEW -->
            <div v-else class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl shadow-sm overflow-hidden flex flex-col">
                
                <!-- Location Tabs (RESTORED ORIGINAL LABELS) -->
                <div class="p-4 border-b border-outline-variant/20 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex gap-2 overflow-x-auto">
                        <button @click="locationTab = 'all'" :class="locationTab === 'all' ? 'bg-primary text-on-primary' : 'bg-surface-container-low text-outline hover:text-on-surface'" class="px-5 py-2 rounded-lg text-xs font-black uppercase tracking-widest transition-all">All Locations</button>
                        <button @click="locationTab = 'shop'" :class="locationTab === 'shop' ? 'bg-primary text-on-primary' : 'bg-surface-container-low text-outline hover:text-on-surface'" class="px-5 py-2 rounded-lg text-xs font-black uppercase tracking-widest transition-all">Shop</button>
                        <button @click="locationTab = 'warehouse'" :class="locationTab === 'warehouse' ? 'bg-primary text-on-primary' : 'bg-surface-container-low text-outline hover:text-on-surface'" class="px-5 py-2 rounded-lg text-xs font-black uppercase tracking-widest transition-all">Main Warehouse</button>
                        <button @click="locationTab = 'remote'" :class="locationTab === 'remote' ? 'bg-primary text-on-primary' : 'bg-surface-container-low text-outline hover:text-on-surface'" class="px-5 py-2 rounded-lg text-xs font-black uppercase tracking-widest transition-all">Remote Warehouse</button>
                    </div>
                    <button @click="goBackToBrands" class="px-4 py-2 bg-surface-container-low text-outline rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-primary/10 hover:text-primary transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">grid_view</span>
                        Switch Brand
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low/50 text-lg font-bold text-outline uppercase tracking-wider">
                                <th class="px-6 py-5">Product Info</th>
                                <th v-if="locationTab === 'all' || locationTab === 'shop'" class="px-6 py-5">Shop</th>
                                <th v-if="locationTab === 'all' || locationTab === 'warehouse'" class="px-6 py-5 text-center">Warehouse</th>
                                <th v-if="locationTab === 'all' || locationTab === 'remote'" class="px-6 py-5 text-center">Remote</th>
                                <th class="px-6 py-5 text-center">Total</th>
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
                                            <span class="w-1 h-1 rounded-full bg-outline-variant/30"></span>
                                            <span class="text-sm text-outline font-medium">{{ getBrandName(item.brand_id) }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td v-if="locationTab === 'all' || locationTab === 'shop'" class="px-6 py-6 text-2xl font-black" :class="item.shop_quantity <= item.low_stock_threshold ? 'text-error' : 'text-on-surface'">{{ item.shop_quantity }}</td>
                                <td v-if="locationTab === 'all' || locationTab === 'warehouse'" class="px-6 py-6 text-2xl font-black text-center text-on-surface">{{ item.warehouse_quantity }}</td>
                                <td v-if="locationTab === 'all' || locationTab === 'remote'" class="px-6 py-6 text-2xl font-black text-center text-on-surface">{{ item.remote_quantity }}</td>
                                <td class="px-6 py-6 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <span class="text-2xl font-black" :class="item.total_quantity <= item.low_stock_threshold ? 'text-error' : 'text-on-surface'">{{ item.total_quantity }}</span>
                                        <span v-if="item.total_quantity <= item.low_stock_threshold" class="material-symbols-outlined text-error text-[20px]">priority_high</span>
                                    </div>
                                </td>
                                <td class="px-6 py-6">
                                    <div class="flex flex-col text-sm font-bold">
                                        <span class="text-outline">Cost: {{ formatCurrency(item.cost_price) }}</span>
                                        <span class="text-primary">Sell: {{ formatCurrency(item.selling_price) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button @click="openItemHistoryModal(item)" class="p-2 text-indigo-500 hover:bg-indigo-50 rounded-lg transition-colors" title="View Product History">
                                            <span class="material-symbols-outlined text-[20px]">history</span>
                                        </button>
                                        <template v-if="$page.props.auth.user.role !== 'viewer'">
                                            <button @click="openAdjustModal(item)" class="p-2 text-emerald-500 hover:bg-emerald-50 rounded-lg transition-colors" title="Increase/Decrease Stock">
                                                <span class="material-symbols-outlined text-[20px]">add_box</span>
                                            </button>
                                            <button @click="openDeductModal(item)" class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors" title="Customer Deduction">
                                                <span class="material-symbols-outlined text-[20px]">person_remove</span>
                                            </button>
                                            <button @click="openTransferModal(item)" class="p-2 text-orange-500 hover:bg-orange-50 rounded-lg transition-colors" title="Transfer">
                                                <span class="material-symbols-outlined text-[20px]">swap_horiz</span>
                                            </button>
                                            <button @click="openEditModal(item)" class="p-2 text-outline hover:text-primary transition-colors">
                                                <span class="material-symbols-outlined text-[20px]">edit</span>
                                            </button>
                                            <button @click="confirmDelete(item.id)" class="p-2 text-outline hover:text-error transition-colors">
                                                <span class="material-symbols-outlined text-[20px]">delete</span>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredInventory.length === 0">
                                <td colspan="7" class="py-20 text-center text-outline italic">No products found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-outline-variant/10">
                    <Pagination :links="inventory.links" :meta="inventory" />
                </div>
            </div>
        </div>

        <!-- History Tab (RESTORED ORIGINAL) -->
        <div v-else class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low/50 text-lg font-bold text-outline uppercase tracking-wider">
                            <th class="px-6 py-5">Date</th>
                            <th class="px-6 py-5">Product</th>
                            <th class="px-6 py-5">Type</th>
                            <th class="px-6 py-5 text-center">Quantity</th>
                            <th class="px-6 py-5">Movement Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <tr v-for="move in movements.data" :key="move.id" class="hover:bg-surface-container-low/30 transition-colors">
                            <td class="px-6 py-5 text-outline text-sm">{{ new Date(move.created_at).toLocaleString() }}</td>
                            <td class="px-6 py-5 font-bold">{{ move.inventory?.name || 'Unknown' }}</td>
                            <td class="px-6 py-5">
                                <Badge :variant="move.type === 'in' ? 'success' : (move.type === 'transfer' ? 'warning' : 'error')" class="px-3 py-1 font-black text-xs">
                                    {{ move.type.toUpperCase() }}
                                </Badge>
                            </td>
                            <td class="px-6 py-5 font-black text-2xl text-center">{{ move.quantity }}</td>
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-2 text-sm text-outline">
                                    <span class="capitalize font-bold text-on-surface">{{ move.from_location || 'Source' }}</span>
                                    <span class="material-symbols-outlined text-xs">arrow_forward</span>
                                    <span class="capitalize font-bold text-on-surface">{{ move.to_location || 'Destination' }}</span>
                                    <span v-if="move.notes" class="ml-4 italic text-[11px] opacity-70">({{ move.notes }})</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="p-4 border-t border-outline-variant/10">
                <Pagination :links="movements.links" :meta="movements" />
            </div>
        </div>

        <!-- Modals (RESTORED ORIGINAL LABELS) -->
        <!-- Add/Edit Product Modal -->
        <SideModal :show="showAddModal" :title="selectedItem ? 'Edit Product' : 'Add Product'" @close="showAddModal = false">
            <form @submit.prevent="submit" class="space-y-4 p-2">
                <FormField label="Brand" :error="form.errors.brand_id" required>
                    <SelectInput v-model="form.brand_id" :options="brands.map(b => ({label: b.name, value: b.id}))" placeholder="Select Brand..." />
                </FormField>
                <FormField label="Product Name" :error="form.errors.name" required>
                    <TextInput v-model="form.name" placeholder="Item Name" />
                </FormField>
                <FormField label="Category" :error="form.errors.category">
                    <TextInput v-model="form.category" placeholder="Electronics, Fashion, etc." />
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

        <!-- Add Brand Modal -->
        <SideModal :show="showBrandModal" title="Add New Brand" @close="showBrandModal = false">
            <form @submit.prevent="submitBrand" class="space-y-4 p-2">
                <FormField label="Brand Name" :error="brandForm.errors.name" required>
                    <TextInput v-model="brandForm.name" placeholder="Brand Name (e.g. Apple, Nike)" />
                </FormField>
                <div class="pt-6 flex justify-end gap-3 border-t border-outline-variant/10 mt-6">
                    <SecondaryButton @click="showBrandModal = false" type="button">Cancel</SecondaryButton>
                    <PrimaryButton :loading="brandForm.processing">Create Brand</PrimaryButton>
                </div>
            </form>
        </SideModal>

        <!-- Transfer Modal -->
        <SideModal :show="showTransferModal" title="Stock Transfer" @close="showTransferModal = false">
            <div v-if="selectedItem" class="p-2 space-y-6">
                <div class="bg-primary/5 p-4 rounded-xl border border-primary/10">
                    <p class="text-[10px] font-bold text-primary uppercase mb-1">Transferring</p>
                    <p class="text-sm font-bold text-on-surface">{{ selectedItem.name }}</p>
                    <div class="flex gap-4 mt-2 text-[10px] font-black text-outline">
                        <span>Shop: {{ selectedItem.shop_quantity }}</span>
                        <span>Warehouse: {{ selectedItem.warehouse_quantity }}</span>
                        <span>Remote: {{ selectedItem.remote_quantity }}</span>
                    </div>
                </div>
                <form @submit.prevent="submitTransfer" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <FormField label="From" required><SelectInput v-model="transferForm.from" :options="locations" /></FormField>
                        <FormField label="To" required><SelectInput v-model="transferForm.to" :options="locations" /></FormField>
                    </div>
                    <FormField label="Quantity" required :error="transferForm.errors.quantity">
                        <TextInput v-model="transferForm.quantity" type="number" />
                    </FormField>
                    <FormField label="Notes"><TextInput v-model="transferForm.notes" placeholder="Reason for transfer..." /></FormField>
                    <div class="pt-6 flex justify-end gap-3 border-t mt-6">
                        <SecondaryButton @click="showTransferModal = false" type="button">Cancel</SecondaryButton>
                        <PrimaryButton :loading="transferForm.processing" class="!bg-orange-600 hover:!bg-orange-700">Confirm Transfer</PrimaryButton>
                    </div>
                </form>
            </div>
        </SideModal>

        <!-- Deduct Modal -->
        <SideModal :show="showDeductModal" title="Deduct for Customer" @close="showDeductModal = false">
            <div v-if="selectedItem" class="p-2 space-y-6">
                <div class="bg-rose-50 p-4 rounded-xl border border-rose-100 text-rose-900">
                    <p class="text-[10px] font-bold uppercase mb-1">Deducting Stock</p>
                    <p class="text-sm font-bold">{{ selectedItem.name }}</p>
                    <div class="flex gap-4 mt-2 text-[10px] font-black opacity-60">
                        <span>Shop: {{ selectedItem.shop_quantity }}</span>
                        <span>Warehouse: {{ selectedItem.warehouse_quantity }}</span>
                        <span>Remote: {{ selectedItem.remote_quantity }}</span>
                    </div>
                </div>
                <form @submit.prevent="submitDeduction" class="space-y-4">
                    <FormField label="Select Customer" required :error="deductForm.errors.customer_id">
                        <SelectInput v-model="deductForm.customer_id" :options="customers.map(c => ({label: c.name, value: c.id}))" placeholder="Choose a customer..." />
                    </FormField>
                    <div class="grid grid-cols-2 gap-4">
                        <FormField label="From Location" required><SelectInput v-model="deductForm.location" :options="locations" /></FormField>
                        <FormField label="Quantity" required :error="deductForm.errors.quantity"><TextInput v-model="deductForm.quantity" type="number" /></FormField>
                    </div>
                    <FormField label="Notes"><TextInput v-model="deductForm.notes" placeholder="e.g. Returned item, sample..." /></FormField>
                    <div class="pt-6 flex justify-end gap-3 border-t mt-6">
                        <SecondaryButton @click="showDeductModal = false" type="button">Cancel</SecondaryButton>
                        <PrimaryButton :loading="deductForm.processing" class="!bg-rose-600 hover:!bg-rose-700">Confirm Deduction</PrimaryButton>
                    </div>
                </form>
            </div>
        </SideModal>

        <!-- Adjustment Modal -->
        <SideModal :show="showAdjustModal" title="Stock Adjustment" @close="showAdjustModal = false">
            <div v-if="selectedItem" class="p-2 space-y-6">
                <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-100 text-emerald-900">
                    <p class="text-[10px] font-bold uppercase mb-1">Adjusting Stock</p>
                    <p class="text-sm font-bold">{{ selectedItem.name }}</p>
                </div>
                <form @submit.prevent="submitAdjustment" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <FormField label="Adjustment Type" required>
                            <SelectInput v-model="adjustForm.type" :options="[{label: 'Increase (+)', value: 'in'}, {label: 'Decrease (-)', value: 'out'}]" />
                        </FormField>
                        <FormField label="Location" required>
                            <SelectInput v-model="adjustForm.location" :options="locations" />
                        </FormField>
                    </div>
                    <FormField label="Quantity" required :error="adjustForm.errors.quantity">
                        <TextInput v-model="adjustForm.quantity" type="number" min="1" />
                    </FormField>
                    <FormField label="Reason / Notes"><TextInput v-model="adjustForm.notes" placeholder="e.g. New stock, damage, correction..." /></FormField>
                    <div class="pt-6 flex justify-end gap-3 border-t mt-6">
                        <SecondaryButton @click="showAdjustModal = false" type="button">Cancel</SecondaryButton>
                        <PrimaryButton :loading="adjustForm.processing" class="!bg-emerald-600 hover:!bg-emerald-700">Update Stock</PrimaryButton>
                    </div>
                </form>
            </div>
        </SideModal>

        <!-- Product History Modal -->
        <SideModal :show="showItemHistoryModal" :title="`History: ${selectedItem?.name}`" @close="showItemHistoryModal = false">
            <div class="space-y-4 p-2">
                <div v-if="loadingHistory" class="flex justify-center py-10">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                </div>
                <div v-else-if="itemHistory.length === 0" class="text-center py-10 text-outline italic">
                    No history found for this product.
                </div>
                <div v-else class="space-y-4">
                    <div v-for="move in itemHistory" :key="move.id" class="p-4 bg-surface-container-low/30 rounded-2xl border border-outline-variant/10">
                        <div class="flex justify-between items-start mb-2">
                            <Badge :variant="move.type === 'in' ? 'success' : (move.type === 'transfer' ? 'warning' : 'error')" class="px-3 py-1 font-black text-[10px]">
                                {{ move.type.toUpperCase() }}
                            </Badge>
                            <span class="text-[10px] text-outline">{{ new Date(move.created_at).toLocaleString() }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="text-xs text-on-surface">
                                <span class="capitalize font-bold">{{ move.from_location || 'Source' }}</span>
                                <span class="material-symbols-outlined text-[10px] mx-1">arrow_forward</span>
                                <span class="capitalize font-bold">{{ move.to_location || 'Destination' }}</span>
                            </div>
                            <span class="text-xl font-black">{{ move.quantity }} units</span>
                        </div>
                        <p v-if="move.notes" class="mt-2 text-[11px] text-outline italic">{{ move.notes }}</p>
                    </div>
                </div>
            </div>
            <template #footer>
                <SecondaryButton @click="showItemHistoryModal = false" class="w-full">Close History</SecondaryButton>
            </template>
        </SideModal>
    </div>
</template>

<style scoped>
.font-black { font-weight: 900; }
.tracking-tight { letter-spacing: -0.025em; }
</style>
