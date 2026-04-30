<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    notifications: Object,
    filters: Object
});

const currentTab = ref(props.filters?.tab || 'all');

const tabs = [
    { id: 'all', name: 'All' },
    { id: 'unread', name: 'Unread' },
    { id: 'reminder', name: 'System' },
    { id: 'financial', name: 'Financial' },
    { id: 'shipping', name: 'Shipping' }
];

const changeTab = (tabId) => {
    currentTab.value = tabId;
    router.get('/notifications', { tab: tabId }, { preserveState: true });
};

const markAsRead = (id) => {
    router.post(`/notifications/${id}/read`, {}, { preserveScroll: true });
};

const markAllAsRead = () => {
    router.post('/notifications/read-all', {}, { preserveScroll: true });
};

const clearAll = () => {
    if(confirm(`Are you sure you want to delete all ${currentTab.value} notifications?`)) {
        router.post('/notifications/clear-all', { tab: currentTab.value }, { preserveScroll: true });
    }
};

const destroy = (id) => {
    router.delete(`/notifications/${id}`, { preserveScroll: true });
};

const formatTime = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    
    if (date.toDateString() === now.toDateString()) {
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
};

const groups = computed(() => {
    const today = new Date().toDateString();
    const result = { today: [], earlier: [] };
    
    props.notifications.data.forEach(n => {
        if (new Date(n.created_at).toDateString() === today) {
            result.today.push(n);
        } else {
            result.earlier.push(n);
        }
    });
    
    return result;
});
</script>

