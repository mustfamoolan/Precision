<script setup>
import { ref } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import Badge from '@/Components/Badge.vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    logs: Object,
    filters: Object,
    events: Array
});

const search = ref(props.filters.search || '');
const eventFilter = ref(props.filters.event || '');

const handleFilter = () => {
    router.get('/activity-log', { 
        search: search.value, 
        event: eventFilter.value 
    }, { preserveState: true, preserveScroll: true });
};

const getEventColor = (event) => {
    switch (event) {
        case 'created':
        case 'invoice_created':
        case 'purchase_created':
            return 'success';
        case 'updated':
            return 'indigo';
        case 'deleted':
            return 'danger';
        default:
            return 'slate';
    }
};

const getEventLabel = (event) => {
    return event.replace('_', ' ').toUpperCase();
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString('en-AE', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

</script>

<template>
    <Head title="Activity Log" />

    <div class="min-h-screen bg-[#f8fafc] pb-20 px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="py-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tight">System Activity Log</h1>
                <p class="mt-1 text-slate-500 font-medium">Monitor all changes and actions across the system</p>
            </div>
        </div>

        <!-- Table Container -->
        <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-slate-100 overflow-hidden flex flex-col">
            <!-- Toolbar -->
            <div class="p-8 border-b border-slate-100 flex flex-wrap xl:flex-nowrap justify-between items-center gap-4">
                
                <div class="flex items-center gap-4 w-full xl:w-auto">
                    <div>
                        <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight">Audit Trail</h3>
                        <p class="text-sm text-slate-400 font-medium mt-0.5">{{ logs.total }} events recorded</p>
                    </div>
                </div>
                
                <div class="flex flex-wrap sm:flex-nowrap items-center gap-4 w-full xl:w-auto xl:flex-1 justify-end max-w-2xl">
                    <!-- Event Dropdown Filter -->
                    <div class="w-full sm:w-48 relative">
                        <select 
                            v-model="eventFilter"
                            @change="handleFilter"
                            class="w-full px-4 py-3 bg-slate-50 rounded-2xl border border-slate-200 focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-400 text-xs font-black uppercase tracking-wider appearance-none cursor-pointer transition-all"
                        >
                            <option value="">All Statuses</option>
                            <option value="created">Created</option>
                            <option value="updated">Updated</option>
                            <option value="deleted">Deleted</option>
                        </select>
                        <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-[18px]">keyboard_arrow_down</span>
                    </div>

                    <!-- Search Input -->
                    <div class="w-full sm:flex-1 max-w-md relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                        <input 
                            v-model="search"
                            @keyup.enter="handleFilter"
                            type="text" 
                            placeholder="Search logs by description or user..." 
                            class="w-full pl-12 pr-4 py-3 bg-slate-50 rounded-2xl border border-slate-200 focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-400 text-sm font-medium transition-all"
                        />
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 text-xs font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                            <th class="py-6 px-8">User</th>
                            <th class="py-6 px-8">Event</th>
                            <th class="py-6 px-8">Description</th>
                            <th class="py-6 px-8">IP Address</th>
                            <th class="py-6 px-8 text-right">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="log in logs.data" :key="log.id" class="group hover:bg-slate-50/50 transition-colors">
                            <td class="py-6 px-8">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 font-black text-sm border border-indigo-100">
                                        {{ log.user ? log.user.name.charAt(0).toUpperCase() : 'S' }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-slate-900">{{ log.user ? log.user.name : 'System' }}</p>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ log.user ? log.user.role : 'Core' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-6 px-8">
                                <Badge :type="getEventColor(log.event)">
                                    {{ getEventLabel(log.event) }}
                                </Badge>
                            </td>
                            <td class="py-6 px-8">
                                <p class="text-sm font-bold text-slate-700">{{ log.description }}</p>
                                <p v-if="log.subject_type" class="text-[10px] font-medium text-slate-400 mt-1 uppercase tracking-wider">
                                    Entity: {{ log.subject_type.split('\\').pop() }} #{{ log.subject_id }}
                                </p>
                            </td>
                            <td class="py-6 px-8">
                                <span class="text-xs font-mono text-slate-400">{{ log.ip_address || '127.0.0.1' }}</span>
                            </td>
                            <td class="py-6 px-8 text-right">
                                <p class="text-xs font-black text-slate-900">{{ formatDate(log.created_at) }}</p>
                            </td>
                        </tr>
                        <tr v-if="logs.data.length === 0">
                            <td colspan="5" class="py-20 text-center text-slate-400 italic text-sm">
                                <span class="material-symbols-outlined text-4xl block mb-2 opacity-50">history</span>
                                No activity recorded yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-6 border-t border-slate-100">
                <Pagination :links="logs.links" :meta="logs" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.font-black { font-weight: 900; }
.tracking-tighter { letter-spacing: -0.05em; }
</style>
