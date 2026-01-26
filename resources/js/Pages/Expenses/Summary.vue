<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    year: [String, Number],
    years: Array,
    monthlySummary: Object,
    yearSummary: Object,
    categories: Object,
});

const yearFilter = ref(String(props.year));

const months = [
    { key: '01', label: 'Janvier' },
    { key: '02', label: 'Février' },
    { key: '03', label: 'Mars' },
    { key: '04', label: 'Avril' },
    { key: '05', label: 'Mai' },
    { key: '06', label: 'Juin' },
    { key: '07', label: 'Juillet' },
    { key: '08', label: 'Août' },
    { key: '09', label: 'Septembre' },
    { key: '10', label: 'Octobre' },
    { key: '11', label: 'Novembre' },
    { key: '12', label: 'Décembre' },
];

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
    <Head title="Résumé des dépenses" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('expenses.index')"
                        class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                        </svg>
                    </Link>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Résumé des dépenses
                    </h1>
                </div>

                <select
                    v-model="yearFilter"
                    class="rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 dark:bg-gray-800 dark:text-white dark:ring-gray-600 sm:text-sm"
                >
                    <option v-for="y in years" :key="y" :value="y">
                        {{ y }}
                    </option>
                </select>
            </div>
        </template>

        <!-- Year Summary Cards -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800 px-4 py-5">
                <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Total HT {{ year }}</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                    {{ formatCurrency(yearSummary.total_ht) }}
                </dd>
            </div>
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800 px-4 py-5">
                <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">TVA récupérable</dt>
                <dd class="mt-1 text-2xl font-semibold text-green-600 dark:text-green-400">
                    {{ formatCurrency(yearSummary.total_vat) }}
                </dd>
            </div>
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800 px-4 py-5">
                <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Total TTC</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                    {{ formatCurrency(yearSummary.total_ttc) }}
                </dd>
            </div>
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800 px-4 py-5">
                <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Dépenses</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                    {{ yearSummary.count }}
                </dd>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Monthly Summary -->
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Par mois</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="py-3 pl-6 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-white">Mois</th>
                                <th class="px-3 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white">HT</th>
                                <th class="px-3 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white">TVA</th>
                                <th class="py-3 pl-3 pr-6 text-right text-sm font-semibold text-gray-900 dark:text-white">Nb</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                            <tr v-for="month in months" :key="month.key">
                                <td class="py-3 pl-6 pr-3 text-sm text-gray-900 dark:text-white">
                                    {{ month.label }}
                                </td>
                                <td class="px-3 py-3 text-right text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatCurrency(getMonthData(month.key).total_ht) }}
                                </td>
                                <td class="px-3 py-3 text-right text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatCurrency(getMonthData(month.key).total_vat) }}
                                </td>
                                <td class="py-3 pl-3 pr-6 text-right text-sm text-gray-500 dark:text-gray-400">
                                    {{ getMonthData(month.key).count || 0 }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <td class="py-3 pl-6 pr-3 text-sm font-semibold text-gray-900 dark:text-white">Total</td>
                                <td class="px-3 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ formatCurrency(yearSummary.total_ht) }}
                                </td>
                                <td class="px-3 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ formatCurrency(yearSummary.total_vat) }}
                                </td>
                                <td class="py-3 pl-3 pr-6 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ yearSummary.count }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Category Summary -->
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Par catégorie</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="py-3 pl-6 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-white">Catégorie</th>
                                <th class="px-3 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white">HT</th>
                                <th class="px-3 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white">TVA</th>
                                <th class="py-3 pl-3 pr-6 text-right text-sm font-semibold text-gray-900 dark:text-white">Nb</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                            <tr v-for="(label, key) in categories" :key="key">
                                <td class="py-3 pl-6 pr-3 text-sm text-gray-900 dark:text-white">
                                    {{ label }}
                                </td>
                                <td class="px-3 py-3 text-right text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatCurrency(getCategoryData(key).total_ht) }}
                                </td>
                                <td class="px-3 py-3 text-right text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatCurrency(getCategoryData(key).total_vat) }}
                                </td>
                                <td class="py-3 pl-3 pr-6 text-right text-sm text-gray-500 dark:text-gray-400">
                                    {{ getCategoryData(key).count || 0 }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <td class="py-3 pl-6 pr-3 text-sm font-semibold text-gray-900 dark:text-white">Total</td>
                                <td class="px-3 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ formatCurrency(yearSummary.total_ht) }}
                                </td>
                                <td class="px-3 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ formatCurrency(yearSummary.total_vat) }}
                                </td>
                                <td class="py-3 pl-3 pr-6 text-right text-sm font-semibold text-gray-900 dark:text-white">
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
