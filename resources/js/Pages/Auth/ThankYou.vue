<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';

const { t } = useTranslations();
const { localizedRoute } = useLocalizedRoute();

defineProps({
    email: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <GuestLayout>
        <Head :title="t('thank_you_title')" />

        <div class="text-center">
            <!-- Icone email -->
            <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/30">
                <svg class="h-10 w-10 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 mb-3">
                {{ t('thank_you_title') }}
            </h1>

            <p class="text-slate-600 dark:text-slate-400 mb-2">
                {{ t('thank_you_message') }}
            </p>

            <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-8">
                {{ email }}
            </p>

            <Link :href="localizedRoute('home')">
                <PrimaryButton class="w-full justify-center">
                    {{ t('thank_you_back_home') }}
                </PrimaryButton>
            </Link>
        </div>
    </GuestLayout>
</template>
