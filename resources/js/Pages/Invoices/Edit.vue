<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import BillingNav from '@/Components/BillingNav.vue';
import InputError from '@/Components/InputError.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import VatScenarioIndicator from '@/Components/VatScenarioIndicator.vue';
import ProductAutocomplete from '@/Components/ProductAutocomplete.vue';
import FlagIcon from '@/Components/FlagIcon.vue';
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
    { value: 'fr', label: 'Français' },
    { value: 'de', label: 'Deutsch' },
    { value: 'en', label: 'English' },
    { value: 'lb', label: 'Lëtzebuergesch' },
    { value: 'pt', label: 'Português' },
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
    discount_type: 'percent',
    discount_value: 0,
    vat_rate: 17,
});

const form = useForm({
    client_id: props.invoice.client_id,
    title: props.invoice.title || '',
    issued_at: formatDateForInput(props.invoice.issued_at),
    due_at: formatDateForInput(props.invoice.due_at),
    notes: props.invoice.notes || '',
    footer_message: props.invoice.footer_message || '',
    vat_mention: props.invoice.vat_mention || '',
    custom_vat_mention: props.invoice.custom_vat_mention || '',
    currency: props.invoice.currency,
    retention_guarantee_rate: props.invoice.retention_guarantee_rate || null,
    retention_release_date: formatDateForInput(props.invoice.retention_release_date),
});

const showRetention = ref(!!props.invoice.retention_guarantee_rate);

const itemForm = useForm({
    title: '',
    description: '',
    quantity: 1,
    unit: 'hour',
    unit_price: 0,
    discount_type: 'percent',
    discount_value: 0,
    vat_rate: defaultVatRate,
});

// Custom ("Autre") VAT rate handling for the item forms
const isKnownVat = (rate) => (props.vatRates || []).some(r => Number(r.value) === Number(rate));
const editVatCustom = ref(false);
const itemVatCustom = ref(false);
const onEditVatChange = (val) => {
    if (val === 'custom') { editVatCustom.value = true; } else { editVatCustom.value = false; editItemForm.vat_rate = parseFloat(val); }
};
const onItemVatChange = (val) => {
    if (val === 'custom') { itemVatCustom.value = true; } else { itemVatCustom.value = false; itemForm.vat_rate = parseFloat(val); }
};

// Pre-fill from a selected catalogue product (FEAT-095).
const applyProductToEdit = (product) => {
    editItemForm.title = product.designation;
    if (product.description) { editItemForm.description = product.description; }
    editItemForm.unit_price = Number(product.unit_price_ht);
    if (product.unit) { editItemForm.unit = product.unit; }
    const rate = Number(product.vat_rate);
    editItemForm.vat_rate = rate;
    editVatCustom.value = !isKnownVat(rate);
};
const applyProductToItem = (product) => {
    itemForm.title = product.designation;
    if (product.description) { itemForm.description = product.description; }
    itemForm.unit_price = Number(product.unit_price_ht);
    if (product.unit) { itemForm.unit = product.unit; }
    const rate = Number(product.vat_rate);
    itemForm.vat_rate = rate;
    itemVatCustom.value = !isKnownVat(rate);
};

// Global discounts (on the total)
const discountForm = useForm({ label: '', type: 'percent', value: null });

const addDiscount = () => {
    discountForm.post(route('invoices.discounts.store', props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => discountForm.reset(),
    });
};

const removeDiscount = (discountId) => {
    router.delete(route('invoices.discounts.destroy', [props.invoice.id, discountId]), {
        preserveScroll: true,
    });
};

// Euro amount of a discount, for display (percent = % of the line subtotal)
const discountAmount = (discount, subtotal) => {
    const value = parseFloat(discount.value) || 0;
    return discount.type === 'amount' ? value : subtotal * value / 100;
};

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

