<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    message: String,
});
</script>

<template>
    <Head title="Invitation invalide" />

    <div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-slate-100 dark:bg-slate-900">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center">
                <svg class="h-12 w-12 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                Invitation invalide
            </h2>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white dark:bg-slate-800 py-8 px-4 shadow-xl shadow-slate-200/50 sm:rounded-2xl sm:px-10 text-center border border-slate-200 dark:border-slate-700">
                <p class="text-slate-600 dark:text-slate-400 mb-6">
                    {{ message }}
                </p>
                <Link
                    :href="route('accountant.login')"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-500"
                >
                    Aller à la connexion
                </Link>
            </div>
        </div>
    </div>
</template>
