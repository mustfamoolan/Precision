<script setup>
const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    options: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: 'Select an option...',
    },
    error: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="relative w-full group">
        <select
            :value="modelValue"
            @change="$emit('update:modelValue', $event.target.value)"
            :class="[
                'w-full bg-surface-container-highest border rounded-lg px-4 py-3 text-sm font-body text-on-surface appearance-none focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all cursor-pointer',
                error ? 'border-error' : 'border-outline-variant/20'
            ]"
        >
            <option disabled value="">{{ placeholder }}</option>
            <option
                v-for="option in options"
                :key="typeof option === 'object' ? option.value : option"
                :value="typeof option === 'object' ? option.value : option"
            >
                {{ typeof option === 'object' ? option.label : option }}
            </option>
        </select>
        <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-on-surface-variant group-focus-within:text-primary transition-colors">
            <span class="material-symbols-outlined text-xl">expand_more</span>
        </div>
    </div>
</template>