const doUpdateInvoice = () => {
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

const updateInvoice = () => {
    if (editingItemId.value !== null) {
        const itemId = editingItemId.value;
        editItemForm.put(route('invoices.items.update', [props.invoice.id, itemId]), {
            preserveScroll: true,
            onSuccess: () => {
                editingItemId.value = null;
                editItemForm.reset();
                doUpdateInvoice();
            },
        });
        return;
    }
    doUpdateInvoice();
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
    editItemForm.discount_type = item.discount_type || 'percent';
    editItemForm.discount_value = parseFloat(item.discount_value) || 0;
    editItemForm.vat_rate = parseFloat(item.vat_rate);
    editVatCustom.value = !isKnownVat(item.vat_rate);
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

const doFinalizeInvoice = () => {
    form.put(route('invoices.update', props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => {
            router.post(route('invoices.finalize', props.invoice.id), {}, {
                onSuccess: () => {
                    showFinalizeModal.value = false;
                },
            });
        },
    });
};

const finalizeInvoice = () => {
    if (editingItemId.value !== null) {
        const itemId = editingItemId.value;
        editItemForm.put(route('invoices.items.update', [props.invoice.id, itemId]), {
            preserveScroll: true,
            onSuccess: () => {
                editingItemId.value = null;
                editItemForm.reset();
                doFinalizeInvoice();
            },
        });
        return;
    }
    doFinalizeInvoice();
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
            <div class="flex items-center space-x-3">
                <Link
                    :href="route('invoices.index')"
                    class="text-slate-400 hover:text-slate-500 dark:text-slate-500 dark:hover:text-slate-400"
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" /></svg>
                </Link>
                <h1 class="text-lg sm:text-xl font-semibold text-slate-900 dark:text-white">
                    {{ invoice.title || t('draft_invoice') }}
                </h1>
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-800 dark:bg-gray-800 dark:text-slate-300">
                    {{ t('draft') }}
                </span>
            </div>
        </template>
        <template #header-actions>
            <button
                type="button"
                @click="openPreview"
                class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300 dark:hover:bg-gray-800"
            >
                <svg class="h-4 w-4 mr-1.5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" /><path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 010-1.186A10.004 10.004 0 0110 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0110 17c-4.257 0-7.893-2.66-9.336-6.41zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" /></svg>
                {{ t('preview') }}
            </button>
            <button
                type="button"
                @click="deleteInvoice"
                class="inline-flex items-center rounded-xl border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50 dark:border-red-600 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-gray-800"
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
        </template>

        <BillingNav class="mb-6" />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <!-- Client & Settings -->
                <div class="overflow-hidden rounded-xl bg-white shadow dark:bg-surface-card">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('information') }}</h2>
                    </div>
                    <div class="px-6 py-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="client_id" :value="t('client')" />
                                <select
                                    id="client_id"
                                    v-model="form.client_id"
                                    class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
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
                                    class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    :placeholder="t('example_placeholder')"
                                />
                                <InputError :message="form.errors.title" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="issued_at" :value="t('issue_date')" />
                                <input
                                    id="issued_at"
                                    v-model="form.issued_at"
                                    type="date"
                                    class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />
                                <InputError :message="form.errors.issued_at" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="due_at" :value="t('due_date')" />
                                <input
                                    id="due_at"
                                    v-model="form.due_at"
                                    type="date"
                                    class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
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
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                :placeholder="t('notes_placeholder')"
                            ></textarea>
                        </div>

                        <div class="mt-4">
                            <InputLabel for="vat_mention" :value="t('vat_mention_optional')" />
                            <select
                                id="vat_mention"
                                v-model="form.vat_mention"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
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
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                :placeholder="t('custom_vat_mention_placeholder')"
                            ></textarea>
                        </div>

                        <div class="mt-4">
                            <InputLabel for="footer_message" :value="t('footer_message_optional')" />
                            <RichTextEditor use-company-link-color v-model="form.footer_message" class="mt-1" />
                            <p v-if="defaultInvoiceFooter && !form.footer_message" class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                {{ t('add_footer_suggestion') }}
                                <button
                                    type="button"
                                    @click="form.footer_message = defaultInvoiceFooter"
                                    class="text-primary-500 hover:underline font-medium"
                                >« {{ defaultInvoiceFooter }} »</button>
                            </p>
                        </div>

                        <!-- Retenue de garantie -->
                        <div class="mt-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                            <div class="flex items-center gap-2 mb-3">
                                <input id="enable_retention_edit" type="checkbox" v-model="showRetention" class="rounded border-gray-300 text-primary-500 focus:ring-primary-500" />
                                <label for="enable_retention_edit" class="text-sm font-medium text-slate-700 dark:text-slate-300 cursor-pointer">{{ t('retention_section_title') }}</label>
                            </div>
                            <div v-if="showRetention" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="retention_rate_edit" :value="t('retention_percentage_label')" />
                                    <input id="retention_rate_edit" v-model="form.retention_guarantee_rate" type="number" step="0.01" min="0" max="100" placeholder="5" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                                </div>
                                <div>
                                    <InputLabel for="retention_release_edit" :value="t('retention_release_date_label')" />
                                    <input id="retention_release_edit" v-model="form.retention_release_date" type="date" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                                </div>
                            </div>
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
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                {{ form.processing ? t('saving') : t('save') }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Invoice items -->
                <div class="overflow-hidden rounded-xl bg-white shadow dark:bg-surface-card">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
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
                                        <ProductAutocomplete
                                            input-id="edit-item-title"
                                            v-model="editItemForm.title"
                                            :placeholder="t('service_title')"
                                            required
                                            @select="applyProductToEdit($event)"
                                        />
                                        <InputError :message="editItemForm.errors.title" class="mt-1" />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">{{ t('description') }} ({{ t('optional') }})</label>
                                        <textarea
                                            v-model="editItemForm.description"
                                            rows="2"
                                            class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
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
                                            class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
                                            required
                                        />
                                    </div>
                                    <div class="w-28">
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">{{ t('unit') }}</label>
                                        <select
                                            v-model="editItemForm.unit"
                                            class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
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
                                            class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
                                            required
                                        />
                                    </div>
                                    <div class="w-32">
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">{{ t('discount') }}</label>
                                        <div class="flex">
                                            <input
                                                v-model.number="editItemForm.discount_value"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                :max="editItemForm.discount_type === 'percent' ? 100 : null"
                                                class="block w-full rounded-l-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
                                                placeholder="0"
                                            />
                                            <select
                                                v-model="editItemForm.discount_type"
                                                :aria-label="t('discount_type')"
                                                class="rounded-r-xl border border-l-0 border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
                                            >
                                                <option value="percent">%</option>
                                                <option value="amount">€</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="w-28">
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">{{ t('vat') }}</label>
                                        <select
                                            :value="editVatCustom ? 'custom' : editItemForm.vat_rate"
                                            @change="onEditVatChange($event.target.value)"
                                            class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
                                        >
                                            <option v-for="rate in vatRates" :key="rate.value" :value="rate.value">
                                                {{ rate.value === 'custom' ? rate.label : rate.value + '%' }}
                                            </option>
                                        </select>
                                        <input
                                            v-if="editVatCustom"
                                            v-model.number="editItemForm.vat_rate"
                                            type="number" step="0.01" min="0" max="100"
                                            placeholder="%"
                                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
                                        />
                                    </div>
                                    <div class="flex space-x-2">
                                        <button
                                            type="button"
                                            @click="saveEditItem(item.id)"
                                            :disabled="editItemForm.processing"
                                            class="inline-flex items-center rounded-xl bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-50"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                        </button>
                                        <button
                                            type="button"
                                            @click="cancelEditItem"
                                            class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
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
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M14.77 12.79a.75.75 0 01-1.06-.02L10 8.832 6.29 12.77a.75.75 0 11-1.08-1.04l4.25-4.5a.75.75 0 011.08 0l4.25 4.5a.75.75 0 01-.02 1.06z" clip-rule="evenodd" /></svg>
                                    </button>
                                    <button
                                        type="button"
                                        @click="moveItem(item.id, 'down')"
                                        :disabled="index === invoice.items.length - 1"
                                        class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 disabled:opacity-30 disabled:cursor-not-allowed"
                                        :title="t('move_down')"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
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
                                    <p v-if="parseFloat(item.discount_value) > 0" class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">
                                        {{ t('discount') }} : {{ item.discount_type === 'amount' ? formatCurrency(item.discount_value) + ' €' : parseFloat(item.discount_value) + ' %' }}
                                    </p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-slate-900 dark:text-white mr-2">
                                        <s v-if="parseFloat(item.discount_value) > 0" class="text-slate-400 text-xs mr-1">{{ formatCurrency(item.quantity * item.unit_price) }}</s>
                                        {{ formatCurrency(item.total_ht) }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="startEditItem(item)"
                                        class="p-1 text-slate-400 hover:text-primary-600 dark:hover:text-primary-400"
                                        :title="t('edit')"
                                    >
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" /></svg>
                                    </button>
                                    <button
                                        type="button"
                                        @click="deleteItem(item.id)"
                                        class="p-1 text-slate-400 hover:text-red-600 dark:hover:text-red-400"
                                        :title="t('delete')"
                                    >
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.519.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 01.78.72l.5 6a.75.75 0 01-1.5.12l-.5-6a.75.75 0 01.72-.78zm3.62.72a.75.75 0 10-1.5-.12l-.5 6a.75.75 0 101.5.12l.5-6z" clip-rule="evenodd" /></svg>
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
                                        <ProductAutocomplete
                                            input-id="add-item-title"
                                            v-model="itemForm.title"
                                            :placeholder="t('service_title')"
                                            required
                                            @select="applyProductToItem($event)"
                                        />
                                        <InputError :message="itemForm.errors.title" class="mt-1" />
                                    </div>
                                    <div>
                                        <textarea
                                            v-model="itemForm.description"
                                            rows="2"
                                            class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
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
                                            class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
                                            required
                                        />
                                    </div>
                                    <div class="w-28">
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">{{ t('unit') }}</label>
                                        <select
                                            v-model="itemForm.unit"
                                            class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
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
                                            class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
                                            required
                                        />
                                    </div>
                                    <div class="w-32">
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">{{ t('discount') }}</label>
                                        <div class="flex">
                                            <input
                                                v-model.number="itemForm.discount_value"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                :max="itemForm.discount_type === 'percent' ? 100 : null"
                                                class="block w-full rounded-l-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
                                                placeholder="0"
                                            />
                                            <select
                                                v-model="itemForm.discount_type"
                                                :aria-label="t('discount_type')"
                                                class="rounded-r-xl border border-l-0 border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
                                            >
                                                <option value="percent">%</option>
                                                <option value="amount">€</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="w-28">
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">{{ t('vat') }}</label>
                                        <select
                                            :value="itemVatCustom ? 'custom' : itemForm.vat_rate"
                                            @change="onItemVatChange($event.target.value)"
                                            class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
                                        >
                                            <option v-for="rate in vatRates" :key="rate.value" :value="rate.value">
                                                {{ rate.value === 'custom' ? rate.label : rate.value + '%' }}
                                            </option>
                                        </select>
                                        <input
                                            v-if="itemVatCustom"
                                            v-model.number="itemForm.vat_rate"
                                            type="number" step="0.01" min="0" max="100"
                                            placeholder="%"
                                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
                                        />
                                    </div>
                                    <button
                                        type="submit"
                                        :disabled="itemForm.processing"
                                        class="inline-flex items-center rounded-xl bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 disabled:opacity-50"
                                    >
                                        <svg class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
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
                <div class="overflow-hidden rounded-xl bg-white shadow dark:bg-surface-card sticky top-20">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('summary') }}</h2>
                    </div>
                    <div class="px-6 py-4 space-y-3">
                        <!-- Sous-total (affiché seulement s'il y a des remises globales) -->
                        <div v-if="(invoice.discounts || []).length > 0" class="flex justify-between text-sm">
                            <span class="text-slate-500 dark:text-slate-400">{{ t('subtotal_ht') }}</span>
                            <span class="font-medium text-slate-900 dark:text-white">{{ formatCurrency(totals.ht) }}</span>
                        </div>

                        <!-- Lignes de remise existantes -->
                        <div
                            v-for="discount in invoice.discounts || []"
                            :key="discount.id"
                            class="flex justify-between items-center text-sm text-amber-700 dark:text-amber-400"
                        >
                            <span class="flex items-center gap-1 min-w-0">
                                <button type="button" @click="removeDiscount(discount.id)" class="text-slate-400 hover:text-red-500 shrink-0" :title="t('delete')">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
                                </button>
                                <span class="truncate">{{ discount.label || t('discount') }}<template v-if="discount.type === 'percent'"> ({{ parseFloat(discount.value) }} %)</template></span>
                            </span>
                            <span class="font-medium whitespace-nowrap">− {{ formatCurrency(discountAmount(discount, totals.ht)) }}</span>
                        </div>

                        <!-- Ajout d'une remise globale -->
                        <form @submit.prevent="addDiscount" class="flex items-center gap-1">
                            <input v-model="discountForm.label" type="text" :placeholder="t('discount_label_placeholder')" class="min-w-0 flex-1 rounded-lg border-gray-300 shadow-sm text-xs focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                            <input v-model.number="discountForm.value" type="number" step="0.01" min="0" :max="discountForm.type === 'percent' ? 100 : null" placeholder="0" class="w-14 rounded-l-lg border-gray-300 shadow-sm text-xs focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                            <select v-model="discountForm.type" :aria-label="t('discount_type')" class="rounded-r-lg border border-l-0 border-gray-300 shadow-sm text-xs focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                <option value="percent">%</option>
                                <option value="amount">€</option>
                            </select>
                            <button type="submit" :disabled="!discountForm.value || discountForm.processing" class="rounded-lg bg-primary-600 px-2 py-1.5 text-white shadow-sm hover:bg-primary-500 disabled:opacity-40" :title="t('add')">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                            </button>
                        </form>

                        <!-- Totaux (backend, TVA ventilée) -->
                        <div class="flex justify-between text-sm border-t border-gray-200 dark:border-gray-700 pt-3">
                            <span class="text-slate-500 dark:text-slate-400">{{ t('total_ht') }}</span>
                            <span class="font-medium text-slate-900 dark:text-white">{{ formatCurrency(invoice.total_ht) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500 dark:text-slate-400">{{ t('vat') }}</span>
                            <span class="font-medium text-slate-900 dark:text-white">{{ formatCurrency(invoice.total_vat) }}</span>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                            <div class="flex justify-between">
                                <span class="text-base font-medium text-slate-900 dark:text-white">{{ t('total_ttc') }}</span>
                                <span class="text-lg font-bold text-slate-900 dark:text-white">{{ formatCurrency(invoice.total_ttc) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 dark:bg-gray-800/50">
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

                <div class="relative bg-white dark:bg-surface-card rounded-xl shadow-sm w-full max-w-5xl max-h-[90vh] flex flex-col">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-slate-900 dark:text-white">
                            {{ t('invoice_preview') }}
                        </h3>
                        <div class="flex items-center space-x-2">
                            <!-- Language selector -->
                            <div class="flex items-center border border-gray-300 dark:border-gray-700 rounded-xl overflow-hidden">
                                <button
                                    v-for="lang in pdfLanguages"
                                    :key="lang.value"
                                    type="button"
                                    @click="changePdfLanguage(lang.value)"
                                    :title="lang.label"
                                    class="px-2 py-1.5 text-base transition-colors"
                                    :class="pdfLocale === lang.value
                                        ? 'bg-primary-100 dark:bg-primary-900'
                                        : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800'"
                                >
                                    <FlagIcon :code="lang.value" class="w-5 h-3.5" />
                                </button>
                            </div>
                            <a
                                :href="pdfUrl"
                                target="_blank"
                                class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300"
                            >
                                <svg class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z" /><path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" /></svg>
                                PDF
                            </a>
                            <button
                                type="button"
                                @click="loadPreview"
                                :disabled="loadingPreview"
                                class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300 disabled:opacity-50"
                            >
                                <svg class="h-4 w-4 mr-1" :class="{ 'animate-spin': loadingPreview }" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H4.598a.75.75 0 00-.75.75v3.634a.75.75 0 001.5 0v-2.033l.312.311a7 7 0 0011.712-3.138.75.75 0 00-1.449-.39zm-1.873-7.263a7 7 0 00-11.712 3.138.75.75 0 001.45.388 5.5 5.5 0 019.2-2.466l.312.311H10.256a.75.75 0 000 1.5h3.634a.75.75 0 00.75-.75V2.648a.75.75 0 00-1.5 0v2.033l-.312-.311-.389-.209z" clip-rule="evenodd" /></svg>
                                {{ t('refresh') }}
                            </button>
                            <button
                                type="button"
                                @click="showPreviewModal = false"
                                class="text-slate-400 hover:text-slate-500 dark:hover:text-slate-300"
                            >
                                <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal body -->
                    <div class="flex-1 overflow-auto p-6 bg-slate-100 dark:bg-surface-dark">
                        <div v-if="loadingPreview" class="flex items-center justify-center h-96">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
                        </div>
                        <iframe
                            v-else
                            :srcdoc="previewHtml"
                            class="bg-white shadow-lg mx-auto block border-0"
                            style="width: 210mm; height: 297mm; color-scheme: light;"
                        ></iframe>
                    </div>

                    <!-- Modal footer -->
                    <div class="flex items-center justify-end px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-800/50">
                        <p class="text-sm text-slate-500 dark:text-slate-400 mr-auto">
                            {{ t('preview_finalize_note') }}
                        </p>
                        <button
                            type="button"
                            @click="showPreviewModal = false"
                            class="inline-flex items-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-gray-50 dark:bg-slate-600 dark:text-white dark:ring-slate-500"
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

                <div class="inline-block transform overflow-hidden rounded-xl bg-white text-left align-bottom shadow-sm transition-all dark:bg-surface-card sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                    <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-yellow-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
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
                    <div class="bg-slate-50 px-4 py-3 dark:bg-gray-800 sm:flex sm:flex-row-reverse sm:px-6">
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
                            class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-gray-50 dark:bg-slate-600 dark:text-white dark:ring-slate-500 sm:mt-0 sm:w-auto"
                        >
                            {{ t('cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
