<script setup>
import { computed, ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    previewNumber: { type: String, required: true },
    documentType: { type: String, required: true }, // 'invoice' | 'quote'
});

const { t } = useTranslations();
const dismissed = ref(false);
const storageKey = computed(() => `numbering-hint-banner-dismissed-${props.documentType}`);

onMounted(() => {
    try {
        dismissed.value = localStorage.getItem(storageKey.value) === '1';
    } catch (e) {
        dismissed.value = false;
    }
});

function dismiss() {
    dismissed.value = true;
    try {
        localStorage.setItem(storageKey.value, '1');
    } catch (e) {
        // localStorage unavailable, just hide for this session
    }
}
</script>

<template>
    <div
        v-if="!dismissed"
        class="mb-4 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 p-4"
    >
        <div class="flex items-start gap-3">
            <svg class="h-5 w-5 flex-shrink-0 text-indigo-600 dark:text-indigo-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            <div class="flex-1 text-sm">
                <p class="font-medium text-indigo-900 dark:text-indigo-100">
                    {{ documentType === 'invoice'
                        ? t('numbering_hint.invoice_title', { number: previewNumber })
                        : t('numbering_hint.quote_title', { number: previewNumber }) }}
                </p>
                <p class="mt-1 text-indigo-800 dark:text-indigo-200">
                    {{ t('numbering_hint.description') }}
                    <Link href="/settings/business#numbering" class="font-medium underline hover:text-indigo-900 dark:hover:text-indigo-100">
                        {{ t('numbering_hint.cta') }} →
                    </Link>
                </p>
            </div>
            <button
                @click="dismiss"
                type="button"
                class="flex-shrink-0 text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200"
                :aria-label="t('numbering_hint.dismiss')"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</template>
