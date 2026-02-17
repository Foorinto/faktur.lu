<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    quote: Object,
    clients: Array,
    vatRates: Array,
    units: Array,
    isVatExempt: Boolean,
    vatMentionOptions: Array,
    defaultVatMention: String,
    suggestedVatMention: String,
    defaultQuoteFooter: String,
});

const defaultVatRate = props.isVatExempt ? 0 : 17;

// Format date to yyyy-MM-dd for input[type="date"]
const formatDateForInput = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '';
    return date.toISOString().split('T')[0];
};

const showPreviewModal = ref(false);
const previewHtml = ref('');
const loadingPreview = ref(false);
const editingItemId = ref(null);

// PDF language selection
const pdfLocale = ref(props.quote.client?.locale || 'fr');

const pdfLanguages = [
    { value: 'fr', label: 'Français', flag: '🇫🇷' },
    { value: 'de', label: 'Deutsch', flag: '🇩🇪' },
    { value: 'en', label: 'English', flag: '🇬🇧' },
    { value: 'lb', label: 'Lëtzebuergesch', flag: '🇱🇺' },
];

const selectedPdfLanguage = computed(() => {
    return pdfLanguages.find(lang => lang.value === pdfLocale.value) || pdfLanguages[0];
});

const pdfUrl = computed(() => {
    const baseUrl = route('quotes.pdf.stream', props.quote.id);
    return `${baseUrl}?locale=${pdfLocale.value}`;
});
const editItemForm = useForm({
    title: '',
    description: '',
    quantity: 1,
    unit: '',
    unit_price: 0,
    vat_rate: defaultVatRate,
});

const form = useForm({
    client_id: props.quote.client_id,
    valid_until: formatDateForInput(props.quote.valid_until),
    notes: props.quote.notes || '',
    currency: props.quote.currency,
    vat_mention: props.quote.vat_mention || '',
    custom_vat_mention: props.quote.custom_vat_mention || '',
    footer_message: props.quote.footer_message || '',
});

const itemForm = useForm({
    title: '',
    description: '',
    quantity: 1,
    unit: 'hour',
    unit_price: 0,
    vat_rate: defaultVatRate,
});

// Calculate totals from items
const totals = computed(() => {
    let totalHt = 0;
    let totalVat = 0;

    for (const item of props.quote.items || []) {
        totalHt += parseFloat(item.total_ht) || 0;
        totalVat += parseFloat(item.total_vat) || 0;
    }

    return {
        ht: totalHt,
        vat: totalVat,
        ttc: totalHt + totalVat,
    };
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: props.quote.currency || 'EUR',
    }).format(amount);
};

const updateQuote = () => {
    form.put(route('quotes.update', props.quote.id), {
        preserveScroll: true,
    });
};

const addItem = () => {
    itemForm.post(route('quotes.items.store', props.quote.id), {
        preserveScroll: true,
        onSuccess: () => {
            itemForm.reset();
            itemForm.title = '';
            itemForm.description = '';
            itemForm.quantity = 1;
            itemForm.unit = 'hour';
            itemForm.vat_rate = defaultVatRate;
        },
    });
};

// Get unit label for display (with correct singular/plural)
const getUnitLabel = (unitValue, quantity) => {
    const unitLabels = {
        hour: { singular: 'heure', plural: 'heures' },
        day: { singular: 'jour', plural: 'jours' },
        piece: { singular: 'unité', plural: 'unités' },
        package: { singular: 'forfait', plural: 'forfaits' },
        month: { singular: 'mois', plural: 'mois' },
        word: { singular: 'mot', plural: 'mots' },
        page: { singular: 'page', plural: 'pages' },
    };
    if (!unitValue || !unitLabels[unitValue]) return '';
    return quantity <= 1 ? unitLabels[unitValue].singular : unitLabels[unitValue].plural;
};

// Format quantity without trailing zeros
const formatQuantity = (qty) => {
    const num = parseFloat(qty);
    if (num === Math.floor(num)) {
        return Math.floor(num).toString();
    }
    return num.toFixed(2).replace(/\.?0+$/, '').replace('.', ',');
};

