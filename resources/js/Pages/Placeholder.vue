<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({ layout: MainLayout });

const props = defineProps({
    title: String,
    icon: String,
    description: String,
});
</script>

<template>
    <Head :title="title" />
    
    <div class="w-full h-full min-h-[500px] border-2 border-dashed border-outline-variant/30 rounded-xl bg-surface-container-lowest flex flex-col items-center justify-center p-8 text-center transition-colors">
        <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mb-4">
            <span class="material-symbols-outlined text-primary text-3xl">{{ icon }}</span>
        </div>
        <h3 class="text-xl font-bold font-headline text-on-surface mb-2">{{ title }} Module</h3>
        <p class="text-outline font-body max-w-md">{{ description }}</p>
    </div>
</template>
