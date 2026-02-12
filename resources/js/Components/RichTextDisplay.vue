<script setup>
defineProps({
    content: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div
        v-if="content"
        class="rich-text-display prose prose-sm dark:prose-invert max-w-none"
        v-html="content"
    />
</template>

<style>
.rich-text-display {
    @apply text-slate-600 dark:text-slate-400;
}

.rich-text-display p {
    @apply my-2;
}

.rich-text-display p:first-child {
    @apply mt-0;
}

.rich-text-display p:last-child {
    @apply mb-0;
}

.rich-text-display ul {
    @apply list-disc pl-5 my-2;
}

.rich-text-display ol {
    @apply list-decimal pl-5 my-2;
}

.rich-text-display li {
    @apply my-1;
}

.rich-text-display strong {
    @apply font-semibold text-slate-700 dark:text-slate-300;
}

.rich-text-display em {
    @apply italic;
}

.rich-text-display u {
    @apply underline;
}
</style>
