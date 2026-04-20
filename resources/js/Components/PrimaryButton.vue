<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        class="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-gradient-to-br from-primary to-primary-container text-on-primary font-label font-bold text-sm shadow-[0_4px_14px_0_rgba(0,82,255,0.39)] hover:shadow-[0_6px_20px_rgba(0,82,255,0.23)] hover:scale-[0.98] active:scale-95 transition-all disabled:opacity-50 disabled:pointer-events-none"
    >
        <span v-if="loading" class="mr-2 animate-spin h-4 w-4 border-2 border-white/30 border-t-white rounded-full"></span>
        <slot />
    </button>
</template>
