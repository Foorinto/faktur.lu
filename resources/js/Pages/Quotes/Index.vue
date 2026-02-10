<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    quotes: Object,
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
const previewQuote = ref(null);

// PDF language selection
const pdfLocale = ref('fr');

const pdfLanguages = [
    { value: 'fr', label: 'Français', flag: '🇫🇷' },
    { value: 'de', label: 'Deutsch', flag: '🇩🇪' },
    { value: 'en', label: 'English', flag: '🇬🇧' },
    { value: 'lb', label: 'Lëtzebuergesch', flag: '🇱🇺' },
];

const pdfUrl = computed(() => {
    if (!previewQuote.value) return '';
    const baseUrl = route('quotes.pdf.stream', previewQuote.value.id);
    return `${baseUrl}?locale=${pdfLocale.value}`;
});

const loadPreview = async () => {
    if (!previewQuote.value) return;
    loadingPreview.value = true;

    try {
        const url = route('quotes.preview-html', previewQuote.value.id) + `?locale=${pdfLocale.value}`;
        const response = await axios.get(url);
        previewHtml.value = response.data.html;
    } catch (error) {
        console.error('Error loading preview:', error);
        previewHtml.value = `<p style="color: red; padding: 20px;">${t('error_loading_preview')}</p>`;
    } finally {
        loadingPreview.value = false;
    }
};

const changePdfLanguage = (locale) => {
    pdfLocale.value = locale;
    if (showPreviewModal.value) {
        loadPreview();
    }
};

const openPreview = async (quote) => {
    previewQuote.value = quote;
    pdfLocale.value = quote.client?.locale || 'fr';
    showPreviewModal.value = true;
    loadPreview();
};

const closePreview = () => {
    showPreviewModal.value = false;
    previewHtml.value = '';
    previewQuote.value = null;
};

