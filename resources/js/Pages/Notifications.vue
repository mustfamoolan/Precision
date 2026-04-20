<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    notifications: Object, // Laravel Paginated object
});

const markAsRead = (id) => {
    router.post(`/notifications/${id}/read`, {}, {
        preserveScroll: true,
    });
};

const markAllAsRead = () => {
    router.post('/notifications/read-all', {}, {
        preserveScroll: true,
    });
};

const formatTime = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    
    if (date.toDateString() === now.toDateString()) {
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
};

// Simple grouping logic for UI (Today, Earlier)
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
    <Head title="Inbox - Notifications" />

    <div class="space-y-12 animate-in fade-in duration-700">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-6 mb-12">
            <div>
                <h1 class="font-headline text-[2.5rem] font-bold text-on-surface leading-tight tracking-tight">Inbox</h1>
                <p class="font-body text-on-surface-variant mt-2 text-lg">Manage your alerts and system updates.</p>
            </div>
            
            <button 
                v-if="notifications.data.some(n => !n.read_at)"
                @click="markAllAsRead" 
                class="flex items-center gap-2 bg-surface-container-highest text-on-surface hover:bg-surface-variant px-6 py-3 rounded-xl font-bold text-xs uppercase tracking-widest shadow-sm transition-all active:scale-95"
            >
                <span class="material-symbols-outlined text-[20px]">done_all</span>
                Mark All Read
            </button>
        </div>

        <!-- Notifications Container -->
        <div class="bg-surface-container-lowest rounded-2xl shadow-[0_12px_32px_0_rgba(25,28,30,0.06)] border border-outline-variant/10 overflow-hidden">
            
            <!-- Today Group -->
            <div v-if="groups.today.length > 0">
                <div class="px-8 py-6 pb-2">
                    <h3 class="font-headline text-lg font-bold text-on-surface-variant mb-4 flex items-center gap-2">
                        <span class="w-1.5 h-6 bg-primary rounded-full"></span>
                        Today
                    </h3>
                </div>
                <div class="flex flex-col">
                    <div 
                        v-for="n in groups.today" 
                        :key="n.id" 
                        class="px-8 py-6 flex items-start gap-6 group transition-colors duration-200 border-b border-outline-variant/10"
                        :class="[n.read_at ? 'bg-surface-container-lowest opacity-60' : 'bg-surface/40 hover:bg-surface']"
                    >
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 shadow-sm"
                            :class="[
                                n.data.type === 'cheque' ? 'bg-error/10 text-error' : 
                                n.data.type === 'reminder' ? 'bg-secondary/10 text-secondary' : 'bg-primary/10 text-primary'
                            ]"
                        >
                            <span class="material-symbols-outlined text-[28px]">{{ n.data.icon || 'notifications' }}</span>
                        </div>
                        <div class="flex-1 pt-1">
                            <div class="flex justify-between items-start mb-1 gap-4">
                                <h4 class="font-headline font-bold text-on-surface text-lg leading-tight">{{ n.data.title }}</h4>
                                <span class="font-label text-xs text-on-surface-variant font-bold uppercase tracking-widest shrink-0">{{ formatTime(n.created_at) }}</span>
                            </div>
                            <p class="font-body text-sm text-on-surface-variant mb-4 max-w-3xl leading-relaxed">{{ n.data.message }}</p>
                            
                            <div class="flex items-center gap-4">
                                <button 
                                    v-if="!n.read_at"
                                    @click="markAsRead(n.id)" 
                                    class="font-label text-xs font-black text-primary hover:underline uppercase tracking-widest flex items-center gap-1.5"
                                >
                                    <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                    Mark as read
                                </button>
                                <Link 
                                    v-if="n.data.link" 
                                    :href="n.data.link" 
                                    class="font-label text-xs font-black text-on-surface-variant hover:text-primary transition-colors uppercase tracking-widest flex items-center gap-1.5"
                                >
                                    <span class="material-symbols-outlined text-[18px]">open_in_new</span>
                                    View Details
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Earlier Group -->
            <div v-if="groups.earlier.length > 0">
                <div class="px-8 py-6 pb-2 mt-4">
                    <h3 class="font-headline text-lg font-bold text-on-surface-variant mb-4 flex items-center gap-2">
                        <span class="w-1.5 h-6 bg-outline-variant rounded-full"></span>
                        Earlier
                    </h3>
                </div>
                <div class="flex flex-col">
                    <div 
                        v-for="n in groups.earlier" 
                        :key="n.id" 
                        class="px-8 py-6 flex items-start gap-6 group transition-colors duration-200 border-b border-outline-variant/10"
                        :class="[n.read_at ? 'bg-surface-container-lowest opacity-60' : 'bg-surface/40 hover:bg-surface']"
                    >
                        <!-- Same as above but with lower profile -->
                        <div class="w-12 h-12 rounded-xl bg-surface-container-high flex items-center justify-center shrink-0 text-outline">
                            <span class="material-symbols-outlined text-[28px]">{{ n.data.icon || 'history' }}</span>
                        </div>
                        <div class="flex-1 pt-1">
                            <div class="flex justify-between items-start mb-1 gap-4">
                                <h4 class="font-headline font-bold text-on-surface text-lg leading-tight">{{ n.data.title }}</h4>
                                <span class="font-label text-xs text-on-surface-variant font-bold uppercase tracking-widest shrink-0">{{ formatTime(n.created_at) }}</span>
                            </div>
                            <p class="font-body text-sm text-on-surface-variant mb-4 max-w-3xl leading-relaxed">{{ n.data.message }}</p>
                            
                            <button 
                                v-if="!n.read_at"
                                @click="markAsRead(n.id)" 
                                class="font-label text-xs font-black text-primary hover:underline uppercase tracking-widest flex items-center gap-1.5"
                            >
                                <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                Mark as read
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="notifications.data.length === 0" class="py-24 text-center">
                <div class="w-20 h-20 bg-surface-container-high rounded-full flex items-center justify-center mx-auto mb-6">
                    <span class="material-symbols-outlined text-4xl text-outline-variant">inbox</span>
                </div>
                <h3 class="text-xl font-headline font-bold text-on-surface mb-2">No notifications found</h3>
                <p class="text-on-surface-variant max-w-xs mx-auto">Your inbox is empty. We'll alert you when something important happens.</p>
            </div>
        </div>

        <!-- Pagination (Simple) -->
        <div class="flex justify-center gap-2" v-if="notifications.last_page > 1">
            <Link 
                v-for="link in notifications.links" 
                :key="link.label"
                :href="link.url || '#'"
                v-html="link.label"
                class="px-4 py-2 rounded-lg text-xs font-bold transition-all"
                :class="[
                    link.active ? 'bg-primary text-on-primary shadow-md' : 'bg-surface-container-lowest text-on-surface hover:bg-surface-variant',
                    !link.url ? 'opacity-30 cursor-not-allowed' : ''
                ]"
            ></Link>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
