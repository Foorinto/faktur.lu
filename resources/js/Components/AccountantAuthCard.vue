<script setup>
import { Head } from '@inertiajs/vue3';

/**
 * Habillage commun des écrans d'authentification du portail comptable.
 *
 * Les trois écrans du second facteur reprennent la mise en page de la
 * connexion : un comptable qui vient de saisir son mot de passe doit
 * reconnaître qu'il est encore chez faktur.lu, et non sur une page qui lui
 * réclame un code sans qu'il sache pourquoi.
 */
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
});
</script>

<template>
    <Head :title="title" />

    <div class="flex min-h-screen flex-col justify-center bg-slate-100 py-12 dark:bg-surface-dark sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center">
                <svg class="h-12 w-12 text-primary-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14H6v-2h6v2zm4-4H6v-2h10v2zm0-4H6V7h10v2z" />
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                {{ title }}
            </h2>
            <p v-if="subtitle" class="mt-2 text-center text-sm text-slate-600 dark:text-slate-400">
                {{ subtitle }}
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div
                class="border border-gray-200 bg-white px-4 py-8 shadow-xl shadow-gray-200/50 dark:border-gray-700 dark:bg-surface-card sm:rounded-2xl sm:px-10"
            >
                <slot />
            </div>
        </div>
    </div>
</template>