const updateFilters = () => {
    router.get(route('quotes.index'), {
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
        draft: 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300',
        sent: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        accepted: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        declined: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        expired: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        converted: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
    };
    return classes[status] || classes.draft;
};

const getStatusLabel = (status) => {
    const labels = {
        draft: t('draft'),
        sent: t('sent'),
        accepted: t('accepted'),
        declined: t('rejected'),
        expired: t('expired'),
        converted: t('converted'),
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

const canEdit = (quote) => {
    return ['draft', 'sent'].includes(quote.status);
};
</script>

<template>
    <Head :title="t('quotes')" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ t('quotes') }}
                </h1>
                <Link
                    :href="route('quotes.create')"
                    class="inline-flex items-center rounded-xl bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
                >
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    {{ t('new_quote') }}
                </Link>
            </div>
        </template>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-4">
            <select
                v-model="statusFilter"
                class="rounded-xl border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-primary-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600 sm:text-sm"
            >
                <option value="">{{ t('all_statuses') }}</option>
                <option v-for="status in statuses" :key="status.value" :value="status.value">
                    {{ status.label }}
                </option>
            </select>

            <select
                v-model="yearFilter"
                class="rounded-xl border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-primary-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600 sm:text-sm"
            >
                <option value="">{{ t('all_years') }}</option>
                <option v-for="year in years" :key="year" :value="year">
                    {{ year }}
                </option>
            </select>

            <select
                v-model="clientFilter"
                class="rounded-xl border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-primary-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600 sm:text-sm"
            >
                <option value="">{{ t('all_clients') }}</option>
                <option v-for="client in clients" :key="client.id" :value="client.id">
                    {{ client.name }}
                </option>
            </select>
        </div>

        <!-- Quotes list -->
        <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                        <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 dark:text-white sm:pl-6">
                            {{ t('reference') }}
                        </th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white">
                            {{ t('client') }}
                        </th>
                        <th class="hidden px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white md:table-cell">
                            {{ t('created_at') }}
                        </th>
                        <th class="hidden px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white lg:table-cell">
                            {{ t('due_date') }}
                        </th>
                        <th class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900 dark:text-white">
                            {{ t('total') }} {{ t('ttc') }}
                        </th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white">
                            {{ t('status') }}
                        </th>
                        <th class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">{{ t('actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-800">
                    <tr v-if="quotes.data.length === 0">
                        <td colspan="7" class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="mt-2">{{ t('no_quotes') }}</p>
                            <Link
                                :href="route('quotes.create')"
                                class="mt-4 inline-flex items-center text-primary-600 hover:text-primary-500 dark:text-primary-400"
                            >
                                {{ t('create_first_quote') }}
                            </Link>
                        </td>
                    </tr>
                    <tr v-for="quote in quotes.data" :key="quote.id" class="hover:bg-slate-50 dark:hover:bg-slate-700">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-6">
                            <Link
                                :href="canEdit(quote) ? route('quotes.edit', quote.id) : route('quotes.show', quote.id)"
                                class="font-medium text-slate-900 hover:text-primary-600 dark:text-white dark:hover:text-primary-400"
                            >
                                {{ quote.reference }}
                            </Link>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500 dark:text-slate-400">
                            {{ quote.client?.name }}
                        </td>
                        <td class="hidden whitespace-nowrap px-3 py-4 text-sm text-slate-500 dark:text-slate-400 md:table-cell">
                            {{ formatDate(quote.created_at) }}
                        </td>
                        <td class="hidden whitespace-nowrap px-3 py-4 text-sm text-slate-500 dark:text-slate-400 lg:table-cell">
                            {{ formatDate(quote.valid_until) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-right text-sm font-medium text-slate-900 dark:text-white">
                            {{ formatCurrency(quote.total_ttc, quote.currency) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <span
                                :class="getStatusBadgeClass(quote.status)"
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                            >
                                {{ getStatusLabel(quote.status) }}
                            </span>
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            <div class="flex items-center justify-end gap-3">
                                <!-- Preview button -->
                                <button
                                    type="button"
                                    @click="openPreview(quote)"
                                    class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300"
                                    :title="t('preview')"
                                >
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.64 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.64 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </button>
                                <!-- Edit/View link -->
                                <Link
                                    v-if="canEdit(quote)"
                                    :href="route('quotes.edit', quote.id)"
                                    class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                                >
                                    {{ t('edit') }}
                                </Link>
                                <Link
                                    v-else
                                    :href="route('quotes.show', quote.id)"
                                    class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                                >
                                    {{ t('view') }}
                                </Link>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="quotes.links && quotes.links.length > 3" class="mt-6 flex items-center justify-between">
            <div class="text-sm text-slate-700 dark:text-slate-400">
                {{ t('showing') }} {{ quotes.from }} {{ t('to') }} {{ quotes.to }} {{ t('of') }} {{ quotes.total }} {{ t('quotes').toLowerCase() }}
            </div>
            <nav class="isolate inline-flex -space-x-px rounded-xl shadow-sm">
                <template v-for="(link, index) in quotes.links" :key="index">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            link.active
                                ? 'z-10 bg-primary-600 text-white'
                                : 'text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:text-slate-300 dark:ring-slate-600 dark:hover:bg-slate-700',
                            'relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20',
                            index === 0 ? 'rounded-l-md' : '',
                            index === quotes.links.length - 1 ? 'rounded-r-md' : '',
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
                <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" @click="closePreview"></div>

                <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-5xl max-h-[90vh] flex flex-col">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h3 class="text-lg font-medium text-slate-900 dark:text-white">
                            {{ previewQuote?.reference }}
                        </h3>
                        <div class="flex items-center space-x-2">
                            <!-- Language selector -->
                            <div class="flex items-center border border-slate-300 dark:border-slate-600 rounded-xl overflow-hidden">
                                <button
                                    v-for="lang in pdfLanguages"
                                    :key="lang.value"
                                    type="button"
                                    @click="changePdfLanguage(lang.value)"
                                    :title="lang.label"
                                    class="px-2 py-1.5 text-base transition-colors"
                                    :class="pdfLocale === lang.value
                                        ? 'bg-primary-100 dark:bg-primary-900'
                                        : 'bg-white dark:bg-slate-700 hover:bg-slate-50 dark:hover:bg-slate-600'"
                                >
                                    {{ lang.flag }}
                                </button>
                            </div>
                            <a
                                :href="pdfUrl"
                                target="_blank"
                                class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300"
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
                                class="text-slate-400 hover:text-slate-500 dark:hover:text-slate-300"
                            >
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal body -->
                    <div class="flex-1 overflow-auto p-6 bg-slate-100 dark:bg-slate-900">
                        <div v-if="loadingPreview" class="flex items-center justify-center h-96">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
                        </div>
                        <div
                            v-else
                            class="bg-white shadow-lg mx-auto"
                            style="width: 210mm; min-height: 297mm; transform: scale(1); transform-origin: top center;"
                            v-html="previewHtml"
                        ></div>
                    </div>

                    <!-- Modal footer -->
                    <div class="flex items-center justify-end px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50">
                        <button
                            type="button"
                            @click="closePreview"
                            class="inline-flex items-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:bg-slate-600 dark:text-white dark:ring-slate-500"
                        >
                            {{ t('close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
