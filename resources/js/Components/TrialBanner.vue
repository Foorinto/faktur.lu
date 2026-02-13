<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    daysRemaining: {
        type: Number,
        required: true,
    },
});
</script>

<template>
    <div class="bg-amber-50 dark:bg-amber-900/30 border-b border-amber-200 dark:border-amber-800 px-4 py-2">
        <div class="flex items-center justify-between max-w-7xl mx-auto">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span class="text-amber-800 dark:text-amber-200 text-sm">
                    <strong>{{ daysRemaining }}</strong>
                    {{ daysRemaining === 1 ? 'jour restant' : 'jours restants' }}
                    sur votre essai gratuit
                </span>
            </div>
            <Link
                :href="route('subscription.index')"
                class="text-amber-700 dark:text-amber-300 hover:text-amber-900 dark:hover:text-amber-100 text-sm font-medium flex items-center gap-1 transition-colors"
            >
                Choisir un abonnement
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </Link>
        </div>
    </div>
</template>
