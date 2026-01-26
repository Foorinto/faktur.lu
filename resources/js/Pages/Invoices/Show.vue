<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    invoice: Object,
});

const processing = ref(false);

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

const markAsSent = () => {
    if (processing.value) return;
    processing.value = true;
    router.post(route('invoices.mark-sent', props.invoice.id), {}, {
        preserveScroll: true,
        onFinish: () => processing.value = false,
    });
};

const markAsPaid = () => {
    if (processing.value) return;
    processing.value = true;
    router.post(route('invoices.mark-paid', props.invoice.id), {}, {
        preserveScroll: true,
        onFinish: () => processing.value = false,
    });
};

const createCreditNote = () => {
    if (processing.value) return;
    if (!confirm('Créer un avoir pour cette facture ? Cette action créera une facture d\'avoir qui annulera les montants de cette facture.')) {
        return;
    }
    processing.value = true;
    router.post(route('invoices.credit-note', props.invoice.id), {}, {
        preserveScroll: true,
        onFinish: () => processing.value = false,
    });
};
</script>

<template>
    <Head :title="`Facture ${invoice.number}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('invoices.index')"
                        class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                        </svg>
                    </Link>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                        <span v-if="invoice.type === 'credit_note'" class="text-red-600 dark:text-red-400">Avoir </span>
                        {{ invoice.number }}
                    </h1>
                    <span
                        :class="getStatusBadgeClass(invoice.status)"
                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                    >
                        {{ getStatusLabel(invoice.status) }}
                    </span>
                </div>

                <div class="flex items-center space-x-3">
                    <!-- PDF Download -->
                    <a
                        :href="route('invoices.show', invoice.id) + '?pdf=1'"
                        target="_blank"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z" />
                            <path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" />
                        </svg>
                        PDF
                    </a>

                    <!-- Mark as Sent -->
                    <button
                        v-if="invoice.status === 'finalized'"
                        @click="markAsSent"
                        :disabled="processing"
                        class="inline-flex items-center rounded-md bg-yellow-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500 disabled:opacity-50"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M3 4a2 2 0 00-2 2v1.161l8.441 4.221a1.25 1.25 0 001.118 0L19 7.162V6a2 2 0 00-2-2H3z" />
                            <path d="M19 8.839l-7.77 3.885a2.75 2.75 0 01-2.46 0L1 8.839V14a2 2 0 002 2h14a2 2 0 002-2V8.839z" />
                        </svg>
                        Marquer envoyée
                    </button>

                    <!-- Mark as Paid -->
                    <button
                        v-if="invoice.status === 'sent'"
                        @click="markAsPaid"
                        :disabled="processing"
                        class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-50"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                        Marquer payée
                    </button>

                    <!-- Create Credit Note -->
                    <button
                        v-if="invoice.type === 'invoice' && ['finalized', 'sent', 'paid'].includes(invoice.status)"
                        @click="createCreditNote"
                        :disabled="processing"
                        class="inline-flex items-center rounded-md border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50 dark:border-red-600 dark:bg-gray-700 dark:text-red-400 dark:hover:bg-gray-600"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 2a2 2 0 00-2 2v11a3 3 0 106 0V4a2 2 0 00-2-2H4zm1 14a1 1 0 100-2 1 1 0 000 2zm5-1.757l4.9-4.9a2.121 2.121 0 013 3l-4.9 4.9a2.121 2.121 0 01-1.5.621h-1a.5.5 0 01-.5-.5v-1a2.121 2.121 0 01.621-1.5z" clip-rule="evenodd" />
                        </svg>
                        Créer un avoir
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Invoice Header Info -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Seller Info -->
                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Émetteur</h2>
                    </div>
                    <div class="px-6 py-4">
                        <div v-if="invoice.seller_snapshot" class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                            <p class="font-semibold">{{ invoice.seller_snapshot.company_name }}</p>
                            <p v-if="invoice.seller_snapshot.legal_name">{{ invoice.seller_snapshot.legal_name }}</p>
                            <p>{{ invoice.seller_snapshot.address_line1 }}</p>
                            <p v-if="invoice.seller_snapshot.address_line2">{{ invoice.seller_snapshot.address_line2 }}</p>
                            <p>{{ invoice.seller_snapshot.postal_code }} {{ invoice.seller_snapshot.city }}</p>
                            <p>{{ invoice.seller_snapshot.country }}</p>
                            <p class="pt-2">
                                <span class="text-gray-500">Matricule:</span> {{ invoice.seller_snapshot.matricule }}
                            </p>
                            <p v-if="invoice.seller_snapshot.vat_number">
                                <span class="text-gray-500">N° TVA:</span> {{ invoice.seller_snapshot.vat_number }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Buyer Info -->
                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Client</h2>
                    </div>
                    <div class="px-6 py-4">
                        <div v-if="invoice.buyer_snapshot" class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                            <p class="font-semibold">{{ invoice.buyer_snapshot.name }}</p>
                            <p v-if="invoice.buyer_snapshot.company_name">{{ invoice.buyer_snapshot.company_name }}</p>
                            <p>{{ invoice.buyer_snapshot.address_line1 }}</p>
                            <p v-if="invoice.buyer_snapshot.address_line2">{{ invoice.buyer_snapshot.address_line2 }}</p>
                            <p>{{ invoice.buyer_snapshot.postal_code }} {{ invoice.buyer_snapshot.city }}</p>
                            <p>{{ invoice.buyer_snapshot.country }}</p>
                            <p v-if="invoice.buyer_snapshot.vat_number" class="pt-2">
                                <span class="text-gray-500">N° TVA:</span> {{ invoice.buyer_snapshot.vat_number }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice Details -->
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Détails</h2>
                </div>
                <div class="px-6 py-4">
                    <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Date d'émission</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(invoice.issued_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Date d'échéance</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(invoice.due_at) }}</dd>
                        </div>
                        <div v-if="invoice.sent_at">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Envoyée le</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(invoice.sent_at) }}</dd>
                        </div>
                        <div v-if="invoice.paid_at">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Payée le</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(invoice.paid_at) }}</dd>
                        </div>
                        <div v-if="invoice.credit_note_for">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Avoir pour</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                <Link
                                    :href="route('invoices.show', invoice.credit_note_for)"
                                    class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                                >
                                    Voir la facture originale
                                </Link>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Invoice Items -->
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Lignes de facture</h2>
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
                            <tr v-for="item in invoice.items" :key="item.id">
                                <td class="py-4 pl-6 pr-3 text-sm text-gray-900 dark:text-white">
                                    {{ item.description }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-gray-500 dark:text-gray-400">
                                    {{ item.quantity }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatCurrency(item.unit_price, invoice.currency) }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-gray-500 dark:text-gray-400">
                                    {{ item.vat_rate }}%
                                </td>
                                <td class="whitespace-nowrap py-4 pl-3 pr-6 text-right text-sm font-medium text-gray-900 dark:text-white">
                                    {{ formatCurrency(item.total_ht, invoice.currency) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <td colspan="4" class="py-3 pl-6 pr-3 text-right text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Total HT
                                </td>
                                <td class="whitespace-nowrap py-3 pl-3 pr-6 text-right text-sm font-medium text-gray-900 dark:text-white">
                                    {{ formatCurrency(invoice.total_ht, invoice.currency) }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="4" class="py-3 pl-6 pr-3 text-right text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Total TVA
                                </td>
                                <td class="whitespace-nowrap py-3 pl-3 pr-6 text-right text-sm font-medium text-gray-900 dark:text-white">
                                    {{ formatCurrency(invoice.total_vat, invoice.currency) }}
                                </td>
                            </tr>
                            <tr class="border-t-2 border-gray-300 dark:border-gray-600">
                                <td colspan="4" class="py-3 pl-6 pr-3 text-right text-sm font-bold text-gray-900 dark:text-white">
                                    Total TTC
                                </td>
                                <td class="whitespace-nowrap py-3 pl-3 pr-6 text-right text-sm font-bold"
                                    :class="invoice.type === 'credit_note' ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'"
                                >
                                    {{ formatCurrency(invoice.total_ttc, invoice.currency) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Notes -->
            <div v-if="invoice.notes" class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Notes</h2>
                </div>
                <div class="px-6 py-4">
                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ invoice.notes }}</p>
                </div>
            </div>

            <!-- Credit Notes linked to this invoice -->
            <div v-if="invoice.credit_notes && invoice.credit_notes.length > 0" class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Avoirs associés</h2>
                </div>
                <div class="px-6 py-4">
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        <li v-for="creditNote in invoice.credit_notes" :key="creditNote.id" class="py-3 flex justify-between items-center">
                            <Link
                                :href="route('invoices.show', creditNote.id)"
                                class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                            >
                                {{ creditNote.number }}
                            </Link>
                            <span class="text-sm text-red-600 dark:text-red-400">
                                {{ formatCurrency(creditNote.total_ttc, creditNote.currency) }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
