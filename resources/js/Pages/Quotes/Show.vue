<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    quote: Object,
});

const processing = ref(false);

const getStatusBadgeClass = (status) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
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
        draft: 'Brouillon',
        sent: 'Envoyé',
        accepted: 'Accepté',
        declined: 'Refusé',
        expired: 'Expiré',
        converted: 'Converti',
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
    if (!confirm('Convertir ce devis en facture ? Une nouvelle facture brouillon sera créée avec les mêmes lignes.')) {
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
                        class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                        </svg>
                    </Link>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
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
                    <!-- PDF Download -->
                    <a
                        :href="route('quotes.pdf.stream', quote.id)"
                        target="_blank"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z" />
                            <path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" />
                        </svg>
                        PDF
                    </a>

                    <!-- Mark as Accepted -->
                    <button
                        v-if="quote.status === 'sent'"
                        @click="markAsAccepted"
                        :disabled="processing"
                        class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-50"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                        Accepter
                    </button>

                    <!-- Mark as Declined -->
                    <button
                        v-if="quote.status === 'sent'"
                        @click="markAsDeclined"
                        :disabled="processing"
                        class="inline-flex items-center rounded-md border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50 dark:border-red-600 dark:bg-gray-700 dark:text-red-400 dark:hover:bg-gray-600"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                        Refuser
                    </button>

                    <!-- Convert to Invoice -->
                    <button
                        v-if="quote.status === 'accepted'"
                        @click="convertToInvoice"
                        :disabled="processing"
                        class="inline-flex items-center rounded-md bg-purple-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 disabled:opacity-50"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M3 4a2 2 0 00-2 2v1.161l8.441 4.221a1.25 1.25 0 001.118 0L19 7.162V6a2 2 0 00-2-2H3z" />
                            <path d="M19 8.839l-7.77 3.885a2.75 2.75 0 01-2.46 0L1 8.839V14a2 2 0 002 2h14a2 2 0 002-2V8.839z" />
                        </svg>
                        Convertir en facture
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Quote Header Info -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Seller Info -->
                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Émetteur</h2>
                    </div>
                    <div class="px-6 py-4">
                        <div v-if="quote.seller_snapshot" class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                            <p class="font-semibold">{{ quote.seller_snapshot.company_name }}</p>
                            <p v-if="quote.seller_snapshot.legal_name">{{ quote.seller_snapshot.legal_name }}</p>
                            <p>{{ quote.seller_snapshot.address }}</p>
                            <p>{{ quote.seller_snapshot.postal_code }} {{ quote.seller_snapshot.city }}</p>
                            <p>{{ quote.seller_snapshot.country }}</p>
                            <p class="pt-2" v-if="quote.seller_snapshot.matricule">
                                <span class="text-gray-500">Matricule:</span> {{ quote.seller_snapshot.matricule }}
                            </p>
                            <p v-if="quote.seller_snapshot.vat_number">
                                <span class="text-gray-500">N° TVA:</span> {{ quote.seller_snapshot.vat_number }}
                            </p>
                        </div>
                        <div v-else class="text-sm text-gray-500 dark:text-gray-400">
                            Informations vendeur non enregistrées
                        </div>
                    </div>
                </div>

                <!-- Buyer Info -->
                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Client</h2>
                    </div>
                    <div class="px-6 py-4">
                        <div v-if="quote.buyer_snapshot" class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                            <p class="font-semibold">{{ quote.buyer_snapshot.company_name || quote.buyer_snapshot.name }}</p>
                            <p v-if="quote.buyer_snapshot.contact_name">{{ quote.buyer_snapshot.contact_name }}</p>
                            <p>{{ quote.buyer_snapshot.address }}</p>
                            <p>{{ quote.buyer_snapshot.postal_code }} {{ quote.buyer_snapshot.city }}</p>
                            <p>{{ quote.buyer_snapshot.country }}</p>
                            <p v-if="quote.buyer_snapshot.vat_number" class="pt-2">
                                <span class="text-gray-500">N° TVA:</span> {{ quote.buyer_snapshot.vat_number }}
                            </p>
                        </div>
                        <div v-else-if="quote.client" class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
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
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Détails</h2>
                </div>
                <div class="px-6 py-4">
                    <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Date de création</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(quote.created_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Valide jusqu'au</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(quote.valid_until) }}</dd>
                        </div>
                        <div v-if="quote.sent_at">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Envoyé le</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(quote.sent_at) }}</dd>
                        </div>
                        <div v-if="quote.accepted_at">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Accepté le</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(quote.accepted_at) }}</dd>
                        </div>
                        <div v-if="quote.declined_at">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Refusé le</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(quote.declined_at) }}</dd>
                        </div>
                        <div v-if="quote.converted_to_invoice_id">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Converti en facture</dt>
                            <dd class="mt-1 text-sm">
                                <Link
                                    :href="route('invoices.show', quote.converted_to_invoice_id)"
                                    class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                                >
                                    Voir la facture
                                </Link>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Quote Items -->
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Lignes du devis</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="py-3.5 pl-6 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                    Description
                                </th>
                                <th class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                    Qté
                                </th>
                                <th class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                    Prix HT
                                </th>
                                <th class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                    TVA
                                </th>
                                <th class="py-3.5 pl-3 pr-6 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                    Total HT
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                            <tr v-for="item in quote.items" :key="item.id">
                                <td class="py-4 pl-6 pr-3 text-sm">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ item.title }}</div>
                                    <div v-if="item.description" class="text-gray-500 dark:text-gray-400 whitespace-pre-line">{{ item.description }}</div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-gray-500 dark:text-gray-400">
                                    {{ item.quantity }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatCurrency(item.unit_price, quote.currency) }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-gray-500 dark:text-gray-400">
                                    {{ item.vat_rate }}%
                                </td>
                                <td class="whitespace-nowrap py-4 pl-3 pr-6 text-right text-sm font-medium text-gray-900 dark:text-white">
                                    {{ formatCurrency(item.total_ht, quote.currency) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <td colspan="4" class="py-3 pl-6 pr-3 text-right text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Total HT
                                </td>
                                <td class="whitespace-nowrap py-3 pl-3 pr-6 text-right text-sm font-medium text-gray-900 dark:text-white">
                                    {{ formatCurrency(quote.total_ht, quote.currency) }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="4" class="py-3 pl-6 pr-3 text-right text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Total TVA
                                </td>
                                <td class="whitespace-nowrap py-3 pl-3 pr-6 text-right text-sm font-medium text-gray-900 dark:text-white">
                                    {{ formatCurrency(quote.total_vat, quote.currency) }}
                                </td>
                            </tr>
                            <tr class="border-t-2 border-gray-300 dark:border-gray-600">
                                <td colspan="4" class="py-3 pl-6 pr-3 text-right text-sm font-bold text-gray-900 dark:text-white">
                                    Total TTC
                                </td>
                                <td class="whitespace-nowrap py-3 pl-3 pr-6 text-right text-sm font-bold text-gray-900 dark:text-white">
                                    {{ formatCurrency(quote.total_ttc, quote.currency) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Notes -->
            <div v-if="quote.notes" class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Notes</h2>
                </div>
                <div class="px-6 py-4">
                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ quote.notes }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
