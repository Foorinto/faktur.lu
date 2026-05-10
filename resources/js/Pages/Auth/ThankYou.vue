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
            <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/30">
                <svg class="h-10 w-10 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 mb-3">
                {{ t('thank_you_title') }}
            </h1>

            <p class="text-slate-600 dark:text-slate-400 mb-2">
                {{ t('thank_you_message') }}
            </p>

            <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-6">
                {{ email }}
            </p>

            <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 mb-6 text-left dark:bg-amber-900/20 dark:border-amber-800">
                <div class="flex gap-3">
                    <svg class="h-5 w-5 flex-shrink-0 text-amber-600 dark:text-amber-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <div class="text-sm text-amber-800 dark:text-amber-200">
                        <p class="font-medium mb-1">{{ t('thank_you_check_spam_title') }}</p>
                        <p class="text-amber-700 dark:text-amber-300">{{ t('thank_you_check_spam_message') }}</p>
                    </div>
                </div>
            </div>

            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100 mb-3 text-left">
                {{ t('thank_you_next_steps') }}
            </h2>
            <ol class="text-sm text-slate-600 dark:text-slate-400 space-y-2 mb-8 text-left">
                <li class="flex gap-3">
                    <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-semibold text-primary-700 dark:bg-primary-900/50 dark:text-primary-300">1</span>
                    <span>{{ t('thank_you_step_open') }}</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-semibold text-primary-700 dark:bg-primary-900/50 dark:text-primary-300">2</span>
                    <span>{{ t('thank_you_step_click') }}</span>
                </li>
                <li class="flex gap-3">
                    <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-semibold text-primary-700 dark:bg-primary-900/50 dark:text-primary-300">3</span>
                    <span>{{ t('thank_you_step_onboarding') }}</span>
                </li>
            </ol>

            <Link :href="localizedRoute('home')">
                <PrimaryButton class="w-full justify-center">
                    {{ t('thank_you_back_home') }}
                </PrimaryButton>
            </Link>

            <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">
                {{ t('thank_you_no_email') }}
                <Link :href="route('login')" class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                    {{ t('thank_you_login_resend') }}
                </Link>
            </p>
        </div>
    </GuestLayout>
</template>
