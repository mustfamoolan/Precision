<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import FormField from '@/Components/FormField.vue';
import { ref } from 'vue';

const form = useForm({
    email: 'admin@admin.com',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Login" />

    <div class="min-h-screen flex flex-col justify-center items-center bg-background relative overflow-hidden px-4">
        <!-- Background Decoration -->
        <div class="absolute -top-24 -left-24 w-64 h-64 bg-primary/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-primary/5 rounded-full blur-3xl"></div>
        
        <!-- Login Card -->
        <div class="w-full max-w-md bg-surface-container-lowest/60 backdrop-blur-xl border border-outline-variant/30 rounded-2xl shadow-2xl p-8 sm:p-10 z-10 transition-colors">
            <!-- Header -->
            <div class="flex flex-col items-center mb-10">
                <div class="w-16 h-16 rounded-xl bg-primary/10 flex items-center justify-center mb-4 transition-transform hover:scale-110 duration-300">
                    <span class="material-symbols-outlined text-primary text-4xl">gallery_thumbnail</span>
                </div>
                <h1 class="font-headline text-3xl font-extrabold text-on-surface tracking-tight leading-tight">Precision Admin</h1>
                <p class="text-outline font-label text-xs uppercase tracking-[0.2em] mt-2">Dubai Branch Console</p>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <FormField label="Email Address" :error="form.errors.email">
                    <TextInput
                        v-model="form.email"
                        type="email"
                        placeholder="admin@admin.com"
                        required
                        autofocus
                        autocomplete="username"
                        :error="!!form.errors.email"
                    />
                </FormField>

                <FormField label="Password" :error="form.errors.password">
                    <TextInput
                        v-model="form.password"
                        type="password"
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                        :error="!!form.errors.password"
                    />
                </FormField>

                <div class="flex items-center justify-between">
                    <label class="flex items-center cursor-pointer group">
                        <div class="relative flex items-center">
                            <input
                                type="checkbox"
                                v-model="form.remember"
                                class="peer h-4 w-4 opacity-0 absolute cursor-pointer"
                            />
                            <div class="h-5 w-5 border-2 border-outline-variant rounded transition-all peer-checked:bg-primary peer-checked:border-primary flex items-center justify-center">
                                <span class="material-symbols-outlined text-white text-[14px] opacity-0 peer-checked:opacity-100 transition-opacity">check</span>
                            </div>
                        </div>
                        <span class="ml-3 text-sm font-medium text-outline group-hover:text-on-surface transition-colors">Remember Me</span>
                    </label>
                    <a href="#" class="text-sm font-bold text-primary hover:text-primary-container transition-colors">Forgot Password?</a>
                </div>

                <div class="pt-4">
                    <PrimaryButton
                        class="w-full py-4 text-base"
                        :loading="form.processing"
                        :disabled="form.processing"
                    >
                        Sign In to Console
                    </PrimaryButton>
                </div>
            </form>

            <!-- Footer -->
            <div class="mt-10 pt-6 border-t border-outline-variant/10 text-center">
                <p class="text-xs text-outline font-label uppercase tracking-widest">
                    Authorized Access Only
                </p>
            </div>
        </div>

        <!-- System Footer -->
        <div class="mt-8 text-outline font-label text-[10px] font-bold uppercase tracking-widest opacity-60">
            © 2024 Precision Admin • v1.0.1
        </div>
    </div>
</template>

<style scoped>
.font-headline {
    font-family: 'Manrope', sans-serif;
}
.font-label {
    font-family: 'Inter', sans-serif;
}
</style>
