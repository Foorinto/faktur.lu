<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Inject one or more JSON-LD scripts into the <head>.
 * Accepts a single object or an array of objects.
 *
 * Usage:
 *   <SchemaJsonLd :schemas="[breadcrumbSchema, faqSchema]" />
 */
const props = defineProps({
    schemas: {
        type: [Array, Object],
        required: true,
    },
});

const list = computed(() => {
    const value = props.schemas;
    return Array.isArray(value) ? value : [value];
});
</script>

<template>
    <Head>
        <component
            v-for="(schema, i) in list"
            :key="i"
            is="script"
            type="application/ld+json"
            v-html="JSON.stringify(schema)"
        />
    </Head>
</template>
