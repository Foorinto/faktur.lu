<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    message: String,
});
</script>

<template>
    <Head title="Invitation invalide" />

    <div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-gray-100 dark:bg-gray-900">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center">
                <svg class="h-12 w-12 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                Invitation invalide
            </h2>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white dark:bg-gray-800 py-8 px-4 shadow sm:rounded-lg sm:px-10 text-center">
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    {{ message }}
                </p>
                <Link
                    :href="route('accountant.login')"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-500"
                >
                    Aller à la connexion
                </Link>
            </div>
        </div>
    </div>
</template>
