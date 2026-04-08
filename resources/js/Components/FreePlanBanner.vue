<script setup>
import { Link } from "@inertiajs/vue3";
</script>

<template>
    <div
        class="bg-emerald-600 dark:bg-emerald-400 border-b border-amber-200 dark:border-emerald-800 px-4 py-3"
    >
        <div
            class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between max-w-7xl mx-auto"
        >
            <div class="flex items-center gap-2">
                <svg
                    class="flex-shrink-0 w-5 h-5 text-yellow-400 dark:text-yellow-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z"
                    />
                </svg>
                <span class="text-emerald-900 dark:text-emerald-900 text-sm">
                    Vous utilisez le plan Gratuit. Passez à Essentiel pour
                    débloquer plus de fonctionnalités.
                </span>
            </div>
            <Link
                :href="route('subscription.index')"
                class="w-full sm:w-auto text-center bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-1.5 rounded-lg text-sm font-medium transition-colors"
            >
                Voir les plans
            </Link>
        </div>
    </div>
</template>
