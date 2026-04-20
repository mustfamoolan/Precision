<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import SideModal from '@/Components/SideModal.vue';
import FormField from '@/Components/FormField.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    inventory: Array,
});

const activeTab = ref('shop'); // 'shop', 'warehouse'
const searchQuery = ref('');
const successToast = ref(null);

// Clear toast after 3s
watch(successToast, (val) => {
    if (val) setTimeout(() => successToast.value = null, 3000);
});

// "Each has its products" logic: Filter list based on active tab stock
const filteredInventory = computed(() => {
    let items = props.inventory;
    
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        items = items.filter(i => i.name.toLowerCase().includes(query) || (i.sku && i.sku.toLowerCase().includes(query)));
    }
    
    // Strict filtering: Only show products that exist in this store
    if (activeTab.value === 'shop') return items.filter(i => i.shop_quantity > 0);
    if (activeTab.value === 'warehouse') return items.filter(i => i.warehouse_quantity > 0);
    return items;
});

// Modals State
const isAddModalOpen = ref(false);
const isTransferModalOpen = ref(false);
const isEditModalOpen = ref(false);
const selectedItem = ref(null);

const addForm = useForm({
    name: '',
    sku: '',
    selling_price: '',
    quantity: 1,
    location: 'shop',
});

const transferForm = useForm({
    quantity: 1,
    from: 'warehouse',
    to: 'shop',
});

const editForm = useForm({
    name: '',
    sku: '',
    selling_price: '',
    shop_quantity: 0,
    warehouse_quantity: 0,
});

const openAddModal = () => {
    addForm.reset();
    addForm.clearErrors();
    addForm.location = activeTab.value;
    isAddModalOpen.value = true;
};

const openTransferModal = (record) => {
    selectedItem.value = record;
    transferForm.reset();
    transferForm.clearErrors();
    transferForm.from = activeTab.value;
    transferForm.to = activeTab.value === 'shop' ? 'warehouse' : 'shop';
    isTransferModalOpen.value = true;
};

const openEditModal = (record) => {
    selectedItem.value = record;
    editForm.reset();
    editForm.clearErrors();
    editForm.name = record.name;
    editForm.sku = record.sku;
    editForm.selling_price = record.selling_price;
    editForm.shop_quantity = record.shop_quantity;
    editForm.warehouse_quantity = record.warehouse_quantity;
    isEditModalOpen.value = true;
};

const submitAdd = () => {
    addForm.transform((data) => ({
        ...data,
        shop_quantity: data.location === 'shop' ? data.quantity : 0,
        warehouse_quantity: data.location === 'warehouse' ? data.quantity : 0,
    })).post('/inventory', {
        onSuccess: () => {
            isAddModalOpen.value = false;
            successToast.value = 'Product added successfully!';
        }
    });
};

const submitTransfer = () => {
    transferForm.post(`/inventory/${selectedItem.value.id}/transfer`, {
        preserveScroll: true,
        onSuccess: () => {
            isTransferModalOpen.value = false;
            successToast.value = 'Stock transferred successfully!';
        }
    });
};

const submitEdit = () => {
    editForm.put(`/inventory/${selectedItem.value.id}`, {
        onSuccess: () => {
            isEditModalOpen.value = false;
            successToast.value = 'Product information updated.';
        }
    });
};

const deleteProduct = (id) => {
    if (confirm('Are you sure you want to remove this product from inventory?')) {
        router.delete(`/inventory/${id}`, {
            onSuccess: () => successToast.value = 'Product removed from system.'
        });
    }
};

const formatPrice = (price) => {
    return price ? `AED ${parseFloat(price).toFixed(2)}` : 'N/A';
};
</script>

