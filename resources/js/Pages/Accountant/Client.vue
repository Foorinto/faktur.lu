<script setup>
import AccountantLayout from '@/Layouts/AccountantLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    client: Object,
    years: Array,
    selectedYear: Number,
    selectedQuarter: Number,
    invoices: Array,
    summary: Object,
    recentDownloads: Array,
});

const year = ref(props.selectedYear || props.years[0] || new Date().getFullYear());
const quarter = ref(props.selectedQuarter || null);

const reloadInvoices = () => {
    const params = { year: year.value };
    if (quarter.value) params.quarter = quarter.value;
    router.get(route('accountant.client', props.client.id), params, { preserveState: true, preserveScroll: true });
};

watch([year, quarter], reloadInvoices);

const statusLabels = {
    finalized: 'Finalisée',
    sent: 'Envoyée',
    paid: 'Payée',
};

const statusClasses = {
    finalized: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    sent: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount || 0);
};

const quarters = [
    { value: null, label: 'Année complète' },
    { value: 1, label: 'T1 (Jan-Mar)' },
    { value: 2, label: 'T2 (Avr-Jun)' },
    { value: 3, label: 'T3 (Jul-Sep)' },
    { value: 4, label: 'T4 (Oct-Déc)' },
];

const exports = [
    {
        type: 'faia',
        title: 'Export FAIA (XML)',
        description: 'Format standard pour l\'AED Luxembourg',
        icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    },
    {
        type: 'excel',
        title: 'Export Excel',
        description: 'Factures et avoirs en format tableur',
        icon: 'M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z',
    },
    {
        type: 'pdf_archive',
        title: 'Archive PDF',
        description: 'Toutes les factures en PDF (ZIP)',
        icon: 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4',
    },
    {
        type: 'accounting/generic',
        title: 'Export comptable (CSV)',
        description: 'Format CSV générique pour logiciels comptables',
        icon: 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
    },
    {
        type: 'accounting/sage_bob',
        title: 'Sage BOB 50 (ASCII)',
        description: 'Format d\'import pour Sage BOB 50',
        icon: 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
    },
    {
        type: 'accounting/sage_100',
        title: 'Sage 100 (CSV)',
        description: 'Format d\'import pour Sage 100',
        icon: 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
    },
];

const getExportUrl = (type) => {
    const params = new URLSearchParams({
        year: year.value,
    });
    if (quarter.value) {
        params.append('quarter', quarter.value);
    }

    // Accounting exports use a different route
    if (type.startsWith('accounting/')) {
        const format = type.replace('accounting/', '');
        return route('accountant.accounting-export', { user: props.client.id, format }) + '?' + params.toString();
    }

    return route('accountant.export', { user: props.client.id, type }) + '?' + params.toString();
};
</script>

