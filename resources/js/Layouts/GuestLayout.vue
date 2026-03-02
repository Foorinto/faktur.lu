<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="relative flex min-h-screen flex-col items-center bg-gray-50 pt-6 sm:justify-center sm:pt-0 dark:bg-surface-dark overflow-hidden"
    >
        <!-- Decorative blur circles -->
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-primary-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-sky-500/10 rounded-full blur-3xl"></div>

        <div class="relative">
            <Link href="/">
                <ApplicationLogo size="lg" />
            </Link>
        </div>

        <div
            class="relative mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-xl shadow-gray-200/50 border border-gray-200 sm:max-w-md sm:rounded-2xl dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50"
        >
            <slot />
        </div>
    </div>
</template>
