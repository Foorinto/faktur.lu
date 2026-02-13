<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="bg-red-50 dark:bg-red-900/30 border-b border-red-200 dark:border-red-800 px-4 py-3">
        <div class="flex items-center justify-between max-w-7xl mx-auto">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <span class="text-red-800 dark:text-red-200">
                    Votre essai est terminé. Votre compte est en lecture seule.
                </span>
            </div>
            <Link
                :href="route('subscription.index')"
                class="bg-red-600 hover:bg-red-700 text-white px-4 py-1.5 rounded-lg text-sm font-medium transition-colors"
            >
                S'abonner maintenant
            </Link>
        </div>
    </div>
</template>
