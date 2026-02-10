<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    year: [String, Number],
    years: Array,
    monthlySummary: Object,
    yearSummary: Object,
    categories: Object,
});

const yearFilter = ref(String(props.year));

const months = computed(() => [
    { key: '01', label: t('months.january') },
    { key: '02', label: t('months.february') },
    { key: '03', label: t('months.march') },
    { key: '04', label: t('months.april') },
    { key: '05', label: t('months.may') },
    { key: '06', label: t('months.june') },
    { key: '07', label: t('months.july') },
    { key: '08', label: t('months.august') },
    { key: '09', label: t('months.september') },
    { key: '10', label: t('months.october') },
    { key: '11', label: t('months.november') },
    { key: '12', label: t('months.december') },
]);

watch(yearFilter, (newYear) => {
    router.get(route('expenses.summary'), { year: newYear }, {
        preserveState: true,
        replace: true,
    });
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0);
};

const getMonthData = (monthKey) => {
    return props.monthlySummary[monthKey] || { total_ht: 0, total_vat: 0, total_ttc: 0, count: 0 };
};

const getCategoryData = (categoryKey) => {
    return props.yearSummary.by_category[categoryKey] || { total_ht: 0, total_vat: 0, count: 0 };
};
</script>

<template>
    <Head :title="t('expenses_summary')" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('expenses.index')"
                        class="text-slate-400 hover:text-slate-500 dark:text-slate-500 dark:hover:text-slate-400"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                        </svg>
                    </Link>
                    <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                        {{ t('expenses_summary') }}
                    </h1>
                </div>

                <select
                    v-model="yearFilter"
                    class="rounded-xl border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-primary-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600 sm:text-sm"
                >
                    <option v-for="y in years" :key="y" :value="y">
                        {{ y }}
                    </option>
                </select>
            </div>
        </template>

        <!-- Year Summary Cards -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800 px-4 py-5">
                <dt class="truncate text-sm font-medium text-slate-500 dark:text-slate-400">Total HT {{ year }}</dt>
                <dd class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white">
                    {{ formatCurrency(yearSummary.total_ht) }}
                </dd>
            </div>
            <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800 px-4 py-5">
                <dt class="truncate text-sm font-medium text-slate-500 dark:text-slate-400">TVA récupérable</dt>
                <dd class="mt-1 text-2xl font-semibold text-green-600 dark:text-green-400">
                    {{ formatCurrency(yearSummary.total_vat) }}
                </dd>
            </div>
            <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800 px-4 py-5">
                <dt class="truncate text-sm font-medium text-slate-500 dark:text-slate-400">Total TTC</dt>
                <dd class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white">
                    {{ formatCurrency(yearSummary.total_ttc) }}
                </dd>
            </div>
            <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800 px-4 py-5">
                <dt class="truncate text-sm font-medium text-slate-500 dark:text-slate-400">Dépenses</dt>
                <dd class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white">
                    {{ yearSummary.count }}
                </dd>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Monthly Summary -->
            <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">Par mois</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-700">
                            <tr>
                                <th class="py-3 pl-6 pr-3 text-left text-sm font-semibold text-slate-900 dark:text-white">Mois</th>
                                <th class="px-3 py-3 text-right text-sm font-semibold text-slate-900 dark:text-white">HT</th>
                                <th class="px-3 py-3 text-right text-sm font-semibold text-slate-900 dark:text-white">TVA</th>
                                <th class="py-3 pl-3 pr-6 text-right text-sm font-semibold text-slate-900 dark:text-white">Nb</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-800">
                            <tr v-for="month in months" :key="month.key">
                                <td class="py-3 pl-6 pr-3 text-sm text-slate-900 dark:text-white">
                                    {{ month.label }}
                                </td>
                                <td class="px-3 py-3 text-right text-sm text-slate-500 dark:text-slate-400">
                                    {{ formatCurrency(getMonthData(month.key).total_ht) }}
                                </td>
                                <td class="px-3 py-3 text-right text-sm text-slate-500 dark:text-slate-400">
                                    {{ formatCurrency(getMonthData(month.key).total_vat) }}
                                </td>
                                <td class="py-3 pl-3 pr-6 text-right text-sm text-slate-500 dark:text-slate-400">
                                    {{ getMonthData(month.key).count || 0 }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-slate-50 dark:bg-slate-700">
                            <tr>
                                <td class="py-3 pl-6 pr-3 text-sm font-semibold text-slate-900 dark:text-white">Total</td>
                                <td class="px-3 py-3 text-right text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ formatCurrency(yearSummary.total_ht) }}
                                </td>
                                <td class="px-3 py-3 text-right text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ formatCurrency(yearSummary.total_vat) }}
                                </td>
                                <td class="py-3 pl-3 pr-6 text-right text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ yearSummary.count }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Category Summary -->
            <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">Par catégorie</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-700">
                            <tr>
                                <th class="py-3 pl-6 pr-3 text-left text-sm font-semibold text-slate-900 dark:text-white">Catégorie</th>
                                <th class="px-3 py-3 text-right text-sm font-semibold text-slate-900 dark:text-white">HT</th>
                                <th class="px-3 py-3 text-right text-sm font-semibold text-slate-900 dark:text-white">TVA</th>
                                <th class="py-3 pl-3 pr-6 text-right text-sm font-semibold text-slate-900 dark:text-white">Nb</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-800">
                            <tr v-for="(label, key) in categories" :key="key">
                                <td class="py-3 pl-6 pr-3 text-sm text-slate-900 dark:text-white">
                                    {{ label }}
                                </td>
                                <td class="px-3 py-3 text-right text-sm text-slate-500 dark:text-slate-400">
                                    {{ formatCurrency(getCategoryData(key).total_ht) }}
                                </td>
                                <td class="px-3 py-3 text-right text-sm text-slate-500 dark:text-slate-400">
                                    {{ formatCurrency(getCategoryData(key).total_vat) }}
                                </td>
                                <td class="py-3 pl-3 pr-6 text-right text-sm text-slate-500 dark:text-slate-400">
                                    {{ getCategoryData(key).count || 0 }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-slate-50 dark:bg-slate-700">
                            <tr>
                                <td class="py-3 pl-6 pr-3 text-sm font-semibold text-slate-900 dark:text-white">Total</td>
                                <td class="px-3 py-3 text-right text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ formatCurrency(yearSummary.total_ht) }}
                                </td>
                                <td class="px-3 py-3 text-right text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ formatCurrency(yearSummary.total_vat) }}
                                </td>
                                <td class="py-3 pl-3 pr-6 text-right text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ yearSummary.count }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
