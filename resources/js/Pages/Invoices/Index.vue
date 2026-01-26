<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    invoices: Object,
    filters: Object,
    statuses: Array,
    years: Array,
    clients: Array,
});

const statusFilter = ref(props.filters.status || '');
const yearFilter = ref(props.filters.year || '');
const clientFilter = ref(props.filters.client_id || '');

// Preview modal state
const showPreviewModal = ref(false);
const previewHtml = ref('');
const loadingPreview = ref(false);
const previewInvoice = ref(null);

const openPreview = async (invoice) => {
    previewInvoice.value = invoice;
    showPreviewModal.value = true;
    loadingPreview.value = true;

    try {
        if (invoice.status === 'draft') {
            const response = await axios.get(route('invoices.preview-draft', invoice.id));
            previewHtml.value = response.data.html;
        } else {
            // For finalized invoices, use the preview-html endpoint
            const response = await axios.get(route('invoices.preview-html', invoice.id));
            previewHtml.value = response.data.html;
        }
    } catch (error) {
        console.error('Error loading preview:', error);
        previewHtml.value = '<p style="color: red; padding: 20px;">Erreur lors du chargement de l\'aperçu</p>';
    } finally {
        loadingPreview.value = false;
    }
};

const closePreview = () => {
    showPreviewModal.value = false;
    previewHtml.value = '';
    previewInvoice.value = null;
};

const updateFilters = () => {
    router.get(route('invoices.index'), {
        status: statusFilter.value || undefined,
        year: yearFilter.value || undefined,
        client_id: clientFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

watch([statusFilter, yearFilter, clientFilter], updateFilters);

const getStatusBadgeClass = (status) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        finalized: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        sent: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        paid: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    };
    return classes[status] || classes.draft;
};

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Brouillon',
        finalized: 'Finalisée',
        sent: 'Envoyée',
        paid: 'Payée',
        cancelled: 'Annulée',
    };
    return labels[status] || status;
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
</script>

