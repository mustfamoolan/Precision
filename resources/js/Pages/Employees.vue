<script setup>
import { ref, computed } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import SideModal from '@/Components/SideModal.vue';
import FormField from '@/Components/FormField.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    employees: Array,
});

const showModal = ref(false);
const editingEmployee = ref(null);

const form = useForm({
    name: '',
});

const openAddModal = () => {
    editingEmployee.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (employee) => {
    editingEmployee.value = employee;
    form.name = employee.name;
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    if (editingEmployee.value) {
        form.put(`/employees/${editingEmployee.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post('/employees', {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const confirmDelete = (id) => {
    if (confirm('Are you sure you want to delete this employee?')) {
        router.delete(`/employees/${id}`);
    }
};

const getInitials = (name) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().substr(0, 2);
};
</script>

<template>
    <Head title="Employees Management" />

    <div class="space-y-8 animate-in fade-in duration-500">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 pb-4">
            <div class="space-y-2 max-w-xl">
                <h1 class="text-[2rem] font-headline font-bold text-on-background tracking-tight">Employees</h1>
                <p class="text-on-surface-variant font-body">Manage your team members and their roles across the organization.</p>
            </div>
            
            <PrimaryButton @click="openAddModal" class="w-fit flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">add</span>
                Add Employee
            </PrimaryButton>
        </div>

        <!-- Employees Roster (Bento List) -->
        <div class="bg-surface-container-lowest rounded-xl p-2 border border-outline-variant/10 shadow-sm transition-colors">
            <!-- Table Header -->
            <div class="hidden md:grid grid-cols-12 gap-4 px-6 py-4 mb-2 text-sm font-label font-bold text-on-surface-variant border-b border-outline-variant/20">
                <div class="col-span-5">Name</div>
                <div class="col-span-4">Role / Department</div>
                <div class="col-span-3 text-right">Actions</div>
            </div>

            <!-- Employee Rows -->
            <div class="flex flex-col gap-2">
                <div 
                    v-for="(employee, index) in employees" 
                    :key="employee.id"
                    class="bg-surface-container-low rounded-lg p-6 md:px-6 md:py-4 flex flex-col md:grid md:grid-cols-12 md:items-center gap-4 hover:bg-surface-container-highest transition-colors group"
                >
                    <div class="col-span-5 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-secondary-container flex items-center justify-center text-on-secondary-container font-headline font-bold text-sm">
                            {{ getInitials(employee.name) }}
                        </div>
                        <div>
                            <h3 class="font-label font-semibold text-on-background group-hover:text-primary transition-colors">{{ employee.name }}</h3>
                            <p class="text-xs font-body text-on-surface-variant md:hidden">General Staff</p>
                        </div>
                    </div>

                    <div class="col-span-4 hidden md:block">
                        <p class="font-label text-sm text-on-background">Staff Member</p>
                        <p class="font-body text-xs text-on-surface-variant uppercase tracking-widest">Employee #{{ employee.id }}</p>
                    </div>

                    <div class="col-span-3 flex justify-end gap-2 md:opacity-0 group-hover:opacity-100 transition-opacity">
                        <button 
                            @click="openEditModal(employee)"
                            class="p-2 text-on-surface-variant hover:text-primary transition-colors rounded-lg hover:bg-surface-container-lowest shadow-sm md:shadow-none"
                            title="Edit"
                        >
                            <span class="material-symbols-outlined text-[20px]">edit</span>
                        </button>
                        <button 
                            @click="confirmDelete(employee.id)"
                            class="p-2 text-on-surface-variant hover:text-error transition-colors rounded-lg hover:bg-error-container/50 shadow-sm md:shadow-none"
                            title="Delete"
                        >
                            <span class="material-symbols-outlined text-[20px]">delete</span>
                        </button>
                    </div>
                </div>

                <div v-if="employees.length === 0" class="p-12 text-center text-outline">
                    No employees found. Start by adding your first team member!
                </div>
            </div>

            <!-- Pagination/Footer -->
            <div class="mt-4 pt-4 border-t border-outline-variant/10 flex justify-between items-center px-4 mb-2">
                <p class="text-xs font-body text-outline uppercase tracking-widest font-bold">Personnel Roster</p>
                <div class="flex gap-2">
                    <button class="px-3 py-1.5 rounded-md border border-outline-variant/10 text-xs font-label text-on-surface-variant hover:bg-surface-container-low transition-colors disabled:opacity-50" disabled>Previous</button>
                    <button class="px-3 py-1.5 rounded-md border border-outline-variant/10 text-xs font-label text-on-surface-variant hover:bg-surface-container-low transition-colors">Next</button>
                </div>
            </div>
        </div>

        <!-- Add/Edit Side Drawer -->
        <SideModal 
            :show="showModal" 
            :title="editingEmployee ? 'Edit Employee' : 'New Employee'" 
            @close="showModal = false"
        >
            <form @submit.prevent="submit" class="space-y-6">
                <FormField label="Full Name" :error="form.errors.name" required>
                    <TextInput 
                        v-model="form.name" 
                        placeholder="e.g. Sarah Jenkins" 
                        autofocus 
                        :error="!!form.errors.name"
                    />
                </FormField>

                <!-- Footer -->
                <div class="pt-6 flex justify-end gap-3 mt-12">
                    <SecondaryButton @click="showModal = false" type="button">Cancel</SecondaryButton>
                    <PrimaryButton :loading="form.processing" :disabled="form.processing">
                        {{ editingEmployee ? 'Update Details' : 'Save Employee' }}
                    </PrimaryButton>
                </div>
            </form>
        </SideModal>
    </div>
</template>

<style>
.font-headline { font-family: 'Manrope', sans-serif; }
.font-label { font-family: 'Inter', sans-serif; }
</style>
