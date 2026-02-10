<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    quote: Object,
});

const processing = ref(false);

// Preview modal state
const showPreviewModal = ref(false);
const previewHtml = ref('');
const loadingPreview = ref(false);

// PDF language selection
const pdfLocale = ref(props.quote.buyer_snapshot?.locale || props.quote.client?.locale || 'fr');

const pdfLanguages = [
    { value: 'fr', label: 'Français', flag: '🇫🇷' },
    { value: 'de', label: 'Deutsch', flag: '🇩🇪' },
    { value: 'en', label: 'English', flag: '🇬🇧' },
    { value: 'lb', label: 'Lëtzebuergesch', flag: '🇱🇺' },
];

const pdfUrl = computed(() => {
    const baseUrl = route('quotes.pdf.stream', props.quote.id);
    return `${baseUrl}?locale=${pdfLocale.value}`;
});

// Load preview with locale
const loadPreview = async () => {
    loadingPreview.value = true;
    try {
        const url = route('quotes.preview-html', props.quote.id) + `?locale=${pdfLocale.value}`;
        const response = await axios.get(url);
        previewHtml.value = response.data.html;
    } catch (error) {
        console.error('Error loading preview:', error);
        previewHtml.value = `<p style="color: red; padding: 20px;">${t('error_loading_preview')}</p>`;
    } finally {
        loadingPreview.value = false;
    }
};

// Reload preview when language changes
const changePdfLanguage = (locale) => {
    pdfLocale.value = locale;
    if (showPreviewModal.value) {
        loadPreview();
    }
};

const openPreview = () => {
    showPreviewModal.value = true;
    loadPreview();
};

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

const statusLabels = computed(() => ({
    draft: t('draft'),
    sent: t('sent'),
    accepted: t('accepted'),
    declined: t('rejected'),
    expired: t('expired'),
    converted: t('converted'),
}));

