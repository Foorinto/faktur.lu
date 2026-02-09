<script setup>
import AccountantLayout from '@/Layouts/AccountantLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    client: Object,
    years: Array,
    recentDownloads: Array,
});

const selectedYear = ref(props.years[0] || new Date().getFullYear());
const selectedQuarter = ref(null);

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
];

const getExportUrl = (type) => {
    const params = new URLSearchParams({
        year: selectedYear.value,
    });
    if (selectedQuarter.value) {
        params.append('quarter', selectedQuarter.value);
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
                class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
            >
                <svg class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Retour
            </Link>
            <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ client.name }}</h1>
            <p v-if="client.vat_number" class="text-sm text-gray-500 dark:text-gray-400">
                TVA: {{ client.vat_number }}
            </p>
        </div>

        <!-- Period selector -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Période</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="year" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Année
                    </label>
                    <select
                        id="year"
                        v-model="selectedYear"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                    </select>
                </div>
                <div>
                    <label for="quarter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Trimestre
                    </label>
                    <select
                        id="quarter"
                        v-model="selectedQuarter"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        <option v-for="q in quarters" :key="q.value" :value="q.value">{{ q.label }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Export options -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Exports disponibles</h2>
            <div class="space-y-4">
                <div
                    v-for="exp in exports"
                    :key="exp.type"
                    class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
                >
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="exp.icon" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ exp.title }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ exp.description }}</p>
                        </div>
                    </div>
                    <a
                        :href="getExportUrl(exp.type)"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-500"
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
        <div v-if="recentDownloads.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Téléchargements récents</h2>
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                <li v-for="download in recentDownloads" :key="download.id" class="py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ download.export_type_label }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Période : {{ download.period }}</p>
                    </div>
                    <span class="text-xs text-gray-400">{{ download.downloaded_at }}</span>
                </li>
            </ul>
        </div>
    </AccountantLayout>
</template>
