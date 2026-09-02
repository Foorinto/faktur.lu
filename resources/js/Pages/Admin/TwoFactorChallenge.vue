<script setup>
import { ref, onMounted } from 'vue';
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

const codeInput = ref(null);

const form = useForm({
    code: '',
});

onMounted(() => {
    codeInput.value?.focus();
});

const submit = () => {
    form.post(route('admin.2fa'), {
        onFinish: () => form.reset('code'),
    });
};
</script>

<template>
    <Head title="Vérification 2FA" />

    <div class="flex min-h-screen items-center justify-center bg-slate-900 px-4">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold text-purple-400">{{ marqueNom }}</h1>
                <p class="mt-2 text-slate-400">Administration</p>
            </div>

            <!-- 2FA form -->
            <div class="rounded-xl bg-slate-800 p-8 shadow-xl">
                <div class="mb-6 text-center">
                    <!-- Lock icon -->
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-purple-500/20">
                        <svg class="h-8 w-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-white">Vérification 2FA</h2>
                    <p class="mt-2 text-sm text-slate-400">
                        Entrez le code à 6 chiffres de votre application d'authentification
                    </p>
                </div>

                <!-- Error message -->
                <div v-if="error" class="mb-4 rounded-lg bg-red-500/10 p-4 text-sm text-red-400">
                    {{ error }}
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel for="code" value="Code 2FA" class="sr-only" />
                        <TextInput
                            id="code"
                            ref="codeInput"
                            v-model="form.code"
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="6"
                            class="mt-1 block w-full border-slate-600 bg-slate-700 text-center text-2xl tracking-widest text-white placeholder-slate-400 focus:border-purple-500 focus:ring-purple-500"
                            placeholder="000000"
                            required
                            autocomplete="one-time-code"
                        />
                        <InputError class="mt-2" :message="form.errors.code" />
                    </div>

                    <PrimaryButton
                        class="w-full justify-center bg-purple-600 hover:bg-purple-700 focus:bg-purple-700"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Vérifier
                    </PrimaryButton>
                </form>
            </div>

            <p class="mt-4 text-center text-sm text-slate-500">
                Utilisez Google Authenticator ou une application compatible TOTP
            </p>
        </div>
    </div>
</template>
