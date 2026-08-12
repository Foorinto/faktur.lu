<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';
import AccountantAuthCard from '@/Components/AccountantAuthCard.vue';

const { t } = useTranslations();

const form = useForm({ code: '', recovery_code: '' });
const modeSecours = ref(false);

const basculer = () => {
    modeSecours.value = !modeSecours.value;
    form.code = '';
    form.recovery_code = '';
    form.clearErrors();
};

const submit = () => form.post(route('accountant.two-factor.verify'));
</script>

<template>
    <AccountantAuthCard
        :title="t('accountant_2fa_challenge_title')"
        :subtitle="modeSecours ? t('accountant_2fa_recovery_subtitle') : t('accountant_2fa_challenge_subtitle')"
    >
        <form @submit.prevent="submit" class="space-y-4">
            <div v-if="!modeSecours">
                <label for="code" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ t('accountant_2fa_code_label') }}
                </label>
                <input
                    id="code"
                    v-model="form.code"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    required
                    autofocus
                    class="mt-1 block w-full rounded-xl border-gray-300 text-center font-mono text-lg tracking-widest shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                />
            </div>

            <div v-else>
                <label for="recovery_code" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ t('accountant_2fa_recovery_label') }}
                </label>
                <input
                    id="recovery_code"
                    v-model="form.recovery_code"
                    type="text"
                    autocomplete="one-time-code"
                    required
                    autofocus
                    class="mt-1 block w-full rounded-xl border-gray-300 font-mono shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                />
            </div>

            <p v-if="form.errors.code" class="text-sm text-pink-600">{{ form.errors.code }}</p>

            <button
                type="submit"
                :disabled="form.processing"
                class="flex w-full justify-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 disabled:opacity-50"
            >
                {{ t('accountant_2fa_verify') }}
            </button>

            <button
                type="button"
                @click="basculer"
                class="w-full text-center text-sm text-slate-500 underline underline-offset-2 hover:text-slate-700 dark:text-slate-400"
            >
                {{ modeSecours ? t('accountant_2fa_use_code') : t('accountant_2fa_use_recovery') }}
            </button>
        </form>
    </AccountantAuthCard>
</template>
