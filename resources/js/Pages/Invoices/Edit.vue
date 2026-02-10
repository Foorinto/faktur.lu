<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import VatScenarioIndicator from '@/Components/VatScenarioIndicator.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    invoice: Object,
    clients: Array,
    vatRates: Array,
    units: Array,
    isVatExempt: Boolean,
    defaultInvoiceFooter: String,
    vatMentionOptions: Array,
    defaultVatMention: String,
    defaultCustomVatMention: String,
    clientVatScenario: Object,
    suggestedVatMention: String,
    vatScenarios: Object,
});

const defaultVatRate = props.isVatExempt ? 0 : 17;

// Format date to yyyy-MM-dd for input[type="date"]
const formatDateForInput = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '';
    return date.toISOString().split('T')[0];
};

// Get selected client's VAT scenario
const selectedClient = computed(() => {
    return props.clients?.find(c => c.id === form.client_id);
});

const clientVatScenario = computed(() => {
    return selectedClient.value?.vat_scenario || null;
});

// Suggested VAT rate based on client scenario
const suggestedVatRate = computed(() => {
    if (!clientVatScenario.value) return defaultVatRate;
    return clientVatScenario.value.rate ?? defaultVatRate;
});

const showFinalizeModal = ref(false);
const showPreviewModal = ref(false);
const previewHtml = ref('');
const loadingPreview = ref(false);
const editingItemId = ref(null);

// PDF language selection
const pdfLocale = ref(props.invoice.client?.locale || 'fr');

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
    const baseUrl = route('invoices.draft-pdf', props.invoice.id);
    return `${baseUrl}?locale=${pdfLocale.value}`;
});
const editItemForm = useForm({
    title: '',
    description: '',
    quantity: 1,
    unit: '',
    unit_price: 0,
    vat_rate: 17,
});

const form = useForm({
    client_id: props.invoice.client_id,
    title: props.invoice.title || '',
    due_at: formatDateForInput(props.invoice.due_at),
    notes: props.invoice.notes || '',
    footer_message: props.invoice.footer_message || '',
    vat_mention: props.invoice.vat_mention || '',
    custom_vat_mention: props.invoice.custom_vat_mention || '',
    currency: props.invoice.currency,
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

    for (const item of props.invoice.items || []) {
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
        currency: props.invoice.currency || 'EUR',
    }).format(amount);
};

const hasChanges = ref(false);
const saveSuccess = ref(false);

// Track changes
watch(() => [form.client_id, form.title, form.due_at, form.notes, form.footer_message, form.vat_mention, form.custom_vat_mention], () => {
    hasChanges.value = true;
    saveSuccess.value = false;
}, { deep: true });

// Auto-suggest VAT mention when client changes
watch(() => form.client_id, (newClientId) => {
    if (newClientId) {
        const client = props.clients?.find(c => c.id === newClientId);
        if (client?.vat_scenario?.mention && !form.vat_mention) {
            // Only suggest if no mention is already set
            form.vat_mention = client.vat_scenario.mention;
        }
    }
});

const updateInvoice = () => {
    form.put(route('invoices.update', props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => {
            hasChanges.value = false;
            saveSuccess.value = true;
            setTimeout(() => {
                saveSuccess.value = false;
            }, 2000);
        },
    });
};

