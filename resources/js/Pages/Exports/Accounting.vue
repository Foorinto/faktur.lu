<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    exports: Array,
    years: Array,
    formats: Object,
    defaultYear: Number,
    accountingSettings: Object,
});

// Export form
const form = useForm({
    period_start: `${props.defaultYear}-01-01`,
    period_end: `${props.defaultYear}-12-31`,
    format: 'generic',
    include_credit_notes: true,
});

// Preview
const preview = ref(null);
const previewLoading = ref(false);

const fetchPreview = async () => {
    previewLoading.value = true;
    try {
        const response = await fetch(route('exports.accounting.preview', {
            period_start: form.period_start,
            period_end: form.period_end,
            include_credit_notes: form.include_credit_notes,
        }));
        if (response.ok) {
            preview.value = await response.json();
        }
    } catch {
        preview.value = null;
    } finally {
        previewLoading.value = false;
    }
};

watch([() => form.period_start, () => form.period_end, () => form.include_credit_notes], () => {
    if (form.period_start && form.period_end) {
        fetchPreview();
    }
}, { immediate: true });

// Quick period selectors
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

// Settings form
const settingsForm = ref({
    sales_account: props.accountingSettings?.sales_account ?? '702000',
    vat_collected_accounts: props.accountingSettings?.vat_collected_accounts ?? { '17': '461100', '14': '461400', '8': '461800', '3': '461300' },
    clients_account: props.accountingSettings?.clients_account ?? '411000',
    bank_account: props.accountingSettings?.bank_account ?? '512000',
    sales_journal: props.accountingSettings?.sales_journal ?? 'VE',
    client_prefix: props.accountingSettings?.client_prefix ?? 'C',
});
const settingsSaving = ref(false);
const settingsSaved = ref(false);
const settingsOpen = ref(false);

const saveSettings = async () => {
    settingsSaving.value = true;
    settingsSaved.value = false;
    try {
        const response = await fetch(route('settings.accounting.update'), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify(settingsForm.value),
        });
        if (response.ok) {
            settingsSaved.value = true;
            setTimeout(() => settingsSaved.value = false, 3000);
        }
    } catch {
        // silent
    } finally {
        settingsSaving.value = false;
    }
};

// Helpers
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount);
};

const formatDateTime = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('fr-FR');
};

const submit = () => {
    form.post(route('exports.accounting.store'));
};

const pdfArchiveLoading = ref(false);
const downloadPdfArchive = () => {
    if (!form.period_start || !form.period_end) return;
    pdfArchiveLoading.value = true;
    const params = new URLSearchParams({
        period_start: form.period_start,
        period_end: form.period_end,
        include_credit_notes: form.include_credit_notes ? '1' : '0',
    });
    window.location.href = route('exports.accounting.pdf-archive') + '?' + params.toString();
    setTimeout(() => pdfArchiveLoading.value = false, 3000);
};

const downloadExport = (exportItem) => {
    window.location.href = route('exports.accounting.download', exportItem.id);
};

