<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';
import { usePlanFeatures } from '@/Composables/usePlanFeatures';

const { t } = useTranslations();
const { isLocked, minPlanFor } = usePlanFeatures();

const currentRoute = computed(() => usePage().url);

const links = [
    { label: () => t('revenue_book'), href: 'reports.revenue-book', match: ['/reports/revenue-book'], requires: 'accounting_exports' },
    { label: () => t('faia_export'), href: 'exports.audit.index', match: ['/exports/audit'] },
    { label: () => t('accounting_export'), href: 'exports.accounting.index', match: ['/exports/accounting'], requires: 'accounting_exports' },
    { label: () => t('fiscal_summary'), href: 'reports.fiscal-summary', match: ['/reports/fiscal-summary'], requires: 'accounting_exports' },
];

const isActive = (match) => {
    const url = currentRoute.value;
    return match.some(m => url.startsWith(m));
};
</script>

<template>
    <!--
        Barre de section, soulignée pour se lire comme une navigation et non
        comme quatre liens en vrac : un client payant ne savait pas que le
        livre de recettes et les exports comptables vivaient au même endroit
        (2026-08-28).
    -->
    <nav class="flex items-center gap-1 overflow-x-auto border-b border-gray-200 pb-2 dark:border-gray-700">
        <Link
            v-for="link in links"
            :key="link.href"
            :href="route(link.href)"
            :class="[
                'whitespace-nowrap inline-flex items-center gap-1.5 rounded-lg px-2 sm:px-3 py-1.5 text-xs sm:text-sm font-medium transition-colors',
                isActive(link.match)
                    ? 'bg-accent-rose text-white dark:bg-accent-rose dark:text-white'
                    : 'text-slate-500 hover:bg-gray-50 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-gray-800 dark:hover:text-slate-300'
            ]"
        >
            {{ link.label() }}
            <span
                v-if="link.requires && isLocked(link.requires)"
                :class="[
                    'inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-bold',
                    minPlanFor(link.requires) === 'Pro'
                        ? 'bg-violet-100 text-violet-700'
                        : 'bg-amber-100 text-amber-800',
                ]"
                :title="`Plan ${minPlanFor(link.requires)} requis`"
            >
                🔒 {{ minPlanFor(link.requires) }}
            </span>
        </Link>
    </nav>
</template>
