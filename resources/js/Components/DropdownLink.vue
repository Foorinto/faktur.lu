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
        class="block w-full px-4 py-2.5 text-start text-sm leading-5 text-slate-700 transition-colors duration-200 hover:bg-slate-50 hover:text-primary-600 focus:bg-slate-50 focus:outline-none dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-primary-400 dark:focus:bg-slate-700"
    >
        <slot />
    </Link>
</template>
