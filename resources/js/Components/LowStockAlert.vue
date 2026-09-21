<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    products: { type: Array, default: () => [] },
});

const shouldShow = computed(() => props.products.length > 0);

const formatQty = (v) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 4 }).format(v || 0);
</script>

<template>
    <div v-if="shouldShow" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/40 dark:bg-amber-900/20">
        <div class="flex items-start gap-3">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="min-w-0 flex-1">
                <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                    {{ t('stock.alert_title', { count: products.length }) }}
                </h3>
                <ul class="mt-2 space-y-1">
                    <li v-for="p in products" :key="p.id" class="text-sm text-amber-700 dark:text-amber-300">
                        <span class="font-medium">{{ p.designation }}</span>
                        <span class="ml-1 text-amber-600 dark:text-amber-400">
                            {{ t('stock.alert_line', { current: formatQty(p.current_stock), threshold: formatQty(p.threshold) }) }}
                        </span>
                    </li>
                </ul>
                <Link :href="route('stock.index')" class="mt-2 inline-block text-sm font-medium text-amber-800 underline hover:text-amber-900 dark:text-amber-200">
                    {{ t('stock.alert_cta') }}
                </Link>
            </div>
        </div>
    </div>
</template>
