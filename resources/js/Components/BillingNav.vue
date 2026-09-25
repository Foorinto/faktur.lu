<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';
import { usePlanFeatures } from '@/Composables/usePlanFeatures';

const { t } = useTranslations();
const { isLocked, minPlanFor } = usePlanFeatures();

const currentRoute = computed(() => usePage().url);

const links = [
    { label: () => t('quotes'), href: 'quotes.index', match: ['/quotes'] },
    { label: () => t('invoices'), href: 'invoices.index', match: ['/invoices'] },
    { label: () => t('recurring_invoices'), href: 'recurring-invoices.index', match: ['/recurring-invoices'], requiresFeature: 'recurring_invoices' },
];

const isActive = (match) => {
    const url = currentRoute.value;
    return match.some(m => url.startsWith(m));
};
</script>

<template>
    <nav class="flex items-center gap-1 overflow-x-auto pb-px">
        <Link
            v-for="link in links"
            :key="link.href"
            :href="route(link.href)"
            :class="[
                'whitespace-nowrap rounded-lg px-2 sm:px-3 py-1.5 text-xs sm:text-sm font-medium transition-colors',
                isActive(link.match)
                    ? 'bg-accent-rose text-white dark:bg-accent-rose dark:text-white'
                    : 'text-slate-500 hover:bg-gray-50 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-gray-800 dark:hover:text-slate-300'
            ]"
        >
            {{ link.label() }}
            <span
                v-if="link.requiresFeature && isLocked(link.requiresFeature)"
                class="ml-1.5 inline-flex items-center gap-0.5 rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
                :title="`Plan ${minPlanFor(link.requiresFeature)} requis`"
            >
                🔒 {{ minPlanFor(link.requiresFeature) }}
            </span>
        </Link>
    </nav>
</template>
