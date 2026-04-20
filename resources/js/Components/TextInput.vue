<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        required: true,
    },
    type: {
        type: String,
        default: 'text',
    },
    placeholder: {
        type: String,
        default: '',
    },
    prefix: {
        type: String,
        default: '',
    },
    error: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['update:modelValue']);

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <div class="relative w-full group">
        <span v-if="prefix" class="absolute inset-y-0 left-0 flex items-center pl-4 text-on-surface-variant font-body text-sm pointer-events-none group-focus-within:text-primary transition-colors">
            {{ prefix }}
        </span>
        <input
            :type="type"
            :class="[
                'w-full bg-surface-container-highest border rounded-lg py-3 text-sm font-body text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all',
                prefix ? 'pl-8 pr-4' : 'px-4',
                error ? 'border-error' : 'border-outline-variant/20'
            ]"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            ref="input"
            :placeholder="placeholder"
        />
    </div>
</template>
