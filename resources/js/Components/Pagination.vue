<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array,
});
</script>

<template>
    <nav v-if="links.length > 3" class="flex items-center justify-between">
        <div class="flex flex-1 justify-between sm:hidden">
            <Link
                v-if="links[0].url"
                :href="links[0].url"
                class="relative inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-50 dark:border-gray-700 dark:bg-surface-card dark:text-slate-200 dark:hover:bg-gray-800"
            >
                Précédent
            </Link>
            <Link
                v-if="links[links.length - 1].url"
                :href="links[links.length - 1].url"
                class="relative ml-3 inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-50 dark:border-gray-700 dark:bg-surface-card dark:text-slate-200 dark:hover:bg-gray-800"
            >
                Suivant
            </Link>
        </div>
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-center">
            <div>
                <nav class="isolate inline-flex -space-x-px rounded-xl shadow-sm" aria-label="Pagination">
                    <template v-for="(link, index) in links" :key="index">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            class="relative inline-flex items-center border px-4 py-2 text-sm font-medium focus:z-20"
                            :class="[
                                link.active
                                    ? 'z-10 border-accent-rose bg-pink-50 text-accent-rose dark:border-pink-400 dark:bg-pink-900/30 dark:text-pink-400'
                                    : 'border-gray-300 bg-white text-slate-500 hover:bg-gray-50 dark:border-gray-700 dark:bg-surface-card dark:text-slate-400 dark:hover:bg-gray-800',
                                index === 0 ? 'rounded-l-xl' : '',
                                index === links.length - 1 ? 'rounded-r-xl' : '',
                            ]"
                        />
                        <span
                            v-else
                            v-html="link.label"
                            class="relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-slate-400 dark:border-gray-700 dark:bg-surface-card dark:text-slate-500"
                            :class="[
                                index === 0 ? 'rounded-l-xl' : '',
                                index === links.length - 1 ? 'rounded-r-xl' : '',
                            ]"
                        />
                    </template>
                </nav>
            </div>
        </div>
    </nav>
</template>