const deleteExport = (exportItem) => {
    if (confirm(t('delete_confirm'))) {
        router.delete(route('exports.accounting.destroy', exportItem.id));
    }
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'completed':
            return { class: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300', text: t('completed') };
        case 'processing':
            return { class: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300', text: t('processing') };
        case 'failed':
            return { class: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300', text: t('failed') };
        default:
            return { class: 'bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-300', text: t('pending') };
    }
};
</script>

<template>
    <Head :title="t('accounting_export')" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('accounting_export') }}
            </h1>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Export Form -->
                    <div class="bg-white dark:bg-slate-800 shadow rounded-2xl p-6">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white mb-4">
                            {{ t('new_export') }}
                        </h2>

                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Period -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    {{ t('period') }}
                                </label>
                                <div class="flex flex-wrap gap-2 mb-3">
                                    <button
                                        v-for="year in years"
                                        :key="year"
                                        type="button"
                                        @click="setYear(year)"
                                        class="px-3 py-1 text-sm rounded-full border border-slate-300 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300"
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
                                        class="px-3 py-1 text-sm rounded-full border border-slate-300 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300"
                                    >
                                        T{{ q }} {{ defaultYear }}
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">{{ t('from') }}</label>
                                        <input
                                            v-model="form.period_start"
                                            type="date"
                                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">{{ t('to') }}</label>
                                        <input
                                            v-model="form.period_end"
                                            type="date"
                                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Format -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    {{ t('format') }}
                                </label>
                                <div class="space-y-2">
                                    <label
                                        v-for="(label, value) in formats"
                                        :key="value"
                                        class="flex items-center p-3 border rounded-2xl cursor-pointer transition-colors"
                                        :class="form.format === value
                                            ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/20'
                                            : 'border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700'"
                                    >
                                        <input
                                            v-model="form.format"
                                            :value="value"
                                            type="radio"
                                            class="h-4 w-4 text-violet-600 focus:ring-violet-500"
                                        />
                                        <span class="ml-3 text-sm text-slate-700 dark:text-slate-300">
                                            {{ label }}
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <!-- Options -->
                            <div>
                                <label class="flex items-center">
                                    <input
                                        v-model="form.include_credit_notes"
                                        type="checkbox"
                                        class="h-4 w-4 rounded text-violet-600 focus:ring-violet-500 border-slate-300 dark:border-slate-600"
                                    />
                                    <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">
                                        {{ t('include_credit_notes') }}
                                    </span>
                                </label>
                            </div>

                            <!-- Preview -->
                            <div v-if="preview || previewLoading" class="rounded-2xl bg-slate-50 dark:bg-slate-700 p-4">
                                <h3 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">{{ t('preview') }}</h3>

                                <div v-if="previewLoading" class="flex items-center justify-center py-4">
                                    <svg class="animate-spin h-5 w-5 text-violet-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>

                                <div v-else-if="preview" class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-600 dark:text-slate-400">{{ t('invoices') }}</span>
                                        <span class="font-medium text-slate-900 dark:text-white">{{ preview.invoices_count }}</span>
                                    </div>
                                    <div v-if="form.include_credit_notes" class="flex justify-between text-sm">
                                        <span class="text-slate-600 dark:text-slate-400">{{ t('credit_notes') }}</span>
                                        <span class="font-medium text-slate-900 dark:text-white">{{ preview.credit_notes_count }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm pt-2 border-t border-slate-200 dark:border-slate-600">
                                        <span class="text-slate-600 dark:text-slate-400">{{ t('total_ht') }}</span>
                                        <span class="font-medium text-slate-900 dark:text-white">{{ formatCurrency(preview.total_ht) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-600 dark:text-slate-400">{{ t('total_vat') }}</span>
                                        <span class="font-medium text-slate-900 dark:text-white">{{ formatCurrency(preview.total_vat) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-600 dark:text-slate-400">{{ t('total_ttc') }}</span>
                                        <span class="font-medium text-slate-900 dark:text-white">{{ formatCurrency(preview.total_ttc) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit -->
                            <div class="flex justify-end gap-3">
                                <button
                                    type="button"
                                    @click="downloadPdfArchive"
                                    :disabled="pdfArchiveLoading || !preview || (preview.invoices_count === 0 && preview.credit_notes_count === 0)"
                                    class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-2xl hover:bg-slate-100 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    <span v-if="pdfArchiveLoading">{{ t('generating') }}...</span>
                                    <span v-else>Archive PDF</span>
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing || !preview || (preview.invoices_count === 0 && preview.credit_notes_count === 0)"
                                    class="px-4 py-2 bg-violet-600 text-white rounded-2xl hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="form.processing">{{ t('generating') }}...</span>
                                    <span v-else>{{ t('generate_export') }}</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Right column: History + Settings -->
                    <div class="space-y-6">
                        <!-- Export History -->
                        <div class="bg-white dark:bg-slate-800 shadow rounded-2xl p-6">
                            <h2 class="text-lg font-medium text-slate-900 dark:text-white mb-4">
                                {{ t('export_history') }}
                            </h2>

                            <div v-if="exports.length === 0" class="text-center py-8 text-slate-500 dark:text-slate-400">
                                {{ t('no_exports_yet') }}
                            </div>

                            <div v-else class="space-y-3">
                                <div
                                    v-for="exportItem in exports"
                                    :key="exportItem.id"
                                    class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700 rounded-2xl"
                                >
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-slate-900 dark:text-white">
                                                {{ exportItem.period_label }}
                                            </span>
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                :class="getStatusBadge(exportItem.status).class"
                                            >
                                                {{ getStatusBadge(exportItem.status).text }}
                                            </span>
                                        </div>
                                        <div class="mt-1 flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                                            <span>{{ exportItem.format_label }}</span>
                                            <span v-if="exportItem.documents_count">{{ exportItem.documents_count }} docs</span>
                                            <span>{{ formatDateTime(exportItem.created_at) }}</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 ml-4">
                                        <button
                                            v-if="exportItem.status === 'completed'"
                                            @click="downloadExport(exportItem)"
                                            class="p-2 text-violet-600 hover:bg-violet-100 dark:hover:bg-violet-900/30 rounded-2xl"
                                        >
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="deleteExport(exportItem)"
                                            class="p-2 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-2xl"
                                        >
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Accounting Settings -->
                        <div class="bg-white dark:bg-slate-800 shadow rounded-2xl p-6">
                            <button
                                @click="settingsOpen = !settingsOpen"
                                class="w-full flex items-center justify-between text-lg font-medium text-slate-900 dark:text-white"
                            >
                                {{ t('accounting_settings') }}
                                <svg
                                    class="h-5 w-5 transition-transform"
                                    :class="settingsOpen ? 'rotate-180' : ''"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div v-if="settingsOpen" class="mt-4 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('sales_account') }}</label>
                                    <input
                                        v-model="settingsForm.sales_account"
                                        type="text"
                                        class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                        placeholder="702000"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ t('vat_collected_accounts') }}</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div v-for="rate in ['17', '14', '8', '3']" :key="rate">
                                            <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">TVA {{ rate }}%</label>
                                            <input
                                                v-model="settingsForm.vat_collected_accounts[rate]"
                                                type="text"
                                                class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-violet-500 focus:ring-violet-500 text-sm"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('clients_account') }}</label>
                                        <input
                                            v-model="settingsForm.clients_account"
                                            type="text"
                                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                            placeholder="411000"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('bank_account') }}</label>
                                        <input
                                            v-model="settingsForm.bank_account"
                                            type="text"
                                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                            placeholder="512000"
                                        />
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('sales_journal') }}</label>
                                        <input
                                            v-model="settingsForm.sales_journal"
                                            type="text"
                                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                            placeholder="VE"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('client_prefix') }}</label>
                                        <input
                                            v-model="settingsForm.client_prefix"
                                            type="text"
                                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                            placeholder="C"
                                        />
                                    </div>
                                </div>

                                <div class="flex items-center justify-end gap-3 pt-2">
                                    <span v-if="settingsSaved" class="text-sm text-green-600 dark:text-green-400">
                                        {{ t('saved') }}
                                    </span>
                                    <button
                                        @click="saveSettings"
                                        :disabled="settingsSaving"
                                        class="px-4 py-2 bg-violet-600 text-white text-sm rounded-2xl hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 disabled:opacity-50"
                                    >
                                        {{ settingsSaving ? t('saving') + '...' : t('save') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info box -->
                <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">
                                {{ t('accounting_export') }}
                            </h3>
                            <p class="mt-1 text-sm text-blue-700 dark:text-blue-400">
                                {{ t('accounting_export_description') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
