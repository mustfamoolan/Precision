<script setup>
import { ref } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SideModal from '@/Components/SideModal.vue';
import FormField from '@/Components/FormField.vue';
import TextInput from '@/Components/TextInput.vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    employees: Array,
    customers: Array,
});

const activeTab = ref('employees'); // 'employees' or 'customers'
const showModal = ref(false);
const editingContact = ref(null);

const form = useForm({
    name: '',
});

const openModal = (contact = null) => {
    if (contact) {
        editingContact.value = contact;
        form.name = contact.name;
    } else {
        editingContact.value = null;
        form.reset();
    }
    showModal.value = true;
};

const submit = () => {
    const endpoint = activeTab.value === 'employees' ? '/employees' : '/customers';
    
    if (editingContact.value) {
        form.put(`${endpoint}/${editingContact.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post(endpoint, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const destroy = (id) => {
    if (confirm('Are you sure you want to delete this record?')) {
        const endpoint = activeTab.value === 'employees' ? '/employees' : '/customers';
        router.delete(`${endpoint}/${id}`);
    }
};
</script>

<template>
    <Head title="Contacts Directory" />

    <div class="space-y-6 animate-in fade-in duration-500">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-headline font-bold text-on-surface tracking-tight">Contacts Directory</h1>
                <p class="text-sm text-outline font-label">Manage your team and your clients</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="bg-surface-container-low p-1 rounded-xl flex gap-1 border border-outline-variant/10">
                    <button 
                        @click="activeTab = 'employees'"
                        :class="[activeTab === 'employees' ? 'bg-primary text-on-primary shadow-sm' : 'text-outline hover:text-on-surface']"
                        class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all"
                    >
                        Employees
                    </button>
                    <button 
                        @click="activeTab = 'customers'"
                        :class="[activeTab === 'customers' ? 'bg-primary text-on-primary shadow-sm' : 'text-outline hover:text-on-surface']"
                        class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all"
                    >
                        Customers
                    </button>
                </div>
                <PrimaryButton @click="openModal()" class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Add {{ activeTab === 'employees' ? 'Employee' : 'Customer' }}
                </PrimaryButton>
            </div>
        </div>

        <!-- Employees List -->
        <div v-if="activeTab === 'employees'" class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl shadow-sm overflow-hidden flex flex-col">
            <div class="p-5 border-b border-outline-variant/20 flex items-center gap-3">
                <div class="w-10 h-10 bg-primary/10 text-primary rounded-xl flex items-center justify-center">
                    <span class="material-symbols-outlined">badge</span>
                </div>
                <div>
                    <h3 class="text-sm font-headline font-bold text-on-surface uppercase tracking-widest">Internal Team</h3>
                    <p class="text-[10px] font-bold text-outline uppercase tracking-widest">{{ employees.length }} Employees</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low/50 text-xl font-bold text-outline uppercase tracking-wider">
                            <th class="px-6 py-6 w-20">ID</th>
                            <th class="px-6 py-6">Full Name</th>
                            <th class="px-6 py-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <tr v-for="emp in employees" :key="'emp'+emp.id" class="hover:bg-surface-container-low/30 transition-colors group">
                            <td class="px-6 py-6 text-lg font-bold text-outline">#{{ emp.id }}</td>
                            <td class="px-6 py-6">
                                <span class="text-2xl font-bold text-on-surface">{{ emp.name }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click="openModal(emp)" class="p-1.5 text-outline hover:text-primary transition-colors"><span class="material-symbols-outlined text-[18px]">edit</span></button>
                                    <button @click="destroy(emp.id)" class="p-1.5 text-outline hover:text-error transition-colors"><span class="material-symbols-outlined text-[18px]">delete</span></button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="employees.length === 0">
                            <td colspan="3" class="py-12 text-center text-outline text-xs">No employees found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Customers List -->
        <div v-if="activeTab === 'customers'" class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl shadow-sm overflow-hidden flex flex-col">
            <div class="p-5 border-b border-outline-variant/20 flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-500/10 text-indigo-600 rounded-xl flex items-center justify-center">
                    <span class="material-symbols-outlined">groups</span>
                </div>
                <div>
                    <h3 class="text-sm font-headline font-bold text-on-surface uppercase tracking-widest">Client Roster</h3>
                    <p class="text-[10px] font-bold text-outline uppercase tracking-widest">{{ customers.length }} Customers</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low/50 text-xl font-bold text-outline uppercase tracking-wider">
                            <th class="px-6 py-6 w-20">ID</th>
                            <th class="px-6 py-6">Full Name</th>
                            <th class="px-6 py-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <tr v-for="cust in customers" :key="'cust'+cust.id" class="hover:bg-surface-container-low/30 transition-colors group">
                            <td class="px-6 py-6 text-lg font-bold text-outline">#{{ cust.id }}</td>
                            <td class="px-6 py-6">
                                <span class="text-2xl font-bold text-on-surface">{{ cust.name }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click="openModal(cust)" class="p-1.5 text-outline hover:text-primary transition-colors"><span class="material-symbols-outlined text-[18px]">edit</span></button>
                                    <button @click="destroy(cust.id)" class="p-1.5 text-outline hover:text-error transition-colors"><span class="material-symbols-outlined text-[18px]">delete</span></button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="customers.length === 0">
                            <td colspan="3" class="py-12 text-center text-outline text-xs">No customers found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <SideModal :show="showModal" :title="(editingContact ? 'Edit ' : 'Add ') + (activeTab === 'employees' ? 'Employee' : 'Customer')" @close="showModal = false">
            <form @submit.prevent="submit" class="space-y-6 p-2">
                <FormField label="Full Name" :error="form.errors.name" required>
                    <TextInput v-model="form.name" placeholder="John Doe" />
                </FormField>

                <div class="pt-6 flex justify-end gap-3 border-t border-outline-variant/10 mt-6">
                    <SecondaryButton @click="showModal = false" type="button">Cancel</SecondaryButton>
                    <PrimaryButton :loading="form.processing">Save</PrimaryButton>
                </div>
            </form>
        </SideModal>
    </div>
</template>