// Move item up or down in the list
const moveItem = (itemId, direction) => {
    router.patch(route('quotes.items.move', [props.quote.id, itemId]), {
        direction: direction,
    }, {
        preserveScroll: true,
    });
};

const deleteItem = (itemId) => {
    if (confirm('Supprimer cette ligne ?')) {
        router.delete(route('quotes.items.destroy', [props.quote.id, itemId]), {
            preserveScroll: true,
        });
    }
};

// Start editing an item
const startEditItem = (item) => {
    editingItemId.value = item.id;
    editItemForm.title = item.title;
    editItemForm.description = item.description || '';
    editItemForm.quantity = parseFloat(item.quantity);
    editItemForm.unit = item.unit || '';
    editItemForm.unit_price = parseFloat(item.unit_price);
    editItemForm.vat_rate = parseFloat(item.vat_rate);
};

// Cancel editing
const cancelEditItem = () => {
    editingItemId.value = null;
    editItemForm.reset();
};

// Save edited item
const saveEditItem = (itemId) => {
    editItemForm.put(route('quotes.items.update', [props.quote.id, itemId]), {
        preserveScroll: true,
        onSuccess: () => {
            editingItemId.value = null;
            editItemForm.reset();
        },
    });
};

const deleteQuote = () => {
    if (confirm('Supprimer ce devis ?')) {
        router.delete(route('quotes.destroy', props.quote.id));
    }
};

const markAsSent = () => {
    router.post(route('quotes.mark-sent', props.quote.id), {}, {
        preserveScroll: true,
    });
};

const markAsAccepted = () => {
    router.post(route('quotes.mark-accepted', props.quote.id), {}, {
        preserveScroll: true,
    });
};

const markAsDeclined = () => {
    router.post(route('quotes.mark-declined', props.quote.id), {}, {
        preserveScroll: true,
    });
};

const convertToInvoice = () => {
    if (!confirm('Convertir ce devis en facture ? Une nouvelle facture brouillon sera créée avec les mêmes lignes.')) {
        return;
    }
    router.post(route('quotes.convert', props.quote.id), {}, {
        preserveScroll: true,
    });
};

