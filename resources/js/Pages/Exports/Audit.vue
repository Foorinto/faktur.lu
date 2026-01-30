<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    exports: Array,
    years: Array,
    formats: Object,
    defaultYear: Number,
});

// Form state
const form = useForm({
    period_start: `${props.defaultYear}-01-01`,
    period_end: `${props.defaultYear}-12-31`,
    format: 'csv',
    include_credit_notes: true,
    anonymize: false,
});

// Preview state
const preview = ref(null);
const previewLoading = ref(false);
const previewError = ref(null);

// Fetch preview when period changes
const fetchPreview = async () => {
    previewLoading.value = true;
    previewError.value = null;

    try {
        const response = await fetch(route('exports.audit.preview', {
            period_start: form.period_start,
            period_end: form.period_end,
            include_credit_notes: form.include_credit_notes,
        }));

        if (!response.ok) {
            throw new Error('Erreur lors du chargement de l\'aperçu');
        }

        preview.value = await response.json();
    } catch (error) {
        previewError.value = error.message;
        preview.value = null;
    } finally {
        previewLoading.value = false;
    }
};

// Watch for period changes
watch([() => form.period_start, () => form.period_end, () => form.include_credit_notes], () => {
    if (form.period_start && form.period_end) {
        fetchPreview();
    }
}, { immediate: true });

// Quick period selection
const setYear = (year) => {
    form.period_start = `${year}-01-01`;
    form.period_end = `${year}-12-31`;
};

const setQuarter = (year, quarter) => {
    const startMonth = (quarter - 1) * 3 + 1;
    const endMonth = startMonth + 2;
    const endDay = new Date(year, endMonth, 0).getDate();
    form.period_start = `${year}-${String(startMonth).padStart(2, '0')}-01`;
    form.period_end = `${year}-${String(endMonth).padStart(2, '0')}-${endDay}`;
};

const setSemester = (year, semester) => {
    if (semester === 1) {
        form.period_start = `${year}-01-01`;
        form.period_end = `${year}-06-30`;
    } else {
        form.period_start = `${year}-07-01`;
        form.period_end = `${year}-12-31`;
    }
};

// Format helpers
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR');
};

const formatDateTime = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('fr-FR');
};

// Submit form
const submit = () => {
    form.post(route('exports.audit.store'));
};

// Download export
const downloadExport = (exportItem) => {
    window.location.href = route('exports.audit.download', exportItem.id);
};

// Delete export
const deleteExport = (exportItem) => {
    if (confirm('Supprimer cet export ?')) {
        router.delete(route('exports.audit.destroy', exportItem.id));
    }
};