<template>
    <Head :title="client.name" />

    <AccountantLayout>
        <!-- Header with back button -->
        <div class="mb-6">
            <Link
                :href="route('accountant.dashboard')"
                class="inline-flex items-center text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300"
            >
                <svg class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Retour
            </Link>
            <h1 class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ client.name }}</h1>
            <p v-if="client.vat_number" class="text-sm text-slate-500 dark:text-slate-400">
                TVA: {{ client.vat_number }}
            </p>
        </div>

        <!-- Period selector -->
        <div class="bg-white dark:bg-surface-card rounded-2xl shadow p-6 mb-6">
            <h2 class="text-lg font-medium text-slate-900 dark:text-white mb-4">Période</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="year" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Année
                    </label>
                    <select
                        id="year"
                        v-model="year"
                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                </div>
                <div>
                    <label for="quarter" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        Trimestre
                    </label>
                    <select
                        id="quarter"
                        v-model="quarter"
                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option v-for="q in quarters" :key="q.value" :value="q.value">{{ q.label }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div v-if="summary" class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-surface-card rounded-2xl shadow p-4 text-center">
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ summary.invoices_count }}</p>
                <p class="text-xs text-slate-500">Factures</p>
            </div>
            <div class="bg-white dark:bg-surface-card rounded-2xl shadow p-4 text-center">
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ formatCurrency(summary.total_ht) }}</p>
                <p class="text-xs text-slate-500">CA HT</p>
            </div>
            <div class="bg-white dark:bg-surface-card rounded-2xl shadow p-4 text-center">
                <p class="text-2xl font-bold text-emerald-600">{{ summary.paid_count }}</p>
                <p class="text-xs text-slate-500">Payées</p>
            </div>
            <div class="bg-white dark:bg-surface-card rounded-2xl shadow p-4 text-center">
                <p class="text-2xl font-bold" :class="summary.unpaid_count > 0 ? 'text-amber-600' : 'text-slate-900 dark:text-white'">{{ summary.unpaid_count }}</p>
                <p class="text-xs text-slate-500">En attente</p>
            </div>
        </div>

        <!-- Invoices list -->
        <div class="bg-white dark:bg-surface-card rounded-2xl shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-lg font-medium text-slate-900 dark:text-white">Factures</h2>
                <span class="text-sm text-slate-500">{{ invoices.length }} document(s)</span>
            </div>
            <div v-if="invoices.length === 0" class="px-6 py-12 text-center text-sm text-slate-500">
                Aucune facture pour cette période.
            </div>
            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-gray-800">
                        <tr>
                            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 dark:text-white sm:pl-6">N°</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white">Client</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white">Date</th>
                            <th class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900 dark:text-white">HT</th>
                            <th class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900 dark:text-white">TTC</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white">Statut</th>
                            <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">PDF</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-surface-card">
                        <tr v-for="inv in invoices" :key="inv.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium sm:pl-6">
                                <span :class="inv.type === 'credit_note' ? 'text-pink-600' : 'text-slate-900 dark:text-white'">
                                    {{ inv.type === 'credit_note' ? 'NC-' : '' }}{{ inv.number }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500 dark:text-slate-400">{{ inv.client_name }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500 dark:text-slate-400">{{ inv.finalized_at }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-right font-medium text-slate-900 dark:text-white">{{ formatCurrency(inv.total_ht) }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-right font-semibold text-slate-900 dark:text-white">{{ formatCurrency(inv.total_ttc) }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm">
                                <span :class="[statusClasses[inv.status], 'inline-flex items-center rounded-xl px-2.5 py-0.5 text-xs font-medium']">
                                    {{ statusLabels[inv.status] || inv.status }}
                                </span>
                                <span v-if="inv.paid_at" class="text-xs text-slate-400 ml-1">{{ inv.paid_at }}</span>
                            </td>
                            <td class="whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm sm:pr-6">
                                <a
                                    :href="route('accountant.invoice-pdf', { user: client.id, invoice: inv.id })"
                                    target="_blank"
                                    class="text-slate-400 hover:text-primary-500 transition-colors"
                                    title="Voir le PDF"
                                >
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.64 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.64 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Export options -->
        <div class="bg-white dark:bg-surface-card rounded-2xl shadow p-6 mb-6">
            <h2 class="text-lg font-medium text-slate-900 dark:text-white mb-4">Exports disponibles</h2>
            <div class="space-y-4">
                <div
                    v-for="exp in exports"
                    :key="exp.type"
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-2xl"
                >
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                            <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="exp.icon" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-slate-900 dark:text-white">{{ exp.title }}</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">{{ exp.description }}</p>
                        </div>
                    </div>
                    <a
                        :href="getExportUrl(exp.type)"
                        class="w-full sm:w-auto justify-center inline-flex items-center px-4 py-2 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-500"
                    >
                        <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Télécharger
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent downloads -->
        <div v-if="recentDownloads.length > 0" class="bg-white dark:bg-surface-card rounded-2xl shadow p-6">
            <h2 class="text-lg font-medium text-slate-900 dark:text-white mb-4">Téléchargements récents</h2>
            <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                <li v-for="download in recentDownloads" :key="download.id" class="py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ download.export_type_label }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Période : {{ download.period }}</p>
                    </div>
                    <span class="text-xs text-slate-400">{{ download.downloaded_at }}</span>
                </li>
            </ul>
        </div>
    </AccountantLayout>
</template>