<template>
    <Head title="Inventory Management" />

    <div class="space-y-6 lg:space-y-10 animate-in fade-in duration-700 pb-12 relative">
        
        <!-- Toast Notification -->
        <Transition
            enter-active-class="transform transition ease-out duration-300"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="successToast" class="fixed top-8 right-8 z-[100] bg-on-surface text-surface px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-3 border border-outline-variant/10">
                <span class="material-symbols-outlined text-primary-container fill-1">check_circle</span>
                <span class="font-label font-bold text-sm uppercase tracking-widest">{{ successToast }}</span>
            </div>
        </Transition>

        <!-- Page Header -->
        <div class="flex flex-col lg:flex-row lg:justify-between lg:items-end gap-6 px-4 sm:px-0">
            <div class="max-w-2xl">
                <h1 class="font-headline text-3xl lg:text-5xl font-bold text-on-surface tracking-tight mb-2">Inventory</h1>
                <p class="font-body text-on-surface-variant text-sm lg:text-base">Coordinate your product flow between the storefront and safe storage.</p>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-4 items-stretch sm:items-center">
                <div class="relative group flex-1 sm:min-w-[280px]">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-outline">
                        <span class="material-symbols-outlined text-lg">search</span>
                    </span>
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        placeholder="Search items..." 
                        class="pl-10 pr-4 py-3 bg-surface-container-highest/50 border border-outline-variant/20 rounded-2xl text-sm font-label focus:ring-2 focus:ring-primary/20 w-full transition-all outline-none"
                    >
                </div>
                <button 
                    @click="openAddModal"
                    class="bg-primary text-on-primary rounded-2xl px-6 py-4 font-label font-bold text-xs uppercase tracking-widest shadow-lg flex items-center justify-center gap-2 hover:opacity-90 transition-all active:scale-95"
                >
                    <span class="material-symbols-outlined text-[20px]">add_circle</span>
                    New Product
                </button>
            </div>
        </div>

        <!-- Store Switcher (Tabs) -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between border-b border-outline-variant/10 pb-4 gap-4 px-4 sm:px-0">
            <div class="flex gap-1 p-1 bg-surface-container-low rounded-2xl border border-outline-variant/10">
                <button 
                    @click="activeTab = 'shop'"
                    class="flex-1 sm:flex-none rounded-xl px-4 lg:px-8 py-3 font-label text-[10px] sm:text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center gap-2"
                    :class="activeTab === 'shop' ? 'bg-surface-container-lowest text-primary shadow-sm' : 'text-on-surface-variant hover:text-on-surface'"
                >
                    <span class="material-symbols-outlined text-lg" :class="activeTab === 'shop' ? 'fill-1' : ''">storefront</span>
                    <span class="hidden xs:inline">Shop Store</span>
                    <span class="xs:hidden">Shop</span>
                </button>
                <button 
                    @click="activeTab = 'warehouse'"
                    class="flex-1 sm:flex-none rounded-xl px-4 lg:px-8 py-3 font-label text-[10px] sm:text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center gap-2"
                    :class="activeTab === 'warehouse' ? 'bg-surface-container-lowest text-secondary shadow-sm' : 'text-on-surface-variant hover:text-on-surface'"
                >
                    <span class="material-symbols-outlined text-lg" :class="activeTab === 'warehouse' ? 'fill-1' : ''">warehouse</span>
                    <span class="hidden xs:inline">Warehouse Store</span>
                    <span class="xs:hidden">W.House</span>
                </button>
            </div>

            <div class="flex items-center justify-center sm:justify-end gap-6 bg-surface-container-low/30 sm:bg-transparent p-3 sm:p-0 rounded-xl">
                <div class="text-center sm:text-right">
                    <p class="text-[9px] font-black text-outline uppercase tracking-widest leading-none mb-1">Global Catalog</p>
                    <p class="text-base lg:text-lg font-headline font-black text-on-surface leading-none">{{ inventory.length }} SKUs</p>
                </div>
            </div>
        </div>

        <!-- Inventory List (Responsive Table/Cards) -->
        <div class="bg-surface-container-lowest sm:rounded-3xl shadow-ambient border-y sm:border border-outline-variant/10 overflow-hidden">
            <!-- Desktop Table View -->
            <div class="hidden md:block w-full overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low/30 text-[10px] font-label font-bold text-outline-variant uppercase tracking-widest border-b border-outline-variant/10">
                            <th class="px-8 py-5">Product Identity</th>
                            <th class="px-8 py-5 text-center">Store Inventory</th>
                            <th class="px-8 py-5 text-center">Price Unit</th>
                            <th class="px-8 py-5 text-right flex justify-end">Sector Control</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <tr 
                            v-for="item in filteredInventory" 
                            :key="item.id" 
                            class="group hover:bg-primary/[0.02] transition-colors"
                        >
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl bg-surface-container-low flex items-center justify-center text-outline-variant group-hover:bg-primary/5 group-hover:text-primary transition-all shrink-0">
                                        <span class="material-symbols-outlined">inventory_2</span>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="font-headline font-extrabold text-on-surface text-base lg:text-lg truncate group-hover:text-primary transition-colors">{{ item.name }}</h3>
                                        <p class="font-body text-[11px] text-outline mt-0.5 tracking-tight font-medium uppercase truncate">{{ item.sku || 'NO SKU' }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-8 py-6 text-center">
                                <div v-if="activeTab === 'shop'" class="flex flex-col items-center">
                                    <div class="text-xl lg:text-2xl font-headline font-black text-primary">{{ item.shop_quantity }}</div>
                                    <div class="text-[10px] font-black text-primary/60 uppercase tracking-widest">In Shop</div>
                                </div>
                                <div v-if="activeTab === 'warehouse'" class="flex flex-col items-center text-secondary">
                                    <div class="text-xl lg:text-2xl font-headline font-black">{{ item.warehouse_quantity }}</div>
                                    <div class="text-[10px] font-black opacity-60 uppercase tracking-widest">In Warehouse</div>
                                </div>
                            </td>

                            <td class="px-8 py-6 text-center font-headline font-black text-on-surface text-base lg:text-lg">
                                {{ formatPrice(item.selling_price) }}
                            </td>

                            <td class="px-8 py-6 text-right">
                                <div class="flex justify-end items-center gap-1">
                                    <button @click="openTransferModal(item)" class="p-3 rounded-xl text-outline-variant hover:text-secondary hover:bg-secondary/10 transition-all active:scale-90" title="Transfer">
                                        <span class="material-symbols-outlined text-[24px]">switch_access_shortcut</span>
                                    </button>
                                    <button @click="openEditModal(item)" class="p-3 rounded-xl text-outline-variant hover:text-primary hover:bg-primary/10 transition-all active:scale-90" title="Edit">
                                        <span class="material-symbols-outlined text-[24px]">edit_note</span>
                                    </button>
                                    <button @click="deleteProduct(item.id)" class="p-3 rounded-xl text-outline-variant hover:text-error hover:bg-error/10 transition-all active:scale-90" title="Delete">
                                        <span class="material-symbols-outlined text-[24px]">delete_sweep</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="md:hidden divide-y divide-outline-variant/10">
                <div v-for="item in filteredInventory" :key="item.id" class="p-6 space-y-4 hover:bg-surface-container-low/50">
                    <div class="flex justify-between items-start">
                        <div class="min-w-0 pr-4">
                            <h3 class="font-headline font-bold text-on-surface text-lg truncate">{{ item.name }}</h3>
                            <p class="text-[10px] font-black text-outline uppercase tracking-widest mt-1">{{ item.sku || 'NO SKU' }} • {{ formatPrice(item.selling_price) }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-2xl font-headline font-black" :class="activeTab === 'shop' ? 'text-primary' : 'text-secondary'">
                                {{ activeTab === 'shop' ? item.shop_quantity : item.warehouse_quantity }}
                            </p>
                            <p class="text-[8px] font-black text-outline uppercase tracking-tighter">{{ activeTab === 'shop' ? 'Shop' : 'Warehouse' }}</p>
                        </div>
                    </div>
                    
                    <div class="flex gap-2 pt-2">
                        <button @click="openTransferModal(item)" class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl bg-secondary/10 text-secondary font-label text-[10px] font-black uppercase tracking-widest active:scale-95 transition-all">
                            <span class="material-symbols-outlined text-lg">switch_access_shortcut</span>
                            Transfer
                        </button>
                        <button @click="openEditModal(item)" class="w-12 flex items-center justify-center py-3 rounded-xl bg-primary/10 text-primary active:scale-95 transition-all">
                            <span class="material-symbols-outlined text-lg">edit</span>
                        </button>
                        <button @click="deleteProduct(item.id)" class="w-12 flex items-center justify-center py-3 rounded-xl bg-error/10 text-error active:scale-95 transition-all">
                            <span class="material-symbols-outlined text-lg">delete</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="filteredInventory.length === 0" class="py-24 text-center">
                <div class="w-20 h-20 bg-surface-container-high rounded-full flex items-center justify-center mx-auto mb-6 opacity-30">
                    <span class="material-symbols-outlined text-4xl">inventory</span>
                </div>
                <h3 class="text-xl font-headline font-black text-on-surface mb-2">Sector Empty</h3>
                <p class="text-on-surface-variant max-w-xs mx-auto text-sm font-medium">No items currently registered in this store location.</p>
            </div>
        </div>

        <!-- MODALS -->

        <!-- ADD PRODUCT MODAL -->
        <SideModal :show="isAddModalOpen" :title="`New ${addForm.location === 'shop' ? 'Shop' : 'Warehouse'} Entry`" @close="isAddModalOpen = false">
            <div class="space-y-6">
                <FormField label="Product Name" :error="addForm.errors.name" required>
                    <TextInput v-model="addForm.name" placeholder="Full descriptive identity" autofocus />
                </FormField>

                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Initial Quant" :error="addForm.errors.quantity" required>
                        <TextInput v-model="addForm.quantity" type="number" />
                    </FormField>
                    <FormField label="Primary Location" :error="addForm.errors.location" required>
                        <SelectInput v-model="addForm.location" :options="[{label: 'Shop Store', value: 'shop'}, {label: 'Warehouse Store', value: 'warehouse'}]" />
                    </FormField>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <FormField label="SKU / Ident" :error="addForm.errors.sku">
                        <TextInput v-model="addForm.sku" placeholder="SKU-XXXX" />
                    </FormField>
                    <FormField label="Retail Unit Price" :error="addForm.errors.selling_price">
                        <TextInput v-model="addForm.selling_price" type="number" step="0.01" />
                    </FormField>
                </div>
            </div>
            <template #footer>
                <SecondaryButton @click="isAddModalOpen = false">Dismiss</SecondaryButton>
                <PrimaryButton @click="submitAdd" :loading="addForm.processing">Register Product</PrimaryButton>
            </template>
        </SideModal>

        <!-- TRANSFER MODAL -->
        <SideModal :show="isTransferModalOpen" title="Global Ledger Transfer" @close="isTransferModalOpen = false" maxWidth="sm:w-[480px]">
            <div class="space-y-8">
                <!-- ERROR ALERT BANNER -->
                <Transition
                    enter-active-class="transform transition ease-out duration-300"
                    enter-from-class="-translate-y-4 opacity-0"
                    enter-to-class="translate-y-0 opacity-100"
                >
                    <div v-if="transferForm.errors.quantity" class="bg-error-container text-on-error-container p-4 rounded-2xl flex items-start gap-3 border border-error/20">
                        <span class="material-symbols-outlined text-error shrink-0">error</span>
                        <div class="space-y-1">
                            <p class="font-label font-black text-[11px] uppercase tracking-widest leading-none">Security Alert / Rejection</p>
                            <p class="font-body text-sm font-bold">{{ transferForm.errors.quantity }}</p>
                        </div>
                    </div>
                </Transition>

                <div class="text-center">
                    <h4 class="font-headline font-black text-on-surface text-xl uppercase tracking-tighter">{{ selectedItem?.name }}</h4>
                    <p class="text-[10px] font-black text-outline uppercase tracking-[0.2em] mt-1">Cross-Sector stock movement</p>
                </div>

                <!-- Direction Control -->
                <div class="grid grid-cols-2 gap-3 p-1 bg-surface-container-low rounded-3xl border border-outline-variant/10">
                    <button 
                        type="button" @click="transferForm.from = 'warehouse'; transferForm.to = 'shop'"
                        class="flex flex-col items-center justify-center p-6 rounded-2xl border-2 transition-all gap-2"
                        :class="transferForm.from === 'warehouse' ? 'bg-surface-container-lowest border-secondary text-secondary shadow-md' : 'bg-transparent border-transparent text-outline opacity-40 hover:opacity-100'"
                    >
                        <span class="material-symbols-outlined text-3xl font-black">move_up</span>
                        <span class="text-[10px] font-black uppercase tracking-widest">W.House → Shop</span>
                    </button>
                    <button 
                        type="button" @click="transferForm.from = 'shop'; transferForm.to = 'warehouse'"
                        class="flex flex-col items-center justify-center p-6 rounded-2xl border-2 transition-all gap-2"
                        :class="transferForm.from === 'shop' ? 'bg-surface-container-lowest border-secondary text-secondary shadow-md' : 'bg-transparent border-transparent text-outline opacity-40 hover:opacity-100'"
                    >
                        <span class="material-symbols-outlined text-3xl font-black">move_down</span>
                        <span class="text-[10px] font-black uppercase tracking-widest">Shop → W.House</span>
                    </button>
                </div>

                <div class="bg-surface-container-low rounded-3xl p-8 space-y-4">
                    <label class="text-[10px] font-black text-outline uppercase tracking-[0.3em] text-center block leading-none">Protocol Quantity</label>
                    <div class="flex items-center justify-center gap-8">
                        <button type="button" @click="transferForm.quantity = Math.max(1, transferForm.quantity - 1)" class="w-14 h-14 rounded-2xl bg-surface-container-high flex items-center justify-center text-on-surface hover:bg-outline-variant/20 transition-all active:scale-90 pb-1 border border-outline-variant/10">
                            <span class="material-symbols-outlined text-2xl font-black">remove</span>
                        </button>
                        <input v-model="transferForm.quantity" type="number" class="w-24 bg-transparent border-none text-center text-5xl font-headline font-black text-on-surface focus:ring-0 p-0">
                        <button type="button" @click="transferForm.quantity++" class="w-14 h-14 rounded-2xl bg-surface-container-high flex items-center justify-center text-on-surface hover:bg-outline-variant/20 transition-all active:scale-90 pb-1 border border-outline-variant/10">
                            <span class="material-symbols-outlined text-2xl font-black">add</span>
                        </button>
                    </div>
                </div>

                <div class="p-6 rounded-2xl bg-primary/[0.03] border border-primary/10 text-center space-y-1">
                    <p class="text-[9px] font-black text-outline uppercase tracking-widest">Availability Check</p>
                    <p class="text-sm font-bold text-on-surface-variant font-body">Available: <span class="text-primary">{{ transferForm.from === 'shop' ? selectedItem?.shop_quantity : selectedItem?.warehouse_quantity }} units</span></p>
                </div>
            </div>
            <template #footer>
                <SecondaryButton @click="isTransferModalOpen = false">Abort</SecondaryButton>
                <PrimaryButton @click="submitTransfer" :loading="transferForm.processing" class="h-14 font-black tracking-[0.1em]">
                    EXECUTE MOVEMENT
                </PrimaryButton>
            </template>
        </SideModal>

        <!-- EDIT MODAL -->
        <SideModal :show="isEditModalOpen" title="Identity Refinement" @close="isEditModalOpen = false">
            <div class="space-y-6">
                <FormField label="Product Name" :error="editForm.errors.name" required>
                    <TextInput v-model="editForm.name" />
                </FormField>
                <div class="grid grid-cols-2 gap-4">
                    <FormField label="SKU / System Ident" :error="editForm.errors.sku">
                        <TextInput v-model="editForm.sku" />
                    </FormField>
                    <FormField label="Unit Retail Price" :error="editForm.errors.selling_price">
                        <TextInput v-model="editForm.selling_price" type="number" step="0.01" />
                    </FormField>
                </div>
                <div class="p-6 rounded-3xl bg-surface-container-low border border-outline-variant/20 space-y-6">
                    <h4 class="text-[10px] font-black text-outline uppercase tracking-widest mb-2 text-center">Manual Stock Audit</h4>
                    <div class="grid grid-cols-2 gap-6">
                        <FormField label="Shop Quant" :error="editForm.errors.shop_quantity">
                            <TextInput v-model="editForm.shop_quantity" type="number" class="text-center font-black text-primary text-xl" />
                        </FormField>
                        <FormField label="Warehouse Quant" :error="editForm.errors.warehouse_quantity">
                            <TextInput v-model="editForm.warehouse_quantity" type="number" class="text-center font-black text-secondary text-xl" />
                        </FormField>
                    </div>
                </div>
            </div>
            <template #footer>
                <SecondaryButton @click="isEditModalOpen = false">Cancel</SecondaryButton>
                <PrimaryButton @click="submitEdit" :loading="editForm.processing">Update Identity</PrimaryButton>
            </template>
        </SideModal>
    </div>
</template>

<style scoped>
.shadow-ambient {
    box-shadow: 0 30px 60px -12px rgba(25, 28, 30, 0.12);
}
.fill-1 {
    font-variation-settings: 'FILL' 1;
}
@media (max-width: 400px) {
    .xs\:hidden { display: inline; }
    .xs\:inline { display: none; }
}

/* Chrome, Safari, Edge, Opera */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Firefox */
input[type=number] {
  -moz-appearance: textfield;
}
</style>
