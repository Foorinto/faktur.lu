<script setup>
import { computed } from 'vue';
import DOMPurify from 'dompurify';

const props = defineProps({
    content: {
        type: String,
        default: '',
    },
});

// Sanitize le HTML avec DOMPurify pour eviter le Stored XSS.
// Whitelist limitee aux tags utilises par Tiptap (editeur rich-text).
const sanitizedContent = computed(() => {
    if (!props.content) return '';
    return DOMPurify.sanitize(props.content, {
        ALLOWED_TAGS: [
            'p', 'br', 'strong', 'em', 'u', 's',
            'ul', 'ol', 'li',
            'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'blockquote', 'code', 'pre',
        ],
        ALLOWED_ATTR: ['href', 'target', 'rel', 'class'],
        ALLOWED_URI_REGEXP: /^(?:(?:https?|mailto|tel):|[^a-z]|[a-z+.\-]+(?:[^a-z+.\-:]|$))/i,
    });
});
</script>

<template>
    <div
        v-if="sanitizedContent"
        class="rich-text-display prose prose-sm dark:prose-invert max-w-none"
        v-html="sanitizedContent"
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

.rich-text-display a {
    color: #2563eb;
    text-decoration: underline;
    cursor: pointer;
}

.rich-text-display a:hover {
    color: #1d4ed8;
}
</style>
