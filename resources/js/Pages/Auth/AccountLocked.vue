<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

/**
 * La page d'arrivée du lien « ce n'était pas moi ».
 *
 * Le compte vient d'être gelé : sessions fermées, connexion refusée. La seule
 * chose à faire ici est d'aller réinitialiser le mot de passe, alors on ne
 * propose que ça.
 */
defineProps({
    event: { type: String, default: null },
    alreadyLocked: { type: Boolean, default: false },
});

const { t } = useTranslations();
</script>

<template>
    <GuestLayout>
        <Head :title="t('account_locked.title')" />

        <h1 class="text-lg font-semibold text-slate-900 dark:text-white">
            {{ alreadyLocked ? t('account_locked.title_already') : t('account_locked.title') }}
        </h1>

        <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">
            {{ t('account_locked.done') }}
        </p>

        <p v-if="event" class="mt-2 text-sm text-slate-600 dark:text-slate-400">
            {{ t('account_locked.event_' + event) }}
        </p>

        <p class="mt-4 text-sm text-slate-600 dark:text-slate-400">
            {{ t('account_locked.next') }}
        </p>

        <div class="mt-6">
            <Link :href="route('password.request')">
                <PrimaryButton>{{ t('account_locked.reset_button') }}</PrimaryButton>
            </Link>
        </div>
    </GuestLayout>
</template>