<template>
    <Head title="Factures" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Factures
                </h1>
                <Link
                    :href="route('invoices.create')"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    Nouvelle facture
                </Link>
            </div>
        </template>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-4">
            <select
                v-model="statusFilter"
                class="rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 dark:bg-gray-800 dark:text-white dark:ring-gray-600 sm:text-sm"
            >
                <option value="">Tous les statuts</option>
                <option v-for="status in statuses" :key="status.value" :value="status.value">
                    {{ status.label }}
                </option>
            </select>

            <select
                v-model="yearFilter"
                class="rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 dark:bg-gray-800 dark:text-white dark:ring-gray-600 sm:text-sm"
            >
                <option value="">Toutes les années</option>
                <option v-for="year in years" :key="year" :value="year">
                    {{ year }}
                </option>
            </select>

            <select
                v-model="clientFilter"
                class="rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 dark:bg-gray-800 dark:text-white dark:ring-gray-600 sm:text-sm"
            >
                <option value="">Tous les clients</option>
                <option v-for="client in clients" :key="client.id" :value="client.id">
                    {{ client.name }}
                </option>
            </select>
        </div>

        <!-- Invoices list -->
        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-white sm:pl-6">
                            N°
                        </th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                            Client
                        </th>
                        <th class="hidden px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white md:table-cell">
                            Date
                        </th>
                        <th class="hidden px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white lg:table-cell">
                            Échéance
                        </th>
                        <th class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-white">
                            Total TTC
                        </th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">
                            Statut
                        </th>
                        <th class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                    <tr v-if="invoices.data.length === 0">
                        <td colspan="7" class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="mt-2">Aucune facture trouvée.</p>
                            <Link
                                :href="route('invoices.create')"
                                class="mt-4 inline-flex items-center text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                            >
                                Créer votre première facture
                            </Link>
                        </td>
                    </tr>
                    <tr v-for="invoice in invoices.data" :key="invoice.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-6">
                            <Link
                                :href="invoice.status === 'draft' ? route('invoices.edit', invoice.id) : route('invoices.show', invoice.id)"
                                class="font-medium text-gray-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400"
                            >
                                <span v-if="invoice.type === 'credit_note'" class="text-red-600 dark:text-red-400">
                                    NC-
                                </span>
                                {{ invoice.number || 'BROUILLON' }}
                            </Link>
                            <p v-if="invoice.title" class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[200px]">
                                {{ invoice.title }}
                            </p>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ invoice.client?.name }}
                        </td>
                        <td class="hidden whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400 md:table-cell">
                            {{ formatDate(invoice.issued_at) }}
                        </td>
                        <td class="hidden whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400 lg:table-cell">
                            {{ formatDate(invoice.due_at) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-right text-sm font-medium"
                            :class="invoice.type === 'credit_note' ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'"
                        >
                            {{ formatCurrency(invoice.total_ttc, invoice.currency) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <span
                                :class="getStatusBadgeClass(invoice.status)"
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                            >
                                {{ getStatusLabel(invoice.status) }}
                            </span>
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            <div class="flex items-center justify-end gap-3">
                                <!-- Preview button -->
                                <button
                                    type="button"
                                    @click="openPreview(invoice)"
                                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                                    title="Aperçu"
                                >
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.64 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.64 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </button>
                                <!-- Edit/View link -->
                                <Link
                                    v-if="invoice.status === 'draft'"
                                    :href="route('invoices.edit', invoice.id)"
                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                >
                                    Modifier
                                </Link>
                                <Link
                                    v-else
                                    :href="route('invoices.show', invoice.id)"
                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                >
                                    Voir
                                </Link>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="invoices.links && invoices.links.length > 3" class="mt-6 flex items-center justify-between">
            <div class="text-sm text-gray-700 dark:text-gray-400">
                Affichage de {{ invoices.from }} à {{ invoices.to }} sur {{ invoices.total }} factures
            </div>
            <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm">
                <template v-for="(link, index) in invoices.links" :key="index">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            link.active
                                ? 'z-10 bg-indigo-600 text-white'
                                : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:text-gray-300 dark:ring-gray-600 dark:hover:bg-gray-700',
                            'relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20',
                            index === 0 ? 'rounded-l-md' : '',
                            index === invoices.links.length - 1 ? 'rounded-r-md' : '',
                        ]"
                        v-html="link.label"
                        preserve-scroll
                    />
                </template>
            </nav>
        </div>

        <!-- Preview Modal -->
        <div v-if="showPreviewModal" class="fixed inset-0 z-50 overflow-hidden">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closePreview"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-5xl max-h-[90vh] flex flex-col">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            Aperçu - {{ previewInvoice?.number || 'Brouillon' }}
                        </h3>
                        <div class="flex items-center space-x-2">
                            <a
                                :href="previewInvoice?.status === 'draft' ? route('invoices.draft-pdf', previewInvoice?.id) : route('invoices.pdf.stream', previewInvoice?.id)"
                                target="_blank"
                                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
                            >
                                <svg class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z" />
                                    <path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" />
                                </svg>
                                PDF
                            </a>
                            <button
                                type="button"
                                @click="closePreview"
                                class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                            >
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal body -->
                    <div class="flex-1 overflow-auto p-6 bg-gray-100 dark:bg-gray-900">
                        <div v-if="loadingPreview" class="flex items-center justify-center h-96">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
                        </div>
                        <div
                            v-else
                            class="bg-white shadow-lg mx-auto"
                            style="width: 210mm; min-height: 297mm; transform-origin: top center;"
                            v-html="previewHtml"
                        ></div>
                    </div>

                    <!-- Modal footer -->
                    <div class="flex items-center justify-end px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <button
                            type="button"
                            @click="closePreview"
                            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-600 dark:text-white dark:ring-gray-500"
                        >
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
