<script setup>
import { ref } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SideModal from '@/Components/SideModal.vue';
import FormField from '@/Components/FormField.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    users: Array,
});

const showModal = ref(false);
const editingUser = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'editor',
});

const openModal = (user = null) => {
    if (user) {
        editingUser.value = user;
        form.name = user.name;
        form.email = user.email;
        form.role = user.role;
        form.password = '';
    } else {
        editingUser.value = null;
        form.reset();
    }
    showModal.value = true;
};

const submit = () => {
    if (editingUser.value) {
        form.put(`/users/${editingUser.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post('/users', {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const destroy = (id) => {
    if (confirm('Are you sure you want to delete this user?')) {
        router.delete(`/users/${id}`);
    }
};

const roles = [
    { label: 'Admin (Full Access)', value: 'admin' },
    { label: 'Editor (Management Access)', value: 'editor' },
    { label: 'Viewer (Read Only)', value: 'viewer' },
];

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-AE', { year: 'numeric', month: 'short', day: 'numeric' });
};
</script>

<template>
    <Head title="User Management" />

    <div class="space-y-6 animate-in fade-in duration-500">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-headline font-bold text-on-surface tracking-tight">User Management</h1>
                <p class="text-sm text-outline font-label">Control system access and permissions</p>
            </div>
            <PrimaryButton @click="openModal()" class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">person_add</span>
                Create New User
            </PrimaryButton>
        </div>

        <!-- Users Table Card -->
        <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl shadow-sm overflow-hidden flex flex-col">
            <div class="p-5 border-b border-outline-variant/20 flex items-center gap-3">
                <div class="w-10 h-10 bg-primary/10 text-primary rounded-xl flex items-center justify-center">
                    <span class="material-symbols-outlined">manage_accounts</span>
                </div>
                <div>
                    <h3 class="text-sm font-headline font-bold text-on-surface uppercase tracking-widest">Authorized Personnel</h3>
                    <p class="text-[10px] font-bold text-outline uppercase tracking-widest">{{ users.length }} Active Users</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low/50 text-xl font-bold text-outline uppercase tracking-wider border-b border-outline-variant/10">
                            <th class="px-6 py-6">User Details</th>
                            <th class="px-6 py-6">Role</th>
                            <th class="px-6 py-6">Joined Date</th>
                            <th class="px-6 py-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <tr v-for="user in users" :key="user.id" class="hover:bg-surface-container-low/30 transition-colors group">
                            <td class="px-6 py-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-surface-container-high border border-outline-variant/20 flex items-center justify-center overflow-hidden">
                                        <img :src="'https://ui-avatars.com/api/?name='+user.name+'&background=004ced&color=fff'" alt="Avatar" class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-lg font-bold text-on-surface">{{ user.name }}</span>
                                        <span class="text-xs text-outline font-medium">{{ user.email }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-6">
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest"
                                    :class="{
                                        'bg-primary/10 text-primary': user.role === 'admin',
                                        'bg-secondary/10 text-secondary': user.role === 'editor',
                                        'bg-outline/10 text-outline': user.role === 'viewer'
                                    }"
                                >
                                    {{ user.role }}
                                </span>
                            </td>
                            <td class="px-6 py-6 text-sm text-outline font-medium">{{ formatDate(user.created_at) }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click="openModal(user)" class="p-1.5 text-outline hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                    <button @click="destroy(user.id)" v-if="$page.props.auth?.user?.id !== user.id" class="p-1.5 text-outline hover:text-error transition-colors">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <SideModal :show="showModal" :title="editingUser ? 'Edit System Access' : 'Provision New Access'" @close="showModal = false">
            <form @submit.prevent="submit" class="space-y-6 p-2">
                <div class="p-4 bg-surface-container-low border border-outline-variant/20 rounded-2xl mb-4">
                    <p class="text-[10px] font-bold text-outline uppercase tracking-widest mb-1">Access Guidelines</p>
                    <p class="text-xs text-on-surface-variant leading-relaxed">System users can access sensitive financial data. Ensure roles are assigned following the principle of least privilege.</p>
                </div>

                <FormField label="Full Name" :error="form.errors.name" required>
                    <TextInput v-model="form.name" placeholder="John Doe" />
                </FormField>

                <FormField label="Email Address" :error="form.errors.email" required>
                    <TextInput v-model="form.email" type="email" placeholder="john@example.com" />
                </FormField>

                <FormField label="Access Role" :error="form.errors.role" required>
                    <SelectInput v-model="form.role" :options="roles" />
                </FormField>

                <FormField :label="editingUser ? 'New Password (Leave blank to keep current)' : 'Password'" :error="form.errors.password" :required="!editingUser">
                    <TextInput v-model="form.password" type="password" placeholder="••••••••" />
                </FormField>

                <div class="pt-6 flex justify-end gap-3 border-t border-outline-variant/10 mt-6">
                    <SecondaryButton @click="showModal = false" type="button">Cancel</SecondaryButton>
                    <PrimaryButton :loading="form.processing" :disabled="form.processing">
                        {{ editingUser ? 'Update Permissions' : 'Grant Access' }}
                    </PrimaryButton>
                </div>
            </form>
        </SideModal>
    </div>
</template>
