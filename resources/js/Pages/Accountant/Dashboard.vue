<script setup>
import AccountantLayout from '@/Layouts/AccountantLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    accountant: Object,
    clients: Array,
    filters: Object,
    years: Array,
    formats: Array,
});

// Reactive filter state
const selectedYear = ref(props.filters.year || new Date().getFullYear());
const selectedQuarter = ref(props.filters.quarter || null);
const searchQuery = ref(props.filters.search || '');
const selectedFormat = ref('faia');

// Selection state
const selectedClientIds = ref([]);
const selectAll = ref(false);

// Loading states
const massExportLoading = ref(false);
const consolidatedLoading = ref(false);

const quarters = [
    { value: null, label: t('full_year') },
    { value: 1, label: 'T1 (Jan-Mar)' },
    { value: 2, label: 'T2 (Avr-Jun)' },
    { value: 3, label: 'T3 (Jul-Sep)' },
    { value: 4, label: 'T4 (Oct-Déc)' },
];

// Client-side search for instant feedback
const filteredClients = computed(() => {
    if (!searchQuery.value) return props.clients;
    const q = searchQuery.value.toLowerCase();
    return props.clients.filter(c =>
        c.name.toLowerCase().includes(q)
        || (c.vat_number && c.vat_number.toLowerCase().includes(q))
    );
});

// Toggle all checkboxes
const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedClientIds.value = filteredClients.value.map(c => c.id);
    } else {
        selectedClientIds.value = [];
    }
};

// Sync selectAll checkbox state
watch(selectedClientIds, (ids) => {
    selectAll.value = ids.length === filteredClients.value.length && ids.length > 0;
});

// Reload data when period changes (server-side aggregation)
const reloadWithFilters = () => {
    selectedClientIds.value = [];
    selectAll.value = false;
    router.get(route('accountant.dashboard'), {
        year: selectedYear.value,
        quarter: selectedQuarter.value || undefined,
        search: searchQuery.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

let searchTimeout;
const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        reloadWithFilters();
    }, 300);
};

watch([selectedYear, selectedQuarter], reloadWithFilters);

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount || 0);
};

// Totals
const totals = computed(() => {
    const clients = filteredClients.value;
    return {
        ca_ht: clients.reduce((sum, c) => sum + (c.ca_ht || 0), 0),
        invoices_count: clients.reduce((sum, c) => sum + (c.invoices_count || 0), 0),
        credit_notes_count: clients.reduce((sum, c) => sum + (c.credit_notes_count || 0), 0),
    };
});

// Mass export via hidden form (POST returns a file, not Inertia response)
const submitMassExport = () => {
    if (selectedClientIds.value.length === 0) return;
    massExportLoading.value = true;
    submitHiddenForm(route('accountant.mass-export'), {
        'client_ids[]': selectedClientIds.value,
        format: selectedFormat.value,
        year: selectedYear.value,
        quarter: selectedQuarter.value || '',
    });
    setTimeout(() => { massExportLoading.value = false; }, 3000);
};

const submitConsolidatedReport = () => {
    if (selectedClientIds.value.length === 0) return;
    consolidatedLoading.value = true;
    submitHiddenForm(route('accountant.consolidated-report'), {
        'client_ids[]': selectedClientIds.value,
        year: selectedYear.value,
        quarter: selectedQuarter.value || '',
    });
    setTimeout(() => { consolidatedLoading.value = false; }, 3000);
};

