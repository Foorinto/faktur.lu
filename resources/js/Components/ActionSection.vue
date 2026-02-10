<script setup>
defineProps({
    title: String,
    description: String,
});
</script>

<template>
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1 flex justify-between">
            <div class="px-4 sm:px-0">
                <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100">
                    <slot name="title" />
                </h3>

                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                    <slot name="description" />
                </p>
            </div>
        </div>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="px-4 py-5 sm:p-6 bg-white dark:bg-slate-800 shadow-xl shadow-slate-200/50 border border-slate-200 dark:border-slate-700 dark:shadow-slate-900/50 sm:rounded-2xl">
                <slot name="content" />
            </div>
        </div>
    </div>
</template>
