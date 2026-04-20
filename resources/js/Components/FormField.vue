<script setup>
defineProps({
    label: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="space-y-2">
        <label v-if="label" class="block font-label text-sm font-medium text-on-surface">
            {{ label }}
            <span v-if="required" class="text-error ml-0.5">*</span>
        </label>
        
        <div class="relative">
            <slot />
        </div>

        <p v-if="error" class="text-xs font-medium text-error mt-1 transition-all animate-in fade-in slide-in-from-top-1">
            {{ error }}
        </p>
    </div>
</template>
