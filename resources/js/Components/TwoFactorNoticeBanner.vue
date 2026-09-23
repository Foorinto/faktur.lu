<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

/**
 * Le bandeau des comptes exposés (IBAN et factures émises) qui n'ont pas
 * encore de second facteur : l'échéance, les jours restants, et le lien vers
 * le profil pour choisir sa méthode. Le serveur décide de l'afficher
 * (props.security.two_factor_notice), voir App\Security\TwoFactorPolicy.
 */
const page = usePage();
const { t } = useTranslations();

const notice = computed(() => page.props.security?.two_factor_notice ?? null);

const date = computed(() => {
    if (!notice.value) return '';
    try {
        return new Date(notice.value.deadline).toLocaleDateString(page.props.locale || 'fr', { day: 'numeric', month: 'long' });
    } catch {
        return new Date(notice.value.deadline).toLocaleDateString();
    }
});
</script>

<template>
    <div v-if="notice" class="border-b border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-900/30">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-2">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
                <span class="text-sm text-amber-900 dark:text-amber-100">
                    {{ t('two_factor_notice.banner', { date }) }}
                    <strong>{{ notice.days_left > 1 ? t('two_factor_notice.banner_days', { days: notice.days_left }) : t('two_factor_notice.banner_day') }}</strong>
                </span>
            </div>
            <Link
                :href="route('profile.edit') + '#two-factor'"
                class="w-full justify-center rounded-lg bg-amber-600 px-4 py-1.5 text-center text-sm font-medium text-white transition-colors hover:bg-amber-700 sm:w-auto"
            >
                {{ t('two_factor_notice.banner_action') }}
            </Link>
        </div>
    </div>
</template>
