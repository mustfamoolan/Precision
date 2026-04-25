<script setup>
defineProps({
    variant: {
        type: String,
        default: 'neutral' // primary, success, warning, error, info, neutral
    }
});
</script>

<template>
    <span 
        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider inline-flex items-center"
        :class="[
            variant === 'primary' ? 'bg-primary/10 text-primary' : '',
            variant === 'success' ? 'bg-emerald-500/10 text-emerald-500' : '',
            variant === 'warning' ? 'bg-amber-500/10 text-amber-500' : '',
            variant === 'error' ? 'bg-rose-500/10 text-rose-500' : '',
            variant === 'info' ? 'bg-sky-500/10 text-sky-500' : '',
            variant === 'neutral' ? 'bg-slate-500/10 text-slate-500' : '',
            variant === 'purple' ? 'bg-purple-500/10 text-purple-500' : '',
            variant === 'orange' ? 'bg-orange-500/10 text-orange-500' : '',
        ]"
    >
        <slot />
    </span>
</template>