const addItem = () => {
    itemForm.post(route('invoices.items.store', props.invoice.id), {
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
    router.patch(route('invoices.items.move', [props.invoice.id, itemId]), {
        direction: direction,
    }, {
        preserveScroll: true,
    });
};

const deleteItem = (itemId) => {
    if (confirm(t('delete_line_confirm'))) {
        router.delete(route('invoices.items.destroy', [props.invoice.id, itemId]), {
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
    editItemForm.put(route('invoices.items.update', [props.invoice.id, itemId]), {
        preserveScroll: true,
        onSuccess: () => {
            editingItemId.value = null;
            editItemForm.reset();
        },
    });
};

const deleteInvoice = () => {
    if (confirm(t('delete_draft_confirm'))) {
        router.delete(route('invoices.destroy', props.invoice.id));
    }
};

const finalizeInvoice = () => {
    router.post(route('invoices.finalize', props.invoice.id), {}, {
        onSuccess: () => {
            showFinalizeModal.value = false;
        },
    });
};

// Load preview with locale
const loadPreview = async () => {
    loadingPreview.value = true;
    try {
        const url = route('invoices.preview-draft', props.invoice.id) + `?locale=${pdfLocale.value}`;
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
</script>

<template>
    <Head :title="`${t('invoice')} ${invoice.display_number}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('invoices.index')"
                        class="text-slate-400 hover:text-slate-500 dark:text-slate-500 dark:hover:text-slate-400"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                        </svg>
                    </Link>
                    <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                        {{ invoice.title || t('draft_invoice') }}
                    </h1>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-800 dark:bg-slate-700 dark:text-slate-300">
                        {{ t('draft') }}
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
                        {{ t('preview') }}
                    </button>
                    <button
                        type="button"
                        @click="deleteInvoice"
                        class="inline-flex items-center rounded-xl border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50 dark:border-red-600 dark:bg-slate-700 dark:text-red-400 dark:hover:bg-slate-600"
                    >
                        {{ t('delete') }}
                    </button>
                    <button
                        type="button"
                        @click="showFinalizeModal = true"
                        :disabled="!invoice.items || invoice.items.length === 0"
                        class="inline-flex items-center rounded-xl bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ t('finalize') }}
                    </button>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <!-- Client & Settings -->
                <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('information') }}</h2>
                    </div>
                    <div class="px-6 py-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="client_id" :value="t('client')" />
                                <select
                                    id="client_id"
                                    v-model="form.client_id"
                                    class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                >
                                    <option v-for="client in clients" :key="client.id" :value="client.id">
                                        {{ client.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.client_id" class="mt-2" />
                                <!-- VAT Scenario indicator -->
                                <div v-if="clientVatScenario" class="mt-2">
                                    <VatScenarioIndicator :scenario="clientVatScenario" size="sm" />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="title" :value="t('title_optional')" />
                                <input
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                    :placeholder="t('example_placeholder')"
                                />
                                <InputError :message="form.errors.title" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="due_at" :value="t('due_date')" />
                                <input
                                    id="due_at"
                                    v-model="form.due_at"
                                    type="date"
                                    class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                />
                                <InputError :message="form.errors.due_at" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-4">
                            <InputLabel for="notes" :value="t('notes')" />
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="2"
                                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                :placeholder="t('notes_placeholder')"
                            ></textarea>
                        </div>

                        <div class="mt-4">
                            <InputLabel for="vat_mention" :value="t('vat_mention_optional')" />
                            <select
                                id="vat_mention"
                                v-model="form.vat_mention"
                                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                            >
                                <option value="">{{ t('use_default_mention') }}</option>
                                <option v-for="option in vatMentionOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                {{ t('default_mention_note') }}
                            </p>
                        </div>

                        <div v-if="form.vat_mention === 'other'" class="mt-4">
                            <InputLabel for="custom_vat_mention" :value="t('custom_vat_mention_label')" />
                            <textarea
                                id="custom_vat_mention"
                                v-model="form.custom_vat_mention"
                                rows="2"
                                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                :placeholder="t('custom_vat_mention_placeholder')"
                            ></textarea>
                        </div>

                        <div class="mt-4">
                            <InputLabel for="footer_message" :value="t('footer_message_optional')" />
                            <textarea
                                id="footer_message"
                                v-model="form.footer_message"
                                rows="2"
                                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                :placeholder="defaultInvoiceFooter"
                            ></textarea>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                {{ t('empty_default_message') }} "{{ defaultInvoiceFooter }}"
                            </p>
                        </div>

                        <div class="mt-4 flex items-center justify-end gap-3">
                            <span v-if="saveSuccess" class="text-sm text-green-600 dark:text-green-400">
                                {{ t('saved') }}
                            </span>
                            <button
                                type="button"
                                @click="updateInvoice"
                                :disabled="form.processing"
                                class="inline-flex items-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 disabled:opacity-50"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ form.processing ? t('saving') : t('save') }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Invoice items -->
                <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('invoice_lines') }}</h2>
                    </div>
                    <div class="divide-y divide-slate-200 dark:divide-slate-700">
                        <!-- Existing items -->
                        <div
                            v-for="(item, index) in invoice.items"
                            :key="item.id"
                            class="px-6 py-4"
                        >
                            <!-- Edit mode -->
                            <div v-if="editingItemId === item.id" class="space-y-3">
                                <div class="grid grid-cols-1 gap-3">
                                    <div>
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">{{ t('title') }}</label>
                                        <input
                                            v-model="editItemForm.title"
                                            type="text"
                                            class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                            :placeholder="t('service_title')"
                                            required
                                        />
                                        <InputError :message="editItemForm.errors.title" class="mt-1" />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">{{ t('description') }} ({{ t('optional') }})</label>
                                        <textarea
                                            v-model="editItemForm.description"
                                            rows="2"
                                            class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                            :placeholder="t('detailed_description')"
                                        ></textarea>
                                        <InputError :message="editItemForm.errors.description" class="mt-1" />
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-3 items-end">
                                    <div class="w-20">
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">{{ t('quantity') }}</label>
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
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">{{ t('unit') }}</label>
                                        <select
                                            v-model="editItemForm.unit"
                                            class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                        >
                                            <option value="">{{ t('without_unit') }}</option>
                                            <option v-for="unit in units" :key="unit.value" :value="unit.value">
                                                {{ unit.label }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="w-28">
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">{{ t('price_ht') }}</label>
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
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">{{ t('vat') }}</label>
                                        <select
                                            v-model.number="editItemForm.vat_rate"
                                            class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                        >
                                            <option v-for="rate in vatRates" :key="rate.value" :value="rate.value">
                                                {{ rate.value }}%
                                            </option>
                                        </select>
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
                                <div class="flex flex-col mr-3" v-if="invoice.items.length > 1">
                                    <button
                                        type="button"
                                        @click="moveItem(item.id, 'up')"
                                        :disabled="index === 0"
                                        class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 disabled:opacity-30 disabled:cursor-not-allowed"
                                        :title="t('move_up')"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M14.77 12.79a.75.75 0 01-1.06-.02L10 8.832 6.29 12.77a.75.75 0 11-1.08-1.04l4.25-4.5a.75.75 0 011.08 0l4.25 4.5a.75.75 0 01-.02 1.06z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        @click="moveItem(item.id, 'down')"
                                        :disabled="index === invoice.items.length - 1"
                                        class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 disabled:opacity-30 disabled:cursor-not-allowed"
                                        :title="t('move_down')"
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
                                        :title="t('edit')"
                                    >
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        @click="deleteItem(item.id)"
                                        class="p-1 text-slate-400 hover:text-red-600 dark:hover:text-red-400"
                                        :title="t('delete')"
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
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">{{ t('add_line') }}</p>
                            <div class="space-y-3">
                                <!-- Title and description -->
                                <div class="grid grid-cols-1 gap-3">
                                    <div>
                                        <input
                                            v-model="itemForm.title"
                                            type="text"
                                            class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                            :placeholder="t('service_title')"
                                            required
                                        />
                                        <InputError :message="itemForm.errors.title" class="mt-1" />
                                    </div>
                                    <div>
                                        <textarea
                                            v-model="itemForm.description"
                                            rows="2"
                                            class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                            :placeholder="t('detailed_description_optional')"
                                        ></textarea>
                                        <InputError :message="itemForm.errors.description" class="mt-1" />
                                    </div>
                                </div>
                                <!-- Quantity, unit, price, VAT -->
                                <div class="flex flex-wrap gap-3 items-end">
                                    <div class="w-20">
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">{{ t('quantity') }}</label>
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
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">{{ t('unit') }}</label>
                                        <select
                                            v-model="itemForm.unit"
                                            class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                        >
                                            <option value="">{{ t('without_unit') }}</option>
                                            <option v-for="unit in units" :key="unit.value" :value="unit.value">
                                                {{ unit.label }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="w-28">
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">{{ t('price_ht') }}</label>
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
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">{{ t('vat') }}</label>
                                        <select
                                            v-model.number="itemForm.vat_rate"
                                            class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm"
                                        >
                                            <option v-for="rate in vatRates" :key="rate.value" :value="rate.value">
                                                {{ rate.value }}%
                                            </option>
                                        </select>
                                    </div>
                                    <button
                                        type="submit"
                                        :disabled="itemForm.processing"
                                        class="inline-flex items-center rounded-xl bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 disabled:opacity-50"
                                    >
                                        <svg class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                                        </svg>
                                        {{ t('add') }}
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
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('summary') }}</h2>
                    </div>
                    <div class="px-6 py-4 space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500 dark:text-slate-400">{{ t('total_ht') }}</span>
                            <span class="font-medium text-slate-900 dark:text-white">{{ formatCurrency(totals.ht) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500 dark:text-slate-400">{{ t('vat') }}</span>
                            <span class="font-medium text-slate-900 dark:text-white">{{ formatCurrency(totals.vat) }}</span>
                        </div>
                        <div class="border-t border-slate-200 dark:border-slate-700 pt-3">
                            <div class="flex justify-between">
                                <span class="text-base font-medium text-slate-900 dark:text-white">{{ t('total_ttc') }}</span>
                                <span class="text-lg font-bold text-slate-900 dark:text-white">{{ formatCurrency(totals.ttc) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50">
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ invoice.items?.length || 0 }} {{ (invoice.items?.length || 0) <= 1 ? t('line') : t('lines') }}
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
                            {{ t('invoice_preview') }}
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
                                {{ t('refresh') }}
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
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
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
                        <p class="text-sm text-slate-500 dark:text-slate-400 mr-auto">
                            {{ t('preview_finalize_note') }}
                        </p>
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

        <!-- Finalize Modal -->
        <div v-if="showFinalizeModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" @click="showFinalizeModal = false"></div>

                <div class="inline-block transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all dark:bg-slate-800 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                    <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg font-medium leading-6 text-slate-900 dark:text-white">
                                    {{ t('finalize_invoice_question') }}
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-slate-500 dark:text-slate-400">
                                        {{ t('finalize_invoice_warning') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-4 py-3 dark:bg-slate-700 sm:flex sm:flex-row-reverse sm:px-6">
                        <button
                            type="button"
                            @click="finalizeInvoice"
                            class="inline-flex w-full justify-center rounded-xl bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 sm:ml-3 sm:w-auto"
                        >
                            {{ t('finalize') }}
                        </button>
                        <button
                            type="button"
                            @click="showFinalizeModal = false"
                            class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:bg-slate-600 dark:text-white dark:ring-slate-500 sm:mt-0 sm:w-auto"
                        >
                            {{ t('cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
