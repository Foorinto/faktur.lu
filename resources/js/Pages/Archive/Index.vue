<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    stats: Object,
    pendingInvoices: Array,
    recentlyArchived: Array,
    ghostscriptAvailable: Boolean,
    formats: Object,
});

// Selection state
const selectedIds = ref([]);
const selectAll = ref(false);

// Form for batch archive
const batchForm = useForm({
    invoice_ids: [],
    format: 'pdfa-1b',
});

// Toggle selection
const toggleSelect = (id) => {
    const index = selectedIds.value.indexOf(id);
    if (index === -1) {
        selectedIds.value.push(id);
    } else {
        selectedIds.value.splice(index, 1);
    }
};

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedIds.value = props.pendingInvoices.map(inv => inv.id);
    } else {
        selectedIds.value = [];
    }
};

// Archive single invoice
const archiveInvoice = (invoice) => {
    router.post(route('invoices.archive', invoice.id), {
        format: batchForm.format,
    });
};

// Archive selected invoices
const archiveSelected = () => {
    if (selectedIds.value.length === 0) return;

    batchForm.invoice_ids = selectedIds.value;
    batchForm.post(route('archive.batch'), {
        onSuccess: () => {
            selectedIds.value = [];
            selectAll.value = false;
        },
    });
};

// Verify archive integrity
const verifyIntegrity = async (invoice) => {
    try {
        const response = await fetch(route('invoices.archive.verify', invoice.id));
        const result = await response.json();

        if (result.valid) {
            alert(t('integrity_verified'));
        } else {
            alert(t('integrity_error', { error: result.error }));
        }
    } catch (error) {
        alert(t('verification_error'));
    }
};

// Format helpers
const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR');
};

const formatDateTime = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('fr-FR');
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount);
};

// Format label
const getFormatLabel = (format) => {
    const labels = {
        'pdfa-1b': 'PDF/A-1b',
        'pdfa-3b': 'PDF/A-3b',
        'pdf': 'PDF',
    };
    return labels[format] || format;
};

// Progress bar width
const progressWidth = computed(() => {
    return `${props.stats.archive_percentage}%`;
});
</script>