const submitHiddenForm = (action, data) => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = action;
    form.style.display = 'none';

    const csrfInput = document.createElement('input');
    csrfInput.name = '_token';
    csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
    form.appendChild(csrfInput);

    Object.entries(data).forEach(([name, value]) => {
        if (Array.isArray(value)) {
            value.forEach(v => {
                const input = document.createElement('input');
                input.name = name;
                input.value = v;
                form.appendChild(input);
            });
        } else if (value !== '' && value !== null && value !== undefined) {
            const input = document.createElement('input');
            input.name = name;
            input.value = value;
            form.appendChild(input);
        }
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

// Single client export
const exportSingleClient = (clientId) => {
    const params = new URLSearchParams({ year: selectedYear.value });
    if (selectedQuarter.value) {
        params.append('quarter', selectedQuarter.value);
    }

    const format = selectedFormat.value;
    if (format.startsWith('accounting_')) {
        const accountingFormat = format.replace('accounting_', '');
        window.location.href = route('accountant.accounting-export', { user: clientId, format: accountingFormat }) + '?' + params.toString();
    } else {
        window.location.href = route('accountant.export', { user: clientId, type: format }) + '?' + params.toString();
    }
};
</script>

<template>
    <Head :title="t('my_clients')" />

    <AccountantLayout :accountant="accountant">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ t('my_clients') }}</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ t('manage_export_clients') }}
            </p>
        </div>

        <!-- Toolbar -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow p-4 mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">{{ t('year') }}</label>
                    <select v-model="selectedYear"
                        class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">{{ t('quarter') }}</label>
                    <select v-model="selectedQuarter"
                        class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                        <option v-for="q in quarters" :key="q.value" :value="q.value">{{ q.label }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">{{ t('format') }}</label>
                    <select v-model="selectedFormat"
                        class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                        <option v-for="f in formats" :key="f.value" :value="f.value">{{ f.label }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">{{ t('search_client') }}</label>
                    <input v-model="searchQuery" @input="onSearchInput" type="text" :placeholder="t('search_client') + '...'"
                        class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm" />
                </div>
            </div>
        </div>

        <!-- Action bar -->
        <div v-if="selectedClientIds.length > 0"
            class="bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800 rounded-2xl p-4 mb-4 flex flex-wrap items-center justify-between gap-3">
            <span class="text-sm font-medium text-violet-700 dark:text-violet-300">
                {{ selectedClientIds.length }} {{ t('clients_selected') }}
            </span>
            <div class="flex gap-3">
                <button @click="submitMassExport" :disabled="massExportLoading"
                    class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium text-white bg-violet-600 hover:bg-violet-500 disabled:opacity-50 transition-colors">
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    {{ massExportLoading ? t('exporting') + '...' : t('export_selection') }}
                </button>
                <button @click="submitConsolidatedReport" :disabled="consolidatedLoading"
                    class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium text-violet-700 bg-white border border-violet-300 hover:bg-violet-50 dark:bg-slate-800 dark:text-violet-300 dark:border-violet-600 dark:hover:bg-slate-700 disabled:opacity-50 transition-colors">
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    {{ consolidatedLoading ? t('generating_report') + '...' : t('consolidated_report') }}
                </button>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="clients.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-white">{{ t('no_clients_yet') }}</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ t('no_clients_yet_description') }}
            </p>
        </div>

        <!-- Client table -->
        <div v-else class="overflow-hidden rounded-2xl bg-white shadow border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="py-3.5 pl-4 pr-2 w-10">
                            <input type="checkbox" v-model="selectAll" @change="toggleSelectAll"
                                class="rounded border-slate-300 text-violet-600 focus:ring-violet-500 dark:border-slate-500 dark:bg-slate-600" />
                        </th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white">Client</th>
                        <th class="hidden lg:table-cell px-3 py-3.5 text-right text-sm font-semibold text-slate-900 dark:text-white">{{ t('ca_ht') }}</th>
                        <th class="px-3 py-3.5 text-center text-sm font-semibold text-slate-900 dark:text-white">{{ t('invoices') }}</th>
                        <th class="hidden md:table-cell px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white">{{ t('last_export') }}</th>
                        <th class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    <tr v-for="client in filteredClients" :key="client.id"
                        class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="py-4 pl-4 pr-2">
                            <input type="checkbox" :value="client.id" v-model="selectedClientIds"
                                class="rounded border-slate-300 text-violet-600 focus:ring-violet-500 dark:border-slate-500 dark:bg-slate-600" />
                        </td>
                        <td class="px-3 py-4">
                            <Link :href="route('accountant.client', client.id)"
                                class="font-medium text-slate-900 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                {{ client.name }}
                            </Link>
                            <p v-if="client.vat_number" class="text-xs text-slate-500 dark:text-slate-400">{{ client.vat_number }}</p>
                        </td>
                        <td class="hidden lg:table-cell px-3 py-4 text-right text-sm text-slate-700 dark:text-slate-300">
                            {{ formatCurrency(client.ca_ht) }}
                        </td>
                        <td class="px-3 py-4 text-center text-sm text-slate-700 dark:text-slate-300">
                            {{ client.invoices_count }}
                            <span v-if="client.credit_notes_count > 0" class="text-xs text-pink-500 ml-1">
                                ({{ client.credit_notes_count }} {{ t('credit_notes_short') }})
                            </span>
                        </td>
                        <td class="hidden md:table-cell px-3 py-4 text-sm text-slate-500 dark:text-slate-400">
                            {{ client.last_export_at || '-' }}
                        </td>
                        <td class="py-4 pl-3 pr-4 text-right sm:pr-6">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="exportSingleClient(client.id)"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors"
                                    :title="t('export_selection')">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                </button>
                                <Link :href="route('accountant.client', client.id)"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors"
                                    :title="t('details')">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </Link>
                            </div>
                        </td>
                    </tr>
                </tbody>
                <!-- Footer totals -->
                <tfoot v-if="filteredClients.length > 1" class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <td class="py-3 pl-4 pr-2"></td>
                        <td class="px-3 py-3 text-sm font-semibold text-slate-900 dark:text-white">
                            Total ({{ filteredClients.length }} clients)
                        </td>
                        <td class="hidden lg:table-cell px-3 py-3 text-right text-sm font-semibold text-slate-900 dark:text-white">
                            {{ formatCurrency(totals.ca_ht) }}
                        </td>
                        <td class="px-3 py-3 text-center text-sm font-semibold text-slate-900 dark:text-white">
                            {{ totals.invoices_count }}
                            <span v-if="totals.credit_notes_count > 0" class="text-xs text-pink-500 ml-1">
                                ({{ totals.credit_notes_count }} {{ t('credit_notes_short') }})
                            </span>
                        </td>
                        <td class="hidden md:table-cell px-3 py-3"></td>
                        <td class="py-3 pl-3 pr-4 sm:pr-6"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </AccountantLayout>
</template>
