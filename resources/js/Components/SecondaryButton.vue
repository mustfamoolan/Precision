<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        class="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-surface-container-highest text-on-surface font-label font-bold text-sm hover:bg-surface-container-high active:scale-95 transition-all disabled:opacity-50 disabled:pointer-events-none"
    >
        <slot />
    </button>
</template>
