<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    invoices: Array,
    totals: Object,
    vatBreakdown: Array,
    filters: Object,
    years: Array,
    periods: Array,
});

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);

const updateFilters = () => {
    router.get(route('reports.revenue-book'), {
        start_date: startDate.value,
        end_date: endDate.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const applyPeriod = (period) => {
    startDate.value = period.start;
    endDate.value = period.end;
    updateFilters();
};

const applyYear = (year) => {
    startDate.value = `${year}-01-01`;
    endDate.value = `${year}-12-31`;
    updateFilters();
};

const formatCurrency = (amount, currency = 'EUR') => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: currency,
    }).format(amount);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR');
};

const formatPeriodLabel = () => {
    const start = new Date(startDate.value);
    const end = new Date(endDate.value);
    return `du ${start.toLocaleDateString('fr-FR')} au ${end.toLocaleDateString('fr-FR')}`;
};

const exportPdf = () => {
    window.location.href = route('reports.revenue-book.pdf', {
        start_date: startDate.value,
        end_date: endDate.value,
    });
};

const exportCsv = () => {
    window.location.href = route('reports.revenue-book.csv', {
        start_date: startDate.value,
        end_date: endDate.value,
    });
};
</script>

<template>
    <Head title="Livre des recettes" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Livre des recettes
                </h1>
                <div class="flex items-center space-x-3">
                    <button
                        type="button"
                        @click="exportCsv"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                        CSV
                    </button>
                    <button
                        type="button"
                        @click="exportPdf"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                        PDF
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Period Selection -->
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Période</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="flex flex-wrap gap-4 items-end">
                        <!-- Date inputs -->
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Du
                            </label>
                            <input
                                id="start_date"
                                v-model="startDate"
                                type="date"
                                @change="updateFilters"
                                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Au
                            </label>
                            <input
                                id="end_date"
                                v-model="endDate"
                                type="date"
                                @change="updateFilters"
                                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <!-- Quick periods -->
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="period in periods"
                                :key="period.label"
                                type="button"
                                @click="applyPeriod(period)"
                                :class="[
                                    'px-3 py-2 text-sm rounded-md border',
                                    startDate === period.start && endDate === period.end
                                        ? 'bg-indigo-100 border-indigo-300 text-indigo-700 dark:bg-indigo-900 dark:border-indigo-700 dark:text-indigo-300'
                                        : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600'
                                ]"
                            >
                                {{ period.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Year quick select -->
                    <div class="mt-4 flex items-center gap-2">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Année :</span>
                        <button
                            v-for="year in years"
                            :key="year"
                            type="button"
                            @click="applyYear(year)"
                            :class="[
                                'px-3 py-1 text-sm rounded-md border',
                                startDate === `${year}-01-01` && endDate === `${year}-12-31`
                                    ? 'bg-indigo-100 border-indigo-300 text-indigo-700 dark:bg-indigo-900 dark:border-indigo-700 dark:text-indigo-300'
                                    : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600'
                            ]"
                        >
                            {{ year }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        Factures payées
                                    </dt>
                                    <dd class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ totals.count }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        Total HT
                                    </dt>
                                    <dd class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ formatCurrency(totals.ht) }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 4.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        Total TVA
                                    </dt>
                                    <dd class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ formatCurrency(totals.vat) }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        Total TTC
                                    </dt>
                                    <dd class="text-lg font-semibold text-green-600 dark:text-green-400">
                                        {{ formatCurrency(totals.ttc) }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VAT Breakdown -->
            <div v-if="vatBreakdown.length > 0" class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Récapitulatif TVA</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="py-3.5 pl-6 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                    Taux TVA
                                </th>
                                <th class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                    Base HT
                                </th>
                                <th class="py-3.5 pl-3 pr-6 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                    Montant TVA
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                            <tr v-for="vat in vatBreakdown" :key="vat.rate">
                                <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm text-gray-900 dark:text-white">
                                    {{ vat.rate }}%
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatCurrency(vat.base) }}
                                </td>
                                <td class="whitespace-nowrap py-4 pl-3 pr-6 text-right text-sm font-medium text-gray-900 dark:text-white">
                                    {{ formatCurrency(vat.amount) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <td class="whitespace-nowrap py-3.5 pl-6 pr-3 text-sm font-semibold text-gray-900 dark:text-white">
                                    Total
                                </td>
                                <td class="whitespace-nowrap px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ formatCurrency(totals.ht) }}
                                </td>
                                <td class="whitespace-nowrap py-3.5 pl-3 pr-6 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ formatCurrency(totals.vat) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        Détail des recettes
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                            ({{ formatPeriodLabel() }})
                        </span>
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="py-3.5 pl-6 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                    Date paiement
                                </th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                    N° Facture
                                </th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                    Client
                                </th>
                                <th class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                    Total HT
                                </th>
                                <th class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                    TVA
                                </th>
                                <th class="py-3.5 pl-3 pr-6 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                    Total TTC
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                            <tr v-if="invoices.length === 0">
                                <td colspan="6" class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="mt-2">Aucune recette sur cette période.</p>
                                </td>
                            </tr>
                            <tr v-for="invoice in invoices" :key="invoice.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatDate(invoice.paid_at) }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                    <a
                                        :href="route('invoices.show', invoice.id)"
                                        class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                                    >
                                        {{ invoice.number }}
                                    </a>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ invoice.client?.name }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatCurrency(invoice.total_ht, invoice.currency) }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatCurrency(invoice.total_vat, invoice.currency) }}
                                </td>
                                <td class="whitespace-nowrap py-4 pl-3 pr-6 text-right text-sm font-medium text-gray-900 dark:text-white">
                                    {{ formatCurrency(invoice.total_ttc, invoice.currency) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="invoices.length > 0" class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <td colspan="3" class="whitespace-nowrap py-3.5 pl-6 pr-3 text-sm font-semibold text-gray-900 dark:text-white">
                                    Total ({{ totals.count }} facture{{ totals.count > 1 ? 's' : '' }})
                                </td>
                                <td class="whitespace-nowrap px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ formatCurrency(totals.ht) }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ formatCurrency(totals.vat) }}
                                </td>
                                <td class="whitespace-nowrap py-3.5 pl-3 pr-6 text-right text-sm font-bold text-green-600 dark:text-green-400">
                                    {{ formatCurrency(totals.ttc) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