const getStatusLabel = (status) => {
    return statusLabels.value[status] || status;
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

const markAsAccepted = () => {
    if (processing.value) return;
    processing.value = true;
    router.post(route('quotes.mark-accepted', props.quote.id), {}, {
        preserveScroll: true,
        onFinish: () => processing.value = false,
    });
};

const markAsDeclined = () => {
    if (processing.value) return;
    processing.value = true;
    router.post(route('quotes.mark-declined', props.quote.id), {}, {
        preserveScroll: true,
        onFinish: () => processing.value = false,
    });
};

const convertToInvoice = () => {
    if (processing.value) return;
    if (!confirm(t('convert_quote_confirm'))) {
        return;
    }
    processing.value = true;
    router.post(route('quotes.convert', props.quote.id), {}, {
        preserveScroll: true,
        onFinish: () => processing.value = false,
    });
};
</script>

<template>
    <Head :title="`Devis ${quote.reference}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('quotes.index')"
                        class="text-slate-400 hover:text-slate-500 dark:text-slate-500 dark:hover:text-slate-400"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                        </svg>
                    </Link>
                    <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                        {{ quote.reference }}
                    </h1>
                    <span
                        :class="getStatusBadgeClass(quote.status)"
                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                    >
                        {{ getStatusLabel(quote.status) }}
                    </span>
                </div>

                <div class="flex items-center space-x-3">
                    <!-- Preview Button -->
                    <button
                        type="button"
                        @click="openPreview"
                        class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600"
                    >
                        <svg class="h-4 w-4 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" />
                            <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 010-1.186A10.004 10.004 0 0110 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0110 17c-4.257 0-7.893-2.66-9.336-6.41zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                        </svg>
                        {{ t('preview') }}
                    </button>

                    <!-- Mark as Accepted -->
                    <button
                        v-if="quote.status === 'sent'"
                        @click="markAsAccepted"
                        :disabled="processing"
                        class="inline-flex items-center rounded-xl bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-50"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                        {{ t('accept') }}
                    </button>

                    <!-- Mark as Declined -->
                    <button
                        v-if="quote.status === 'sent'"
                        @click="markAsDeclined"
                        :disabled="processing"
                        class="inline-flex items-center rounded-xl border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50 dark:border-red-600 dark:bg-slate-700 dark:text-red-400 dark:hover:bg-slate-600"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                        {{ t('reject') }}
                    </button>

                    <!-- Convert to Invoice -->
                    <button
                        v-if="quote.status === 'accepted'"
                        @click="convertToInvoice"
                        :disabled="processing"
                        class="inline-flex items-center rounded-xl bg-purple-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 disabled:opacity-50"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M3 4a2 2 0 00-2 2v1.161l8.441 4.221a1.25 1.25 0 001.118 0L19 7.162V6a2 2 0 00-2-2H3z" />
                            <path d="M19 8.839l-7.77 3.885a2.75 2.75 0 01-2.46 0L1 8.839V14a2 2 0 002 2h14a2 2 0 002-2V8.839z" />
                        </svg>
                        {{ t('convert_to_invoice') }}
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Quote Header Info -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Seller Info -->
                <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('issuer') }}</h2>
                    </div>
                    <div class="px-6 py-4">
                        <div v-if="quote.seller_snapshot" class="text-sm text-slate-700 dark:text-slate-300 space-y-1">
                            <p class="font-semibold">{{ quote.seller_snapshot.company_name }}</p>
                            <p v-if="quote.seller_snapshot.legal_name">{{ quote.seller_snapshot.legal_name }}</p>
                            <p>{{ quote.seller_snapshot.address }}</p>
                            <p>{{ quote.seller_snapshot.postal_code }} {{ quote.seller_snapshot.city }}</p>
                            <p>{{ quote.seller_snapshot.country }}</p>
                            <p class="pt-2" v-if="quote.seller_snapshot.matricule">
                                <span class="text-slate-500">{{ t('matricule') }}:</span> {{ quote.seller_snapshot.matricule }}
                            </p>
                            <p v-if="quote.seller_snapshot.vat_number">
                                <span class="text-slate-500">{{ t('vat_number_short') }}:</span> {{ quote.seller_snapshot.vat_number }}
                            </p>
                        </div>
                        <div v-else class="text-sm text-slate-500 dark:text-slate-400">
                            {{ t('seller_info_not_recorded') }}
                        </div>
                    </div>
                </div>

                <!-- Buyer Info -->
                <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('client') }}</h2>
                    </div>
                    <div class="px-6 py-4">
                        <div v-if="quote.buyer_snapshot" class="text-sm text-slate-700 dark:text-slate-300 space-y-1">
                            <p class="font-semibold">{{ quote.buyer_snapshot.company_name || quote.buyer_snapshot.name }}</p>
                            <p v-if="quote.buyer_snapshot.contact_name">{{ quote.buyer_snapshot.contact_name }}</p>
                            <p>{{ quote.buyer_snapshot.address }}</p>
                            <p>{{ quote.buyer_snapshot.postal_code }} {{ quote.buyer_snapshot.city }}</p>
                            <p>{{ quote.buyer_snapshot.country }}</p>
                            <p v-if="quote.buyer_snapshot.vat_number" class="pt-2">
                                <span class="text-slate-500">{{ t('vat_number_short') }}:</span> {{ quote.buyer_snapshot.vat_number }}
                            </p>
                        </div>
                        <div v-else-if="quote.client" class="text-sm text-slate-700 dark:text-slate-300 space-y-1">
                            <p class="font-semibold">{{ quote.client.name }}</p>
                            <p v-if="quote.client.contact_name">{{ quote.client.contact_name }}</p>
                            <p>{{ quote.client.address }}</p>
                            <p>{{ quote.client.postal_code }} {{ quote.client.city }}</p>
                            <p>{{ quote.client.country }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quote Details -->
            <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('details') }}</h2>
                </div>
                <div class="px-6 py-4">
                    <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div>
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('created_at') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ formatDate(quote.created_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('valid_until') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ formatDate(quote.valid_until) }}</dd>
                        </div>
                        <div v-if="quote.sent_at">
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('sent_on') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ formatDate(quote.sent_at) }}</dd>
                        </div>
                        <div v-if="quote.accepted_at">
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('accepted_on') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ formatDate(quote.accepted_at) }}</dd>
                        </div>
                        <div v-if="quote.declined_at">
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('rejected_on') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ formatDate(quote.declined_at) }}</dd>
                        </div>
                        <div v-if="quote.converted_to_invoice_id">
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('converted_to_invoice') }}</dt>
                            <dd class="mt-1 text-sm">
                                <Link
                                    :href="route('invoices.show', quote.converted_to_invoice_id)"
                                    class="text-primary-600 hover:text-primary-500 dark:text-primary-400"
                                >
                                    {{ t('see_invoice') }}
                                </Link>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Quote Items -->
            <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('quote_lines') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-700">
                            <tr>
                                <th class="py-3.5 pl-6 pr-3 text-left text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ t('description') }}
                                </th>
                                <th class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ t('qty') }}
                                </th>
                                <th class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ t('price_ht') }}
                                </th>
                                <th class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ t('vat') }}
                                </th>
                                <th class="py-3.5 pl-3 pr-6 text-right text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ t('total_ht') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-800">
                            <tr v-for="item in quote.items" :key="item.id">
                                <td class="py-4 pl-6 pr-3 text-sm">
                                    <div class="font-medium text-slate-900 dark:text-white">{{ item.title }}</div>
                                    <div v-if="item.description" class="text-slate-500 dark:text-slate-400 whitespace-pre-line">{{ item.description }}</div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-slate-500 dark:text-slate-400">
                                    {{ item.quantity }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-slate-500 dark:text-slate-400">
                                    {{ formatCurrency(item.unit_price, quote.currency) }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-slate-500 dark:text-slate-400">
                                    {{ item.vat_rate }}%
                                </td>
                                <td class="whitespace-nowrap py-4 pl-3 pr-6 text-right text-sm font-medium text-slate-900 dark:text-white">
                                    {{ formatCurrency(item.total_ht, quote.currency) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-slate-50 dark:bg-slate-700">
                            <tr>
                                <td colspan="4" class="py-3 pl-6 pr-3 text-right text-sm font-medium text-slate-500 dark:text-slate-400">
                                    {{ t('total_ht') }}
                                </td>
                                <td class="whitespace-nowrap py-3 pl-3 pr-6 text-right text-sm font-medium text-slate-900 dark:text-white">
                                    {{ formatCurrency(quote.total_ht, quote.currency) }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="4" class="py-3 pl-6 pr-3 text-right text-sm font-medium text-slate-500 dark:text-slate-400">
                                    {{ t('total_vat') }}
                                </td>
                                <td class="whitespace-nowrap py-3 pl-3 pr-6 text-right text-sm font-medium text-slate-900 dark:text-white">
                                    {{ formatCurrency(quote.total_vat, quote.currency) }}
                                </td>
                            </tr>
                            <tr class="border-t-2 border-slate-300 dark:border-slate-600">
                                <td colspan="4" class="py-3 pl-6 pr-3 text-right text-sm font-bold text-slate-900 dark:text-white">
                                    {{ t('total_ttc') }}
                                </td>
                                <td class="whitespace-nowrap py-3 pl-3 pr-6 text-right text-sm font-bold text-slate-900 dark:text-white">
                                    {{ formatCurrency(quote.total_ttc, quote.currency) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Notes -->
            <div v-if="quote.notes" class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('notes') }}</h2>
                </div>
                <div class="px-6 py-4">
                    <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap">{{ quote.notes }}</p>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <div v-if="showPreviewModal" class="fixed inset-0 z-50 overflow-hidden">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" @click="showPreviewModal = false"></div>

                <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-5xl max-h-[90vh] flex flex-col">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h3 class="text-lg font-medium text-slate-900 dark:text-white">
                            {{ quote.reference }}
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
                                @click="showPreviewModal = false"
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
                            @click="showPreviewModal = false"
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
