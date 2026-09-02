<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useMarque } from "@/Composables/useMarque";

const { nom: marqueNom } = useMarque();


defineProps({
    error: String,
});

const form = useForm({
    username: '',
    password: '',
});

const submit = () => {
    form.post(route('admin.login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Admin Login" />

    <div class="flex min-h-screen items-center justify-center bg-slate-900 px-4">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold text-purple-400">{{ marqueNom }}</h1>
                <p class="mt-2 text-slate-400">Administration</p>
            </div>

            <!-- Login form -->
            <div class="rounded-xl bg-slate-800 p-8 shadow-xl">
                <h2 class="mb-6 text-xl font-semibold text-white">Connexion Admin</h2>

                <!-- Error message -->
                <div v-if="error" class="mb-4 rounded-lg bg-red-500/10 p-4 text-sm text-red-400">
                    {{ error }}
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel for="username" value="Identifiant" class="text-slate-300" />
                        <TextInput
                            id="username"
                            v-model="form.username"
                            type="text"
                            class="mt-1 block w-full border-slate-600 bg-slate-700 text-white placeholder-slate-400 focus:border-purple-500 focus:ring-purple-500"
                            required
                            autofocus
                            autocomplete="username"
                        />
                        <InputError class="mt-2" :message="form.errors.username" />
                    </div>

                    <div>
                        <InputLabel for="password" value="Mot de passe" class="text-slate-300" />
                        <TextInput
                            id="password"
                            v-model="form.password"
                            type="password"
                            class="mt-1 block w-full border-slate-600 bg-slate-700 text-white placeholder-slate-400 focus:border-purple-500 focus:ring-purple-500"
                            required
                            autocomplete="current-password"
                        />
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <PrimaryButton
                        class="w-full justify-center bg-purple-600 hover:bg-purple-700 focus:bg-purple-700"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Se connecter
                    </PrimaryButton>
                </form>
            </div>

            <p class="mt-4 text-center text-sm text-slate-500">
                Accès réservé aux administrateurs
            </p>
        </div>
    </div>
</template>
