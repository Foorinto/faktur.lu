<script setup>
import { ref, computed } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const recovery = ref(false);
const { t } = useTranslations();

const form = useForm({
    code: '',
    recovery_code: '',
});

const toggleRecovery = () => {
    recovery.value = !recovery.value;
    form.code = '';
    form.recovery_code = '';
};

const submit = () => {
    form.post(route('two-factor.login'));
};
</script>

<template>
    <GuestLayout>
        <Head :title="t('two_factor_title')" />

        <div class="mb-4 text-sm text-slate-600 dark:text-slate-400">
            <template v-if="!recovery">
                {{ t('two_factor_description') }}
            </template>
            <template v-else>
                {{ t('two_factor_recovery_description') }}
            </template>
        </div>

        <form @submit.prevent="submit">
            <div v-if="!recovery">
                <InputLabel for="code" :value="t('authentication_code')" />
                <TextInput
                    id="code"
                    ref="codeInput"
                    v-model="form.code"
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="6"
                    class="mt-1 block w-full text-center text-2xl tracking-widest"
                    autofocus
                    autocomplete="one-time-code"
                    placeholder="000000"
                />
                <InputError class="mt-2" :message="form.errors.code" />
            </div>

            <div v-else>
                <InputLabel for="recovery_code" :value="t('recovery_code')" />
                <TextInput
                    id="recovery_code"
                    ref="recoveryCodeInput"
                    v-model="form.recovery_code"
                    type="text"
                    class="mt-1 block w-full"
                    autocomplete="one-time-code"
                    placeholder="XXXX-XXXX-XXXX"
                />
                <InputError class="mt-2" :message="form.errors.recovery_code" />
            </div>

            <div class="mt-6 flex items-center justify-between">
                <button
                    type="button"
                    class="rounded-xl text-sm text-slate-600 underline hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 cursor-pointer dark:text-slate-400 dark:hover:text-slate-100 dark:focus:ring-offset-slate-800"
                    @click="toggleRecovery"
                >
                    <template v-if="!recovery">
                        {{ t('use_recovery_code') }}
                    </template>
                    <template v-else>
                        {{ t('use_authentication_code') }}
                    </template>
                </button>

                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ t('verify') }}
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