<template>
    <Head :title="t('pdf_a_archiving')" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('archive_title') }}
            </h1>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Ghostscript warning -->
                <div v-if="!ghostscriptAvailable" class="mb-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-amber-800 dark:text-amber-300">
                                {{ t('pdfa_not_available') }}
                            </h3>
                            <p class="mt-1 text-sm text-amber-700 dark:text-amber-400">
                                {{ t('ghostscript_not_installed') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="bg-white dark:bg-slate-800 shadow rounded-2xl p-6 mb-6">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white mb-4">
                        {{ t('global_status') }}
                    </h2>

                    <div class="mb-4">
                        <div class="flex justify-between text-sm text-slate-600 dark:text-slate-400 mb-1">
                            <span>{{ t('documents_archived', { count: stats.total_archived, total: stats.total_finalized }) }}</span>
                            <span>{{ stats.archive_percentage }}%</span>
                        </div>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-3">
                            <div
                                class="bg-violet-600 h-3 rounded-full transition-all duration-500"
                                :style="{ width: progressWidth }"
                            ></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center p-3 bg-slate-50 dark:bg-slate-700 rounded-2xl">
                            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.total_finalized }}</div>
                            <div class="text-sm text-slate-500 dark:text-slate-400">{{ t('total_finalized') }}</div>
                        </div>
                        <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-2xl">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.total_archived }}</div>
                            <div class="text-sm text-green-600 dark:text-green-400">{{ t('archived') }}</div>
                        </div>
                        <div class="text-center p-3 bg-amber-50 dark:bg-amber-900/20 rounded-2xl">
                            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ stats.not_archived }}</div>
                            <div class="text-sm text-amber-600 dark:text-amber-400">{{ t('waiting') }}</div>
                        </div>
                        <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-2xl">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ stats.expiring_this_year }}</div>
                            <div class="text-sm text-blue-600 dark:text-blue-400">{{ t('expire_this_year') }}</div>
                        </div>
                    </div>

                    <!-- Format breakdown -->
                    <div v-if="Object.keys(stats.by_format).length > 0" class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <div class="text-sm text-slate-600 dark:text-slate-400">{{ t('by_format') }}</div>
                        <div class="flex gap-4 mt-2">
                            <span
                                v-for="(count, format) in stats.by_format"
                                :key="format"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-300"
                            >
                                {{ getFormatLabel(format) }}: {{ count }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Pending archives -->
                    <div class="bg-white dark:bg-slate-800 shadow rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-medium text-slate-900 dark:text-white">
                                {{ t('unarchived_documents', { count: pendingInvoices.length }) }}
                            </h2>

                            <select
                                v-model="batchForm.format"
                                class="text-sm rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                            >
                                <option v-for="(label, value) in formats" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                        </div>

                        <div v-if="pendingInvoices.length === 0" class="text-center py-8 text-slate-500 dark:text-slate-400">
                            {{ t('all_documents_archived') }}
                        </div>

                        <div v-else>
                            <div class="flex items-center gap-4 mb-3 pb-3 border-b border-slate-200 dark:border-slate-700">
                                <label class="flex items-center">
                                    <input
                                        v-model="selectAll"
                                        @change="toggleSelectAll"
                                        type="checkbox"
                                        class="h-4 w-4 rounded text-violet-600 focus:ring-violet-500 border-slate-300 dark:border-slate-600"
                                    />
                                    <span class="ml-2 text-sm text-slate-600 dark:text-slate-400">{{ t('select_all') }}</span>
                                </label>

                                <button
                                    v-if="selectedIds.length > 0"
                                    @click="archiveSelected"
                                    :disabled="batchForm.processing"
                                    class="ml-auto px-3 py-1 text-sm bg-violet-600 text-white rounded-2xl hover:bg-violet-700 disabled:opacity-50"
                                >
                                    <span v-if="batchForm.processing">{{ t('archiving') }}</span>
                                    <span v-else>{{ t('archive_selected', { count: selectedIds.length }) }}</span>
                                </button>
                            </div>

                            <div class="space-y-2 max-h-96 overflow-y-auto">
                                <div
                                    v-for="invoice in pendingInvoices"
                                    :key="invoice.id"
                                    class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700 rounded-2xl"
                                >
                                    <div class="flex items-center gap-3">
                                        <input
                                            type="checkbox"
                                            :checked="selectedIds.includes(invoice.id)"
                                            @change="toggleSelect(invoice.id)"
                                            class="h-4 w-4 rounded text-violet-600 focus:ring-violet-500 border-slate-300 dark:border-slate-600"
                                        />
                                        <div>
                                            <div class="text-sm font-medium text-slate-900 dark:text-white">
                                                {{ invoice.number }}
                                            </div>
                                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                                {{ invoice.client?.name }} | {{ formatDate(invoice.issued_at) }}
                                            </div>
                                        </div>
                                    </div>

                                    <button
                                        @click="archiveInvoice(invoice)"
                                        class="p-2 text-violet-600 hover:bg-violet-100 dark:hover:bg-violet-900/30 rounded-2xl"
                                        :title="t('archive_action')"
                                    >
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recently archived -->
                    <div class="bg-white dark:bg-slate-800 shadow rounded-2xl p-6">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white mb-4">
                            {{ t('recently_archived') }}
                        </h2>

                        <div v-if="recentlyArchived.length === 0" class="text-center py-8 text-slate-500 dark:text-slate-400">
                            {{ t('no_archived_documents') }}
                        </div>

                        <div v-else class="space-y-2 max-h-96 overflow-y-auto">
                            <div
                                v-for="invoice in recentlyArchived"
                                :key="invoice.id"
                                class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700 rounded-2xl"
                            >
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-slate-900 dark:text-white">
                                            {{ invoice.number }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-300">
                                            {{ getFormatLabel(invoice.archive_format) }}
                                        </span>
                                    </div>
                                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        <span>{{ t('archived_on', { date: formatDateTime(invoice.archived_at) }) }}</span>
                                        <span class="mx-1">|</span>
                                        <span>{{ t('expires_on', { date: formatDate(invoice.archive_expires_at) }) }}</span>
                                    </div>
                                    <div class="mt-1 text-xs text-slate-400 dark:text-slate-500 font-mono truncate">
                                        SHA256: {{ invoice.archive_checksum?.substring(0, 16) }}...
                                    </div>
                                </div>

                                <div class="flex items-center gap-1 ml-4">
                                    <a
                                        :href="route('invoices.archive.download', invoice.id)"
                                        class="p-2 text-violet-600 hover:bg-violet-100 dark:hover:bg-violet-900/30 rounded-2xl"
                                        :title="t('download')"
                                    >
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    </a>
                                    <button
                                        @click="verifyIntegrity(invoice)"
                                        class="p-2 text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-600 rounded-2xl"
                                        :title="t('verify_integrity')"
                                    >
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
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
                                {{ t('luxembourg_archiving') }}
                            </h3>
                            <p class="mt-1 text-sm text-blue-700 dark:text-blue-400">
                                {{ t('archiving_info') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
