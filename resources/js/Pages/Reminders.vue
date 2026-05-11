<script setup>
import { ref, computed } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import SideModal from '@/Components/SideModal.vue';
import FormField from '@/Components/FormField.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Pagination from '@/Components/Pagination.vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    reminders: Object,
    kpi: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const isModalOpen = ref(false);
const editingReminder = ref(null);

const handleFilter = () => {
    router.get('/reminders', {
        search: search.value,
        status: statusFilter.value,
    }, { preserveState: true, preserveScroll: true });
};

const openModal = (reminder = null) => {
    if (reminder) {
        editingReminder.value = reminder;
        form.date = reminder.date;
        form.item = reminder.item;
        form.quantity = reminder.quantity || '';
        form.unit = reminder.unit || '';
        form.notes = reminder.notes || '';
        form.status = reminder.status;
    } else {
        editingReminder.value = null;
        form.reset();
        // set default date to today
        form.date = new Date().toISOString().split('T')[0];
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    editingReminder.value = null;
};

const submitForm = () => {
    if (editingReminder.value) {
        form.put(`/reminders/${editingReminder.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/reminders', {
            onSuccess: () => closeModal(),
        });
    }
};

const destroy = (id) => {
    if (confirm('Are you sure you want to delete this reminder?')) {
        router.delete(`/reminders/${id}`);
    }
};

const getStatusBadge = (status) => {
    switch(status) {
        case 'pending': return 'bg-amber-100 text-amber-700';
        case 'in_progress': return 'bg-blue-100 text-blue-700';
        case 'done': return 'bg-emerald-100 text-emerald-700';
        default: return 'bg-surface-container-high text-on-surface';
    }
};

const getStatusIcon = (status) => {
    switch(status) {
        case 'pending': return 'schedule';
        case 'in_progress': return 'pending_actions';
        case 'done': return 'check_circle';
        default: return 'help';
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '—';
    return new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

// Compute KPI
const pendingCount = computed(() => props.kpi.pending);
const inProgressCount = computed(() => props.kpi.in_progress);
const completedCount = computed(() => props.kpi.done);
</script>

<template>
    <Head title="Smart Reminders" />

    <div class="space-y-6 animate-in fade-in duration-700">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-headline font-bold text-on-surface tracking-tight">Smart Reminders</h1>
                <p class="text-sm text-outline font-label">Logistics, Inventory & Task Management</p>
            </div>
            <button 
                v-if="$page.props.auth.user.role !== 'viewer'"
                @click="openModal()" 
                class="bg-primary text-on-primary px-6 py-2.5 rounded-xl font-bold flex items-center gap-2 hover:bg-primary/90 transition-all active:scale-95 shadow-lg shadow-primary/20"
            >
                <span class="material-symbols-outlined text-[20px]">add_task</span>
                New Reminder
            </button>
        </div>

        <!-- KPI Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-amber-500/10 border border-amber-500/20 p-5 rounded-2xl flex items-center gap-4">
                <div class="w-12 h-12 bg-amber-500 text-white rounded-xl flex items-center justify-center shadow-lg shadow-amber-500/30">
                    <span class="material-symbols-outlined text-[28px]">schedule</span>
                </div>
                <div>
                    <h3 class="text-2xl font-headline font-black text-amber-600">{{ pendingCount }}</h3>
                    <p class="text-[10px] font-bold text-amber-700/70 uppercase tracking-widest">Pending</p>
                </div>
            </div>
            
            <div class="bg-blue-500/10 border border-blue-500/20 p-5 rounded-2xl flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-500 text-white rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30">
                    <span class="material-symbols-outlined text-[28px]">pending_actions</span>
                </div>
                <div>
                    <h3 class="text-2xl font-headline font-black text-blue-600">{{ inProgressCount }}</h3>
                    <p class="text-[10px] font-bold text-blue-700/70 uppercase tracking-widest">In Progress</p>
                </div>
            </div>

            <div class="bg-emerald-500/10 border border-emerald-500/20 p-5 rounded-2xl flex items-center gap-4">
                <div class="w-12 h-12 bg-emerald-500 text-white rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/30">
                    <span class="material-symbols-outlined text-[28px]">task_alt</span>
                </div>
                <div>
                    <h3 class="text-2xl font-headline font-black text-emerald-600">{{ completedCount }}</h3>
                    <p class="text-[10px] font-bold text-emerald-700/70 uppercase tracking-widest">Completed</p>
                </div>
            </div>
        </div>

        <!-- Task Table -->
        <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl shadow-sm overflow-hidden flex flex-col">
            <div class="p-5 border-b border-outline-variant/20 flex flex-col sm:flex-row justify-between items-center gap-4">
                <h3 class="text-sm font-headline font-bold text-on-surface uppercase tracking-widest flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">list_alt</span>
                    Upcoming Tasks
                </h3>
                
                <div class="flex gap-3 w-full sm:w-auto">
                    <div class="relative flex-1 sm:min-w-[250px]">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                        <input v-model="search" @keyup.enter="handleFilter" type="text" placeholder="Search tasks..."
                            class="w-full pl-10 pr-4 py-2 bg-surface-container-low border border-outline-variant/20 rounded-lg text-xs font-medium text-on-surface outline-none focus:ring-1 focus:ring-primary transition-all" />
                    </div>
                    <select v-model="statusFilter" @change="handleFilter" class="bg-surface-container-low border border-outline-variant/20 rounded-lg px-4 py-2 text-xs font-bold text-on-surface outline-none cursor-pointer">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="done">Completed</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-surface-container-low/30 text-lg font-bold text-outline uppercase tracking-widest border-b border-outline-variant/20">
                            <th class="py-5 px-6">Due Date</th>
                            <th class="py-5 px-6 min-w-[200px]">Objective / Item</th>
                            <th class="py-5 px-6">Quantity</th>
                            <th class="py-5 px-6">Notes</th>
                            <th class="py-5 px-6 text-center">Status</th>
                            <th class="py-5 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <tr v-for="r in reminders.data" :key="r.id" class="group hover:bg-surface-container-low/50 transition-colors">
                            <td class="py-6 px-6">
                                <div class="text-xl font-bold text-on-surface whitespace-nowrap">{{ formatDate(r.date) }}</div>
                            </td>
                            <td class="py-6 px-6">
                                <div class="text-2xl font-bold text-on-surface">{{ r.item }}</div>
                            </td>
                            <td class="py-6 px-6">
                                <div v-if="r.quantity" class="flex items-center gap-1.5">
                                    <span class="text-2xl font-black text-on-surface">{{ r.quantity }}</span>
                                    <span class="text-base font-bold text-outline uppercase tracking-widest">{{ r.unit || 'Units' }}</span>
                                </div>
                                <span v-else class="text-outline text-lg">—</span>
                            </td>
                            <td class="py-6 px-6">
                                <div class="text-lg text-on-surface-variant max-w-[250px] truncate" :title="r.notes">{{ r.notes || '—' }}</div>
                            </td>
                            <td class="py-6 px-6 text-center">
                                <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full text-sm font-black uppercase tracking-widest" :class="getStatusBadge(r.status)">
                                    <span class="material-symbols-outlined text-[18px]">{{ getStatusIcon(r.status) }}</span>
                                    {{ r.status.replace('_', ' ') }}
                                </div>
                            </td>
                            <td class="py-5 px-6 text-right">
                                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity" v-if="$page.props.auth.user.role !== 'viewer'">
                                    <button @click="openModal(r)" class="p-1.5 text-outline hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </button>
                                    <button @click="destroy(r.id)" class="p-1.5 text-outline hover:text-error transition-colors">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="reminders.data.length === 0">
                            <td colspan="6" class="py-20 text-center">
                                <div class="flex flex-col items-center">
                                    <span class="material-symbols-outlined text-5xl text-outline-variant mb-4">search_off</span>
                                    <p class="text-on-surface font-bold text-sm">No tasks found</p>
                                    <p class="text-outline text-xs mt-1">Try adjusting your search filters or add a new task.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-5 border-t border-outline-variant/20">
                <Pagination :links="reminders.links" :meta="reminders" />
            </div>
        </div>

        <!-- Add/Edit Modal (Side Panel) -->
        <SideModal :show="isModalOpen" :title="editingReminder ? 'Edit Reminder' : 'New Reminder'" @close="closeModal">
            <form @submit.prevent="submitForm" class="p-4 space-y-6">
                <!-- Objective -->
                <FormField label="Objective / Item Name" :error="form.errors.item" required>
                    <TextInput v-model="form.item" placeholder="e.g. Iraq Shipment Arrival" />
                </FormField>

                <!-- Due Date -->
                <FormField label="Due Date" :error="form.errors.date" required>
                    <TextInput v-model="form.date" type="date" />
                </FormField>

                <!-- Quantity & Unit -->
                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Quantity" :error="form.errors.quantity">
                        <TextInput v-model="form.quantity" type="number" placeholder="e.g. 50" />
                    </FormField>
                    <FormField label="Unit" :error="form.errors.unit">
                        <TextInput v-model="form.unit" placeholder="e.g. Cartons, Kg" />
                    </FormField>
                </div>

                <!-- Status -->
                <FormField label="Task Status" :error="form.errors.status" required>
                    <SelectInput v-model="form.status" :options="[
                        { label: 'Pending', value: 'pending' },
                        { label: 'In Progress', value: 'in_progress' },
                        { label: 'Completed', value: 'done' }
                    ]" />
                </FormField>

                <!-- Notes -->
                <FormField label="Detailed Notes" :error="form.errors.notes">
                    <textarea 
                        v-model="form.notes" 
                        rows="4"
                        placeholder="Additional details..."
                        class="w-full bg-surface-container-low border border-outline-variant/20 rounded-lg p-3 text-sm text-on-surface outline-none focus:ring-1 focus:ring-primary"
                    ></textarea>
                </FormField>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/20 mt-8">
                    <SecondaryButton @click="closeModal" type="button">Cancel</SecondaryButton>
                    <PrimaryButton :loading="form.processing">{{ editingReminder ? 'Save Changes' : 'Create Task' }}</PrimaryButton>
                </div>
            </form>
        </SideModal>
    </div>
</template>
