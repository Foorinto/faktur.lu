<script setup>
import { useForm } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';
import AccountantAuthCard from '@/Components/AccountantAuthCard.vue';

const { t } = useTranslations();

defineProps({
    qrCodeSvg: String,
    setupKey: String,
});

const form = useForm({ code: '' });

const submit = () => form.post(route('accountant.two-factor.confirm'));
</script>

<template>
    <AccountantAuthCard
        :title="t('accountant_2fa_enroll_title')"
        :subtitle="t('accountant_2fa_enroll_subtitle')"
    >
        <p class="mb-4 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:bg-gray-800 dark:text-slate-400">
            {{ t('accountant_2fa_why') }}
        </p>

        <div class="mb-4 flex justify-center rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700" v-html="qrCodeSvg" />

        <!-- Une saisie manuelle reste possible : tous les gestionnaires
             d'authentification ne lisent pas les QR codes, et un poste sans
             caméra ne doit pas fermer l'accès au portail. -->
        <p class="mb-6 text-center text-xs text-slate-500 dark:text-slate-400">
            {{ t('accountant_2fa_manual_key') }}
            <code class="mt-1 block break-all font-mono text-sm text-slate-700 dark:text-slate-300">{{ setupKey }}</code>
        </p>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
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
                <p v-if="form.errors.code" class="mt-1 text-sm text-pink-600">{{ form.errors.code }}</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="flex w-full justify-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 disabled:opacity-50"
            >
                {{ t('accountant_2fa_activate') }}
            </button>
        </form>
    </AccountantAuthCard>
</template>
