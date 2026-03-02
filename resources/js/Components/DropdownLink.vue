<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
    method: {
        type: String,
        default: 'get',
    },
    as: {
        type: String,
        default: 'a',
    },
});
</script>

<template>
    <Link
        :href="href"
        :method="method"
        :as="as"
        class="block w-full px-4 py-2.5 text-start text-sm leading-5 text-gray-900 transition-colors duration-200 hover:bg-gray-50 hover:text-primary-600 focus:bg-gray-50 focus:outline-none dark:text-slate-200 dark:hover:bg-gray-800 dark:hover:text-primary-400 dark:focus:bg-gray-800"
    >
        <slot />
    </Link>
</template>
