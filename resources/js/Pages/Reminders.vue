<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import SideModal from '@/Components/SideModal.vue';
import FormField from '@/Components/FormField.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextArea from '@/Components/TextArea.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    reminders: Array,
});

const isModalOpen = ref(false);
const editingReminder = ref(null);

const form = useForm({
    date: new Date().toISOString().substr(0, 10),
    item: '',
    quantity: '',
    status: 'pending',
    notes: '',
});

const openCreateModal = () => {
    editingReminder.value = null;
    form.reset();
    form.date = new Date().toISOString().substr(0, 10);
    form.status = 'pending';
    isModalOpen.value = true;
};

const openEditModal = (reminder) => {
    editingReminder.value = reminder;
    form.date = reminder.date;
    form.item = reminder.item;
    form.quantity = reminder.quantity || '';
    form.status = reminder.status;
    form.notes = reminder.notes || '';
    isModalOpen.value = true;
};

const submit = () => {
    if (editingReminder.value) {
        form.put(`/reminders/${editingReminder.value.id}`, {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    } else {
        form.post('/reminders', {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    }
};

const deleteReminder = (id) => {
    if (confirm('Are you sure you want to delete this reminder?')) {
        router.delete(`/reminders/${id}`);
    }
};

const toggleStatus = (reminder) => {
    const statuses = ['pending', 'in_progress', 'done'];
    const currentIndex = statuses.indexOf(reminder.status);
    const nextStatus = statuses[(currentIndex + 1) % statuses.length];
    
    router.put(`/reminders/${reminder.id}`, {
        status: nextStatus
    }, {
        preserveScroll: true,
        preserveState: true,
    });
};

const getStatusClass = (status) => {
    switch (status) {
        case 'done':
            return 'bg-[#e6f4ea] text-[#137333] border border-[#ceead6] shadow-[0_2px_8px_rgba(19,115,51,0.05)]';
        case 'in_progress':
            return 'bg-[#fff7ed] text-[#9a3412] border border-[#ffedd5] shadow-[0_2px_8px_rgba(154,52,18,0.05)]';
        default:
            return 'bg-[#eff6ff] text-[#1e40af] border border-[#dbeafe] shadow-[0_2px_8px_rgba(30,64,175,0.05)]';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'done': return 'Done';
        case 'in_progress': return 'Active';
        default: return 'Wait';
    }
};

const statusOptions = [
    { label: 'Pending', value: 'pending' },
    { label: 'In Progress', value: 'in_progress' },
    { label: 'Done', value: 'done' },
];
</script>

<template>
    <Head title="Reminders" />

    <div class="space-y-6 lg:space-y-10 animate-in fade-in duration-700 pb-12">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:justify-between md:items-end gap-6">
            <div class="max-w-2xl text-center md:text-left">
                <h1 class="text-3xl lg:text-5xl font-headline font-bold text-on-surface mb-2 tracking-tight">Reminders</h1>
                <p class="text-on-surface-variant font-body text-sm lg:text-base">Manage your upcoming tasks and inventory restocks across the gallery.</p>
            </div>
            <PrimaryButton @click="openCreateModal" class="px-6 py-4 rounded-2xl shadow-ambient w-full md:w-auto">
                <span class="material-symbols-outlined text-sm mr-2">add</span>
                New Alert
            </PrimaryButton>
        </div>

        <!-- Main Content -->
        <div class="bg-surface-container-low/50 sm:bg-surface-container-low rounded-3xl p-2 sm:p-6 min-h-[400px]">
            <!-- Table Card -->
            <div class="bg-surface-container-lowest rounded-2xl shadow-ambient overflow-hidden border border-outline-variant/10">
                
                <!-- Desktop Table -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left font-body text-sm">
                        <thead class="bg-surface-container-low/30 border-b border-outline-variant/10">
                            <tr class="font-headline uppercase tracking-widest text-[10px] font-bold text-outline-variant">
                                <th class="py-5 px-8 w-32">Date</th>
                                <th class="py-5 px-8">Item / Target</th>
                                <th class="py-5 px-8 w-32">Qty</th>
                                <th class="py-5 px-8">Notes</th>
                                <th class="py-5 px-8 w-40 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            <tr v-for="reminder in reminders" :key="reminder.id" 
                                class="hover:bg-primary/[0.02] transition-colors group cursor-pointer"
                                @click="openEditModal(reminder)"
                            >
                                <td class="py-6 px-8 text-on-surface whitespace-nowrap font-medium">{{ reminder.date }}</td>
                                <td class="py-6 px-8 font-headline font-extrabold text-on-surface group-hover:text-primary transition-colors">{{ reminder.item }}</td>
                                <td class="py-6 px-8 text-on-surface-variant">{{ reminder.quantity || '-' }}</td>
                                <td class="py-6 px-8 text-on-surface-variant max-w-xs truncate italic opacity-80">{{ reminder.notes || '-' }}</td>
                                <td class="py-6 px-8 text-right relative">
                                    <div class="flex items-center justify-end gap-3">
                                        <span 
                                            @click.stop="toggleStatus(reminder)"
                                            :class="['inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest cursor-pointer hover:opacity-80 transition-all select-none', getStatusClass(reminder.status)]"
                                        >
                                            {{ getStatusLabel(reminder.status) }}
                                        </span>
                                        <button 
                                            @click.stop="deleteReminder(reminder.id)"
                                            class="opacity-0 group-hover:opacity-100 p-2 text-error hover:bg-error/10 rounded-xl transition-all"
                                        >
                                            <span class="material-symbols-outlined text-[20px]">delete_sweep</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Feed -->
                <div class="md:hidden divide-y divide-outline-variant/10">
                    <div 
                        v-for="reminder in reminders" 
                        :key="reminder.id" 
                        @click="openEditModal(reminder)"
                        class="p-5 space-y-4 hover:bg-surface-container-low transition-colors"
                    >
                        <div class="flex justify-between items-start">
                            <div class="space-y-1 min-w-0 pr-4">
                                <h3 class="font-headline font-extrabold text-on-surface text-base truncate">{{ reminder.item }}</h3>
                                <p class="text-[10px] font-black text-outline uppercase tracking-[0.1em]">{{ reminder.date }} <span v-if="reminder.quantity" class="ml-2 pr-2 border-l border-outline-variant/30 pl-2">Qty: {{ reminder.quantity }}</span></p>
                            </div>
                            <span 
                                @click.stop="toggleStatus(reminder)"
                                :class="['inline-flex items-center px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest shadow-sm', getStatusClass(reminder.status)]"
                            >
                                {{ getStatusLabel(reminder.status) }}
                            </span>
                        </div>
                        
                        <p v-if="reminder.notes" class="text-xs text-on-surface-variant italic line-clamp-2">{{ reminder.notes }}</p>
                        
                        <div class="flex justify-end pt-2 border-t border-outline-variant/10">
                            <button @click.stop="deleteReminder(reminder.id)" class="text-xs font-black text-error flex items-center gap-1 uppercase tracking-widest">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                Delete
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="reminders.length === 0" class="py-24 text-center">
                    <div class="w-16 h-16 bg-surface-container-high rounded-full flex items-center justify-center mx-auto mb-6 opacity-30">
                        <span class="material-symbols-outlined text-4xl">notifications_off</span>
                    </div>
                    <h3 class="text-lg font-headline font-black text-on-surface mb-2">Clear History</h3>
                    <p class="text-sm text-on-surface-variant max-w-xs mx-auto font-medium">No pending reminders in the current queue.</p>
                </div>
            </div>
        </div>

        <!-- SIDE MODAL (Standardized) -->
        <SideModal 
            :show="isModalOpen" 
            :title="editingReminder ? 'Update Link' : 'New Reminder Node'" 
            @close="isModalOpen = false"
        >
            <div class="space-y-6">
                <FormField label="Item / Objective" required>
                    <TextInput v-model="form.item" placeholder="e.g. Shipment #4029" autofocus />
                </FormField>

                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Target Date" required>
                        <TextInput v-model="form.date" type="date" />
                    </FormField>
                    <FormField label="Quantity">
                        <TextInput v-model="form.quantity" placeholder="Units..." />
                    </FormField>
                </div>

                <FormField label="Status" required>
                    <SelectInput v-model="form.status" :options="statusOptions" />
                </FormField>

                <FormField label="Observations / Notes">
                    <TextArea v-model="form.notes" placeholder="Optional intelligence notes..." rows="4" />
                </FormField>
            </div>

            <template #footer>
                <SecondaryButton @click="isModalOpen = false">Cancel</SecondaryButton>
                <PrimaryButton @click="submit" :loading="form.processing">
                    {{ editingReminder ? 'Update Node' : 'Initialize Alert' }}
                </PrimaryButton>
            </template>
        </SideModal>
    </div>
</template>

<style scoped>
.shadow-ambient {
    box-shadow: 0 12px 32px 0 rgba(25, 28, 30, 0.06);
}
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
