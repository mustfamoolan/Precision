<script setup>
import { onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    maxWidth: {
        type: String,
        default: 'sm:w-[400px]',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

watch(() => props.show, (value) => {
    if (value) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = null;
    }
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-show="show" class="fixed inset-0 bg-inverse-surface/10 backdrop-blur-[2px] z-50 transition-opacity" @click="close" />
        </Transition>

        <Transition
            enter-active-class="transform transition ease-out duration-300"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transform transition ease-in duration-200"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <div
                v-show="show"
                :class="['fixed right-0 top-0 h-screen w-full bg-surface-container-lowest shadow-[0_12px_32px_0_rgba(0,0,0,0.06)] z-50 flex flex-col rounded-l-xl transition-colors', maxWidth]"
            >
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-5 bg-surface-container-lowest sticky top-0 z-10">
                    <h2 class="font-headline text-xl font-bold text-on-surface tracking-tight">
                        <slot name="title">{{ title }}</slot>
                    </h2>
                    <button 
                        v-if="closeable"
                        @click="close"
                        class="text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high p-2 rounded-lg transition-colors"
                    >
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <!-- Body / Content -->
                <div class="flex-1 overflow-y-auto px-6 py-4 space-y-6 bg-surface-container-lowest">
                    <slot />
                </div>

                <!-- Footer -->
                <div v-if="$slots.footer" class="px-6 py-4 bg-surface-container-lowest sticky bottom-0 z-10 flex items-center justify-end gap-3 border-t-0 shadow-[0_-8px_24px_0_rgba(0,0,0,0.02)]">
                    <slot name="footer" />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