<template>
    <Head title="Notification Center" />

    <div class="space-y-8 animate-in fade-in duration-700">
        <!-- Modern Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div>
                <h1 class="font-headline text-3xl font-black text-on-surface tracking-tight">Notification Center</h1>
                <p class="font-label text-sm text-outline mt-1 font-bold">Stay updated with systemic alerts and financial milestones</p>
            </div>
            
            <div class="flex items-center gap-3">
                <button 
                    v-if="notifications.data.some(n => !n.read_at)"
                    @click="markAllAsRead" 
                    class="bg-surface-container-low text-primary border border-outline-variant/30 hover:bg-surface-container-high hover:border-primary/30 px-5 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all active:scale-95 shadow-sm flex items-center gap-2"
                >
                    <span class="material-symbols-outlined text-[18px]">done_all</span>
                    Mark All Read
                </button>
                <button 
                    v-if="notifications.data.length > 0"
                    @click="clearAll" 
                    class="bg-error/10 text-error hover:bg-error/20 px-5 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all active:scale-95 shadow-sm flex items-center gap-2"
                >
                    <span class="material-symbols-outlined text-[18px]">delete_sweep</span>
                    Clear {{ currentTab === 'all' ? 'All' : currentTab }}
                </button>
            </div>
        </div>

        <!-- Navigation Tabs (Neumorphic inspired) -->
        <div class="p-1.5 bg-surface-container-lowest border border-outline-variant/20 rounded-2xl shadow-[inset_0_2px_4px_rgba(0,0,0,0.02)] inline-flex flex-wrap gap-1">
            <button 
                v-for="tab in tabs" 
                :key="tab.id"
                @click="changeTab(tab.id)"
                class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all duration-300"
                :class="currentTab === tab.id 
                    ? 'bg-primary text-on-primary shadow-lg shadow-primary/20 scale-100' 
                    : 'text-outline hover:text-on-surface hover:bg-surface-container-low scale-95'"
            >
                {{ tab.name }}
            </button>
        </div>

        <!-- Notifications Container -->
        <div class="bg-surface-container-lowest rounded-[2rem] border border-outline-variant/20 shadow-xl shadow-surface-variant/10 overflow-hidden relative">
            
            <div v-if="notifications.data.length === 0" class="py-32 text-center flex flex-col items-center">
                <div class="w-24 h-24 bg-surface-container-low rounded-full flex items-center justify-center mb-6 border border-outline-variant/10 shadow-[inset_0_2px_10px_rgba(0,0,0,0.02)]">
                    <span class="material-symbols-outlined text-5xl text-outline-variant">notifications_paused</span>
                </div>
                <h3 class="font-headline text-xl font-black text-on-surface mb-2">You're all caught up!</h3>
                <p class="font-body text-outline font-bold text-sm">There are no {{ currentTab !== 'all' ? currentTab : '' }} notifications at the moment.</p>
            </div>

            <div v-else class="divide-y divide-outline-variant/10">
                <!-- Today Group -->
                <div v-if="groups.today.length > 0">
                    <div class="px-8 py-4 bg-surface-container-low/50 border-b border-outline-variant/10">
                        <span class="text-[10px] font-black text-primary uppercase tracking-widest flex items-center gap-2">
                            <span class="w-1.5 h-1.5 bg-primary rounded-full animate-pulse"></span>
                            Today
                        </span>
                    </div>
                    <div class="flex flex-col">
                        <div 
                            v-for="n in groups.today" 
                            :key="n.id" 
                            class="relative px-8 py-6 flex items-start gap-6 group transition-all duration-300"
                            :class="[
                                n.read_at ? 'bg-surface-container-lowest opacity-70' : 'bg-primary/5 hover:bg-primary/10 shadow-[inset_4px_0_0_var(--color-primary)]'
                            ]"
                        >
                            <!-- Unread Indicator Dot -->
                            <div v-if="!n.read_at" class="absolute left-3 top-1/2 -translate-y-1/2 w-2 h-2 rounded-full bg-primary shadow-[0_0_8px_var(--color-primary)]"></div>

                            <!-- Icon -->
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 border border-outline-variant/20 transition-transform group-hover:scale-105"
                                :class="[
                                    n.data.type === 'financial' || n.data.icon === 'payments' ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' : 
                                    n.data.type === 'shipping' || n.data.icon === 'local_shipping' ? 'bg-indigo-500/10 text-indigo-600 border-indigo-500/20' : 
                                    n.data.type === 'reminder' ? 'bg-amber-500/10 text-amber-600 border-amber-500/20' : 'bg-surface-container-high text-on-surface'
                                ]"
                            >
                                <span class="material-symbols-outlined text-[26px]">{{ n.data.icon || 'notifications' }}</span>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0 pr-10">
                                <div class="flex justify-between items-start mb-2 gap-4">
                                    <h4 class="font-headline font-black text-on-surface text-2xl leading-tight truncate group-hover:text-primary transition-colors">{{ n.data.title }}</h4>
                                    <span class="font-label text-xs text-outline font-black uppercase tracking-widest shrink-0">{{ formatTime(n.created_at) }}</span>
                                </div>
                                <p class="font-body text-lg text-on-surface-variant mb-6 leading-relaxed line-clamp-2 pr-12">{{ n.data.message }}</p>
                                
                                <div class="flex items-center gap-6">
                                    <button 
                                        v-if="!n.read_at"
                                        @click="markAsRead(n.id)" 
                                        class="px-5 py-2.5 rounded-lg bg-surface-container-high text-on-surface text-xs font-black uppercase tracking-widest hover:bg-primary hover:text-on-primary transition-all shadow-sm"
                                    >
                                        Mark as read
                                    </button>
                                    <Link 
                                        v-if="n.data.link" 
                                        :href="n.data.link" 
                                        class="px-5 py-2.5 rounded-lg border border-outline-variant/30 text-outline text-xs font-black uppercase tracking-widest hover:border-primary hover:text-primary transition-all flex items-center gap-1"
                                    >
                                        Action Required <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                                    </Link>
                                </div>
                            </div>

                            <!-- Hover Delete (Swipe Alternative) -->
                            <div class="absolute right-6 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-all translate-x-4 group-hover:translate-x-0">
                                <button @click="destroy(n.id)" class="w-10 h-10 rounded-xl bg-error/10 text-error flex items-center justify-center hover:bg-error hover:text-on-error shadow-lg transition-colors">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Earlier Group -->
                <div v-if="groups.earlier.length > 0">
                    <div class="px-8 py-4 bg-surface-container-low/50 border-y border-outline-variant/10">
                        <span class="text-[10px] font-black text-outline uppercase tracking-widest">
                            Earlier
                        </span>
                    </div>
                    <div class="flex flex-col">
                        <div 
                            v-for="n in groups.earlier" 
                            :key="n.id" 
                            class="relative px-8 py-6 flex items-start gap-6 group transition-all duration-300"
                            :class="[
                                n.read_at ? 'bg-surface-container-lowest opacity-60 hover:opacity-100' : 'bg-primary/5 hover:bg-primary/10 shadow-[inset_4px_0_0_var(--color-primary)]'
                            ]"
                        >
                            <!-- Unread Indicator Dot -->
                            <div v-if="!n.read_at" class="absolute left-3 top-1/2 -translate-y-1/2 w-2 h-2 rounded-full bg-primary shadow-[0_0_8px_var(--color-primary)]"></div>

                            <!-- Icon -->
                            <div class="w-14 h-14 rounded-2xl bg-surface-container-low border border-outline-variant/20 flex items-center justify-center shrink-0 text-outline transition-transform group-hover:scale-105 group-hover:bg-surface-container-high">
                                <span class="material-symbols-outlined text-[26px]">{{ n.data.icon || 'history' }}</span>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0 pr-10">
                                <div class="flex justify-between items-start mb-2 gap-4">
                                    <h4 class="font-headline font-bold text-on-surface text-2xl leading-tight truncate">{{ n.data.title }}</h4>
                                    <span class="font-label text-xs text-outline font-black uppercase tracking-widest shrink-0">{{ formatTime(n.created_at) }}</span>
                                </div>
                                <p class="font-body text-lg text-on-surface-variant mb-6 leading-relaxed line-clamp-2 pr-12">{{ n.data.message }}</p>
                                
                                <div class="flex items-center gap-6">
                                    <button 
                                        v-if="!n.read_at"
                                        @click="markAsRead(n.id)" 
                                        class="px-5 py-2.5 rounded-lg bg-surface-container-high text-on-surface text-xs font-black uppercase tracking-widest hover:bg-primary hover:text-on-primary transition-all shadow-sm"
                                    >
                                        Mark as read
                                    </button>
                                    <Link 
                                        v-if="n.data.link" 
                                        :href="n.data.link" 
                                        class="px-5 py-2.5 rounded-lg border border-outline-variant/30 text-outline text-xs font-black uppercase tracking-widest hover:border-primary hover:text-primary transition-all flex items-center gap-1"
                                    >
                                        Review <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                                    </Link>
                                </div>
                            </div>

                            <!-- Hover Delete -->
                            <div class="absolute right-6 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-all translate-x-4 group-hover:translate-x-0">
                                <button @click="destroy(n.id)" class="w-10 h-10 rounded-xl bg-error/10 text-error flex items-center justify-center hover:bg-error hover:text-on-error shadow-lg transition-colors">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center gap-2 pb-10" v-if="notifications.last_page > 1">
            <Link 
                v-for="link in notifications.links" 
                :key="link.label"
                :href="link.url || '#'"
                v-html="link.label"
                class="px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all"
                :class="[
                    link.active ? 'bg-primary text-on-primary shadow-lg shadow-primary/20' : 'bg-surface-container-lowest border border-outline-variant/20 text-outline hover:text-on-surface hover:bg-surface-container-low',
                    !link.url ? 'opacity-30 cursor-not-allowed' : ''
                ]"
            ></Link>
        </div>
    </div>
</template>
