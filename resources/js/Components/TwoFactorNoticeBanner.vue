<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

/**
 * Le bandeau des comptes exposés (IBAN et factures émises) qui n'ont pas
 * encore de second facteur : l'échéance, les jours restants, et le lien vers
 * le profil pour choisir sa méthode. Le serveur décide de l'afficher
 * (props.security.two_factor_notice), voir App\Security\TwoFactorPolicy.
 *
 * Plein fond, comme le bandeau d'usurpation : un fond pâle passait inaperçu.
 * Ambre pendant le préavis, rouge les deux derniers jours, en écho au mail
 * de rappel.
 */
const page = usePage();
const { t } = useTranslations();

const notice = computed(() => page.props.security?.two_factor_notice ?? null);
const urgent = computed(() => (notice.value?.days_left ?? 99) <= 2);

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
    <div
        v-if="notice"
        :class="urgent ? 'bg-red-600' : 'bg-amber-500'"
        class="px-4 py-3 text-white"
    >
        <div class="mx-auto flex max-w-7xl flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-2">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
                <span class="text-sm font-medium">
                    {{ t('two_factor_notice.banner', { date }) }}
                    <strong class="font-bold">{{ notice.days_left > 1 ? t('two_factor_notice.banner_days', { days: notice.days_left }) : t('two_factor_notice.banner_day') }}</strong>
                </span>
            </div>
            <Link
                :href="route('profile.edit') + '#two-factor'"
                :class="urgent ? 'text-red-700 hover:bg-red-50' : 'text-amber-700 hover:bg-amber-50'"
                class="w-full justify-center rounded-lg bg-white px-4 py-1.5 text-center text-sm font-semibold transition-colors sm:w-auto"
            >
                {{ t('two_factor_notice.banner_action') }}
            </Link>
        </div>
    </div>
</template>
