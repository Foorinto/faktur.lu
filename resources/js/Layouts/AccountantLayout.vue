<script setup>
import { Link, router } from '@inertiajs/vue3';

defineProps({
    accountant: {
        type: Object,
        default: null,
    },
});

const logout = () => {
    router.post(route('accountant.logout'));
};
</script>

<template>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <!-- Header -->
        <header class="bg-white dark:bg-slate-800 shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center space-x-4">
                        <Link :href="route('accountant.dashboard')" class="flex items-center space-x-2">
                            <svg class="h-8 w-8 text-primary-600" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14H6v-2h6v2zm4-4H6v-2h10v2zm0-4H6V7h10v2z"/>
                            </svg>
                            <span class="text-xl font-bold text-slate-900 dark:text-white">faktur.lu</span>
                        </Link>
                        <span class="text-sm text-slate-500 dark:text-slate-400">Espace Comptable</span>
                    </div>

                    <div v-if="accountant" class="flex items-center space-x-4">
                        <span class="text-sm text-slate-600 dark:text-slate-300">{{ accountant.name || accountant.email }}</span>
                        <button
                            @click="logout"
                            class="inline-flex items-center px-3 py-1.5 border border-slate-300 dark:border-slate-600 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 hover:bg-slate-50 dark:hover:bg-slate-600"
                        >
                            Déconnexion
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main content -->
        <main class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <p class="text-center text-sm text-slate-500 dark:text-slate-400">
                    faktur.lu - Espace Comptable
                </p>
            </div>
        </footer>
    </div>
</template>
