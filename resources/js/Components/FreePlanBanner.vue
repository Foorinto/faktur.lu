<script setup>
import { Link } from "@inertiajs/vue3";
import { useTranslations } from "@/Composables/useTranslations";

const { t } = useTranslations();
</script>

<template>
    <div
        class="bg-emerald-600 dark:bg-emerald-700 border-b border-emerald-700 dark:border-emerald-800 px-4 py-3"
    >
        <div
            class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between max-w-7xl mx-auto"
        >
            <div class="flex items-center gap-2">
                <svg
                    class="flex-shrink-0 w-5 h-5 text-yellow-300"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z"
                    />
                </svg>
                <span class="text-white text-sm font-medium">
                    {{ t('free_plan_banner_message') }}
                </span>
            </div>
            <!-- Bouton en blanc plein : le fond du bandeau étant déjà emerald,
                 un bouton emerald y était invisible. -->
            <Link
                :href="route('subscription.index')"
                class="w-full sm:w-auto text-center bg-white text-emerald-700 hover:bg-emerald-50 px-4 py-1.5 rounded-lg text-sm font-semibold shadow-sm transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-emerald-600 dark:focus-visible:ring-offset-emerald-700"
            >
                {{ t('view_plans') }}
            </Link>
        </div>
    </div>
</template>