// Load preview with locale
const loadPreview = async () => {
    loadingPreview.value = true;
    try {
        const url = route('quotes.preview-html', props.quote.id) + `?locale=${pdfLocale.value}`;
        const response = await axios.get(url);
        previewHtml.value = response.data.html;
    } catch (error) {
        console.error('Error loading preview:', error);
        previewHtml.value = '<p style="color: red; padding: 20px;">Erreur lors du chargement de l\'aperçu</p>';
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
                        Devis {{ quote.reference }}
                    </h1>
                    <span
                        :class="getStatusBadgeClass(quote.status)"
                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                    >
                        {{ getStatusLabel(quote.status) }}
                    </span>
                </div>
                <div class="flex items-center space-x-3">
                    <button
                        type="button"
                        @click="openPreview"
                        class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600"
                    >
                        <svg class="h-4 w-4 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" />
                            <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 010-1.186A10.004 10.004 0 0110 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0110 17c-4.257 0-7.893-2.66-9.336-6.41zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                        </svg>
                        Aperçu
                    </button>
                    <button
                        type="button"
                        @click="deleteQuote"
                        class="inline-flex items-center rounded-xl border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50 dark:border-red-600 dark:bg-slate-700 dark:text-red-400 dark:hover:bg-slate-600"
                    >
                        Supprimer
                    </button>
                    <button
                        v-if="quote.status === 'draft'"
                        type="button"
                        @click="markAsSent"
                        :disabled="!quote.items || quote.items.length === 0"
                        class="inline-flex items-center rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Marquer envoyé
                    </button>

                    <!-- Accept button (for sent quotes) -->
                    <button
                        v-if="quote.status === 'sent'"
                        type="button"
                        @click="markAsAccepted"
                        class="inline-flex items-center rounded-xl bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                        Accepter
                    </button>

                    <!-- Decline button (for sent quotes) -->
                    <button
                        v-if="quote.status === 'sent'"
                        type="button"
                        @click="markAsDeclined"
                        class="inline-flex items-center rounded-xl border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50 dark:border-red-600 dark:bg-slate-700 dark:text-red-400 dark:hover:bg-slate-600"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                        Refuser
                    </button>

                    <!-- Convert to Invoice button (for accepted quotes) -->
                    <button
                        v-if="quote.status === 'accepted'"
                        type="button"
                        @click="convertToInvoice"
                        class="inline-flex items-center rounded-xl bg-purple-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M1 4a1 1 0 011-1h16a1 1 0 011 1v8a1 1 0 01-1 1H2a1 1 0 01-1-1V4zm12 4a3 3 0 11-6 0 3 3 0 016 0zM4 9a1 1 0 100-2 1 1 0 000 2zm13-1a1 1 0 11-2 0 1 1 0 012 0zM1.75 14.5a.75.75 0 000 1.5c4.417 0 8.693.603 12.749 1.73 1.111.309 2.251-.512 2.251-1.696v-.784a.75.75 0 00-1.5 0v.784a.272.272 0 01-.35.25A49.043 49.043 0 001.75 14.5z" clip-rule="evenodd" />
                        </svg>
                        Convertir en facture
                    </button>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <!-- Client & Settings -->
                <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">Informations</h2>
                    </div>
                    <div class="px-6 py-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="client_id" value="Client" />
                                <select
                                    id="client_id"
                                    v-model="form.client_id"
                                    @change="updateQuote"
                                    class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                >
                                    <option v-for="client in clients" :key="client.id" :value="client.id">
                                        {{ client.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.client_id" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="valid_until" value="Valide jusqu'au" />
                                <input
                                    id="valid_until"
                                    v-model="form.valid_until"
                                    @change="updateQuote"
                                    type="date"
                                    class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                />
                                <InputError :message="form.errors.valid_until" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-4">
                            <InputLabel for="notes" value="Notes / Conditions" />
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                @blur="updateQuote"
                                rows="2"
                                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                placeholder="Conditions particulières..."
                            ></textarea>
                        </div>

                        <div class="mt-4">
                            <InputLabel for="vat_mention" value="Mention TVA (optionnel)" />
                            <select
                                id="vat_mention"
                                v-model="form.vat_mention"
                                @change="updateQuote"
                                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                            >
                                <option value="">Mention par défaut</option>
                                <option v-for="option in vatMentionOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                Cette mention apparaîtra sur le PDF du devis.
                            </p>
                        </div>

                        <div v-if="form.vat_mention === 'other'" class="mt-4">
                            <InputLabel for="custom_vat_mention" value="Mention TVA personnalisée" />
                            <textarea
                                id="custom_vat_mention"
                                v-model="form.custom_vat_mention"
                                @blur="updateQuote"
                                rows="2"
                                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                placeholder="Entrez votre mention TVA personnalisée..."
                            ></textarea>
                        </div>

                        <div class="mt-4">
                            <InputLabel for="footer_message" value="Message de pied de page (optionnel)" />
                            <textarea
                                id="footer_message"
                                v-model="form.footer_message"
                                @blur="updateQuote"
                                rows="2"
                                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                :placeholder="defaultQuoteFooter"
                            ></textarea>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                Si vide, le message par défaut sera utilisé : "{{ defaultQuoteFooter }}"
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Quote items -->
                <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">Lignes du devis</h2>
                    </div>
                    <div class="divide-y divide-slate-200 dark:divide-slate-700">
                        <!-- Existing items -->
                        <div
                            v-for="(item, index) in quote.items"
                            :key="item.id"
                            class="px-6 py-4"
                        >
                            <!-- Edit mode -->
                            <div v-if="editingItemId === item.id" class="space-y-3">
                                <div class="grid grid-cols-1 gap-3">
                                    <div>
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Titre</label>
                                        <input
                                            v-model="editItemForm.title"
                                            type="text"
                                            class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                            placeholder="Titre de la prestation"
                                            required
                                        />
                                        <InputError :message="editItemForm.errors.title" class="mt-1" />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Description (optionnel)</label>
                                        <textarea
                                            v-model="editItemForm.description"
                                            rows="2"
                                            class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                            placeholder="Description détaillée"
                                        ></textarea>
                                        <InputError :message="editItemForm.errors.description" class="mt-1" />
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-3 items-end">
                                    <div class="w-20">
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Quantité</label>
                                        <input
                                            v-model.number="editItemForm.quantity"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                            required
                                        />
                                    </div>
                                    <div class="w-28">
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Unité</label>
                                        <select
                                            v-model="editItemForm.unit"
                                            class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                        >
                                            <option value="">Sans unité</option>
                                            <option v-for="unit in units" :key="unit.value" :value="unit.value">
                                                {{ unit.label }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="w-28">
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Prix HT</label>
                                        <input
                                            v-model.number="editItemForm.unit_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                            required
                                        />
                                    </div>
                                    <div class="w-28">
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">TVA</label>
                                        <select
                                            v-if="!isVatExempt"
                                            v-model.number="editItemForm.vat_rate"
                                            class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                        >
                                            <option v-for="rate in vatRates" :key="rate.value" :value="rate.value">
                                                {{ rate.value }}%
                                            </option>
                                        </select>
                                        <div
                                            v-else
                                            class="block w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-slate-500 dark:border-slate-600 dark:bg-slate-600 dark:text-slate-400 text-sm"
                                        >
                                            0%
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button
                                            type="button"
                                            @click="saveEditItem(item.id)"
                                            :disabled="editItemForm.processing"
                                            class="inline-flex items-center rounded-xl bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-50"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            @click="cancelEditItem"
                                            class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- View mode -->
                            <div v-else class="flex items-center justify-between">
                                <!-- Reorder buttons -->
                                <div class="flex flex-col mr-3" v-if="quote.items.length > 1">
                                    <button
                                        type="button"
                                        @click="moveItem(item.id, 'up')"
                                        :disabled="index === 0"
                                        class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 disabled:opacity-30 disabled:cursor-not-allowed"
                                        title="Monter"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M14.77 12.79a.75.75 0 01-1.06-.02L10 8.832 6.29 12.77a.75.75 0 11-1.08-1.04l4.25-4.5a.75.75 0 011.08 0l4.25 4.5a.75.75 0 01-.02 1.06z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        @click="moveItem(item.id, 'down')"
                                        :disabled="index === quote.items.length - 1"
                                        class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 disabled:opacity-30 disabled:cursor-not-allowed"
                                        title="Descendre"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="flex-1 cursor-pointer" @click="startEditItem(item)">
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">
                                        {{ item.title }}
                                    </p>
                                    <p v-if="item.description" class="text-sm text-slate-500 dark:text-slate-400 whitespace-pre-line">
                                        {{ item.description }}
                                    </p>
                                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                                        {{ formatQuantity(item.quantity) }} {{ getUnitLabel(item.unit, item.quantity) }} x {{ formatCurrency(item.unit_price) }} (TVA {{ item.vat_rate }}%)
                                    </p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-slate-900 dark:text-white mr-2">
                                        {{ formatCurrency(item.total_ht) }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="startEditItem(item)"
                                        class="p-1 text-slate-400 hover:text-primary-600 dark:hover:text-primary-400"
                                        title="Modifier"
                                    >
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        @click="deleteItem(item.id)"
                                        class="p-1 text-slate-400 hover:text-red-600 dark:hover:text-red-400"
                                        title="Supprimer"
                                    >
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.519.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Add new item form -->
                        <form @submit.prevent="addItem" class="px-6 py-4">
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">Ajouter une ligne</p>
                            <div class="space-y-3">
                                <!-- Title and description -->
                                <div class="grid grid-cols-1 gap-3">
                                    <div>
                                        <input
                                            v-model="itemForm.title"
                                            type="text"
                                            class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                            placeholder="Titre de la prestation"
                                            required
                                        />
                                        <InputError :message="itemForm.errors.title" class="mt-1" />
                                    </div>
                                    <div>
                                        <textarea
                                            v-model="itemForm.description"
                                            rows="2"
                                            class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                            placeholder="Description détaillée (optionnel)"
                                        ></textarea>
                                        <InputError :message="itemForm.errors.description" class="mt-1" />
                                    </div>
                                </div>
                                <!-- Quantity, unit, price, VAT -->
                                <div class="flex flex-wrap gap-3 items-end">
                                    <div class="w-20">
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Quantité</label>
                                        <input
                                            v-model.number="itemForm.quantity"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                            required
                                        />
                                    </div>
                                    <div class="w-28">
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Unité</label>
                                        <select
                                            v-model="itemForm.unit"
                                            class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                        >
                                            <option value="">Sans unité</option>
                                            <option v-for="unit in units" :key="unit.value" :value="unit.value">
                                                {{ unit.label }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="w-28">
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Prix HT</label>
                                        <input
                                            v-model.number="itemForm.unit_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                            required
                                        />
                                    </div>
                                    <div class="w-28">
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">TVA</label>
                                        <select
                                            v-if="!isVatExempt"
                                            v-model.number="itemForm.vat_rate"
                                            class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                        >
                                            <option v-for="rate in vatRates" :key="rate.value" :value="rate.value">
                                                {{ rate.value }}%
                                            </option>
                                        </select>
                                        <div
                                            v-else
                                            class="block w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-slate-500 dark:border-slate-600 dark:bg-slate-600 dark:text-slate-400 text-sm"
                                        >
                                            0%
                                        </div>
                                    </div>
                                    <button
                                        type="submit"
                                        :disabled="itemForm.processing"
                                        class="inline-flex items-center rounded-xl bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 disabled:opacity-50"
                                    >
                                        <svg class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                                        </svg>
                                        Ajouter
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar with totals -->
            <div class="space-y-6">
                <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800 sticky top-20">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">Résumé</h2>
                    </div>
                    <div class="px-6 py-4 space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500 dark:text-slate-400">Total HT</span>
                            <span class="font-medium text-slate-900 dark:text-white">{{ formatCurrency(totals.ht) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500 dark:text-slate-400">TVA</span>
                            <span class="font-medium text-slate-900 dark:text-white">{{ formatCurrency(totals.vat) }}</span>
                        </div>
                        <div class="border-t border-slate-200 dark:border-slate-700 pt-3">
                            <div class="flex justify-between">
                                <span class="text-base font-medium text-slate-900 dark:text-white">Total TTC</span>
                                <span class="text-lg font-bold text-slate-900 dark:text-white">{{ formatCurrency(totals.ttc) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50">
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ quote.items?.length || 0 }} ligne(s)
                        </p>
                    </div>
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
                            Aperçu du devis
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
                                @click="loadPreview"
                                :disabled="loadingPreview"
                                class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300 disabled:opacity-50"
                            >
                                <svg class="h-4 w-4 mr-1" :class="{ 'animate-spin': loadingPreview }" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H3.989a.75.75 0 00-.75.75v4.242a.75.75 0 001.5 0v-2.43l.31.31a7 7 0 0011.712-3.138.75.75 0 00-1.449-.39zm1.23-3.723a.75.75 0 00.219-.53V2.929a.75.75 0 00-1.5 0V5.36l-.31-.31A7 7 0 003.239 8.188a.75.75 0 101.448.389A5.5 5.5 0 0113.89 6.11l.311.31h-2.432a.75.75 0 000 1.5h4.243a.75.75 0 00.53-.219z" clip-rule="evenodd" />
                                </svg>
                                Actualiser
                            </button>
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
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
