<script setup>
import { Link } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';
import AccountantAuthCard from '@/Components/AccountantAuthCard.vue';

const { t } = useTranslations();

defineProps({ recoveryCodes: Array });
</script>

<template>
    <AccountantAuthCard
        :title="t('accountant_2fa_recovery_title')"
        :subtitle="t('accountant_2fa_recovery_intro')"
    >
        <!-- Un comptable qui perd son téléphone sans ces codes perd l'accès aux
             dossiers de tous ses clients. L'avertissement est en ambre parce
             que la page ne sera plus jamais affichée. -->
        <p class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200">
            {{ t('accountant_2fa_recovery_warning') }}
        </p>

        <ul class="mb-6 grid grid-cols-2 gap-2 rounded-xl bg-slate-50 p-4 font-mono text-sm dark:bg-gray-800">
            <li v-for="code in recoveryCodes" :key="code" class="text-slate-800 dark:text-slate-200">{{ code }}</li>
        </ul>

        <Link
            :href="route('accountant.dashboard')"
            class="flex w-full justify-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700"
        >
            {{ t('accountant_2fa_recovery_done') }}
        </Link>
    </AccountantAuthCard>
</template>
