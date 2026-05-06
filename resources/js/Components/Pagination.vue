<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    links: {
        type: Array,
        required: true,
    },
    meta: {
        type: Object,
        default: () => ({}),
    }
});

const cleanLinks = computed(() => {
    return props.links.map(link => {
        // Clean up label if it contains special characters or just ensure it's readable
        let label = link.label;
        if (label.includes('Previous')) label = 'chevron_left';
        if (label.includes('Next')) label = 'chevron_right';
        
        return {
            ...link,
            isIcon: label === 'chevron_left' || label === 'chevron_right',
            label: label
        };
    });
});

const isMobile = typeof window !== 'undefined' ? window.innerWidth < 640 : false;
</script>

<template>
    <div v-if="links.length > 3" class="flex items-center justify-between px-4 py-3 sm:px-6">
        <div class="flex flex-1 justify-between sm:hidden">
            <Link
                v-if="links[0].url"
                :href="links[0].url"
                class="relative inline-flex items-center rounded-xl bg-white px-4 py-2 text-sm font-black text-slate-700 border border-slate-200 hover:bg-slate-50 transition-all active:scale-95"
                preserve-scroll
            >
                Previous
            </Link>
            <div v-else class="relative inline-flex items-center rounded-xl bg-slate-50 px-4 py-2 text-sm font-black text-slate-300 border border-slate-100 cursor-not-allowed">
                Previous
            </div>
            
            <Link
                v-if="links[links.length - 1].url"
                :href="links[links.length - 1].url"
                class="relative ml-3 inline-flex items-center rounded-xl bg-white px-4 py-2 text-sm font-black text-slate-700 border border-slate-200 hover:bg-slate-50 transition-all active:scale-95"
                preserve-scroll
            >
                Next
            </Link>
            <div v-else class="relative ml-3 inline-flex items-center rounded-xl bg-slate-50 px-4 py-2 text-sm font-black text-slate-300 border border-slate-100 cursor-not-allowed">
                Next
            </div>
        </div>
        
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">
                    Showing
                    <span class="font-black text-slate-900">{{ meta.from || 1 }}</span>
                    to
                    <span class="font-black text-slate-900">{{ meta.to || meta.total }}</span>
                    of
                    <span class="font-black text-slate-900">{{ meta.total }}</span>
                    results
                </p>
            </div>
            
            <div>
                <nav class="isolate inline-flex -space-x-px rounded-2xl shadow-sm bg-slate-100/50 p-1 gap-1" aria-label="Pagination">
                    <template v-for="(link, key) in cleanLinks" :key="key">
                        <div v-if="link.url === null" 
                            class="relative inline-flex items-center px-3 py-2 text-xs font-black text-slate-300 transition-all rounded-xl"
                            :class="link.isIcon ? 'w-10 h-10 justify-center' : ''"
                        >
                            <span v-if="link.isIcon" class="material-symbols-outlined text-[20px]">{{ link.label }}</span>
                            <span v-else v-html="link.label"></span>
                        </div>
                        
                        <Link
                            v-else
                            :href="link.url"
                            class="relative inline-flex items-center text-xs font-black transition-all rounded-xl active:scale-95"
                            :class="[
                                link.active 
                                    ? 'z-10 bg-indigo-600 text-white shadow-lg shadow-indigo-100 w-10 h-10 justify-center' 
                                    : 'text-slate-500 hover:bg-white hover:text-slate-900 w-10 h-10 justify-center',
                                link.isIcon ? 'w-10 h-10 justify-center' : ''
                            ]"
                            preserve-scroll
                        >
                            <span v-if="link.isIcon" class="material-symbols-outlined text-[20px]">{{ link.label }}</span>
                            <span v-else v-html="link.label"></span>
                        </Link>
                    </template>
                </nav>
            </div>
        </div>
    </div>
</template>

<style scoped>
.font-black { font-weight: 900; }
</style>