// Status badge
const getStatusBadge = (status) => {
    switch (status) {
        case 'completed':
            return { class: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300', text: 'Terminé' };
        case 'processing':
            return { class: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300', text: 'En cours' };
        case 'failed':
            return { class: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300', text: 'Échec' };
        default:
            return { class: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300', text: 'En attente' };
    }
};
</script>

<template>
    <Head title="Export Audit FAIA" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                Export Audit FAIA
            </h1>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Export Form -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            Nouvel export
                        </h2>

                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Period Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Période
                                </label>

                                <!-- Quick select buttons -->
                                <div class="flex flex-wrap gap-2 mb-3">
                                    <button
                                        v-for="year in years"
                                        :key="year"
                                        type="button"
                                        @click="setYear(year)"
                                        class="px-3 py-1 text-sm rounded-full border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300"
                                    >
                                        {{ year }}
                                    </button>
                                </div>

                                <div class="flex flex-wrap gap-2 mb-3">
                                    <button
                                        v-for="q in [1, 2, 3, 4]"
                                        :key="`q${q}`"
                                        type="button"
                                        @click="setQuarter(defaultYear, q)"
                                        class="px-3 py-1 text-sm rounded-full border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300"
                                    >
                                        T{{ q }} {{ defaultYear }}
                                    </button>
                                    <button
                                        v-for="s in [1, 2]"
                                        :key="`s${s}`"
                                        type="button"
                                        @click="setSemester(defaultYear, s)"
                                        class="px-3 py-1 text-sm rounded-full border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300"
                                    >
                                        S{{ s }} {{ defaultYear }}
                                    </button>
                                </div>

                                <!-- Date inputs -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Du</label>
                                        <input
                                            v-model="form.period_start"
                                            type="date"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Au</label>
                                        <input
                                            v-model="form.period_end"
                                            type="date"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Format Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Format
                                </label>
                                <div class="space-y-2">
                                    <label
                                        v-for="(label, value) in formats"
                                        :key="value"
                                        class="flex items-center p-3 border rounded-lg cursor-pointer transition-colors"
                                        :class="form.format === value
                                            ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/20'
                                            : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'"
                                    >
                                        <input
                                            v-model="form.format"
                                            :value="value"
                                            type="radio"
                                            class="h-4 w-4 text-violet-600 focus:ring-violet-500"
                                        />
                                        <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                                            {{ label }}
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <!-- Options -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Options
                                </label>
                                <div class="space-y-3">
                                    <label class="flex items-center">
                                        <input
                                            v-model="form.include_credit_notes"
                                            type="checkbox"
                                            class="h-4 w-4 rounded text-violet-600 focus:ring-violet-500 border-gray-300 dark:border-gray-600"
                                        />
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                            Inclure les avoirs (notes de crédit)
                                        </span>
                                    </label>
                                    <label class="flex items-center">
                                        <input
                                            v-model="form.anonymize"
                                            type="checkbox"
                                            class="h-4 w-4 rounded text-violet-600 focus:ring-violet-500 border-gray-300 dark:border-gray-600"
                                        />
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                            Anonymiser les données clients (test)
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <!-- Preview -->
                            <div v-if="preview || previewLoading || previewError" class="rounded-lg bg-gray-50 dark:bg-gray-700 p-4">
                                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Aperçu</h3>

                                <div v-if="previewLoading" class="flex items-center justify-center py-4">
                                    <svg class="animate-spin h-5 w-5 text-violet-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">Chargement...</span>
                                </div>

                                <div v-else-if="previewError" class="text-sm text-red-600 dark:text-red-400">
                                    {{ previewError }}
                                </div>

                                <div v-else-if="preview" class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Factures</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ preview.invoices_count }}</span>
                                    </div>
                                    <div v-if="form.include_credit_notes" class="flex justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Avoirs</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ preview.credit_notes_count }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm pt-2 border-t border-gray-200 dark:border-gray-600">
                                        <span class="text-gray-600 dark:text-gray-400">Total HT</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(preview.total_ht) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Total TVA</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(preview.total_vat) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Total TTC</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(preview.total_ttc) }}</span>
                                    </div>

                                    <!-- Sequence validation -->
                                    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                                        <div v-if="preview.sequence_valid" class="flex items-center text-sm text-green-600 dark:text-green-400">
                                            <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            Séquençage OK
                                        </div>
                                        <div v-else class="text-sm text-amber-600 dark:text-amber-400">
                                            <div class="flex items-center">
                                                <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                                Trous dans la numérotation
                                            </div>
                                            <ul v-if="preview.sequence_errors?.length" class="mt-1 ml-5 list-disc text-xs">
                                                <li v-for="error in preview.sequence_errors.slice(0, 5)" :key="error">{{ error }}</li>
                                                <li v-if="preview.sequence_errors.length > 5">... et {{ preview.sequence_errors.length - 5 }} autres</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="form.processing || !preview || (preview.invoices_count === 0 && preview.credit_notes_count === 0)"
                                    class="px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="form.processing">Génération...</span>
                                    <span v-else>Générer l'export</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Export History -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            Historique des exports
                        </h2>

                        <div v-if="exports.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            Aucun export réalisé
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="exportItem in exports"
                                :key="exportItem.id"
                                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                            >
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ exportItem.period_label }}
                                        </span>
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                            :class="getStatusBadge(exportItem.status).class"
                                        >
                                            {{ getStatusBadge(exportItem.status).text }}
                                        </span>
                                    </div>
                                    <div class="mt-1 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                        <span>{{ exportItem.format_label }}</span>
                                        <span v-if="exportItem.documents_count">{{ exportItem.documents_count }} docs</span>
                                        <span>{{ formatDateTime(exportItem.created_at) }}</span>
                                    </div>
                                    <div v-if="!exportItem.sequence_valid" class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                        Séquençage incomplet
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 ml-4">
                                    <button
                                        v-if="exportItem.status === 'completed'"
                                        @click="downloadExport(exportItem)"
                                        class="p-2 text-violet-600 hover:bg-violet-100 dark:hover:bg-violet-900/30 rounded-lg"
                                        title="Télécharger"
                                    >
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="deleteExport(exportItem)"
                                        class="p-2 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg"
                                        title="Supprimer"
                                    >
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info box -->
                <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">
                                À propos du FAIA
                            </h3>
                            <p class="mt-1 text-sm text-blue-700 dark:text-blue-400">
                                Le FAIA (Fichier d'Audit Informatisé de l'AED) est un standard exigé par l'Administration des contributions directes
                                du Luxembourg lors des contrôles fiscaux. L'export XML FAIA respecte le format SAF-T luxembourgeois.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
