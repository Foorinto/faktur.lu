<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import BillingNav from '@/Components/BillingNav.vue';
import InputError from '@/Components/InputError.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import InputLabel from '@/Components/InputLabel.vue';
import NumberingHintBanner from '@/Components/Numbering/NumberingHintBanner.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import VatScenarioIndicator from '@/Components/VatScenarioIndicator.vue';
import ProductAutocomplete from '@/Components/ProductAutocomplete.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useTour } from '@/Composables/useTour';

const { t } = useTranslations();
const { startTour } = useTour();

onMounted(() => setTimeout(() => startTour('invoiceCreate'), 600));

const props = defineProps({
    clients: Array,
    vatRates: Array,
    units: Array,
    defaultClientId: [String, Number],
    isVatExempt: Boolean,
    vatScenarios: Object,
    defaultVatRate: {
        type: Number,
        default: 17,
    },
    vatMentionOptions: Array,
    defaultVatMention: String,
    suggestedVatMention: String,
    defaultInvoiceFooter: String,
    numberingHint: { type: Object, default: null },
    businessSettingsMissing: Boolean,
});

// Calculate effective default VAT rate based on exemption status and country
const effectiveDefaultVatRate = computed(() => {
    if (props.isVatExempt) return 0;

    // If a client is selected and has a default rate, use it
    if (selectedClient.value?.default_vat_rate !== null && selectedClient.value?.default_vat_rate !== undefined) {
        return parseFloat(selectedClient.value.default_vat_rate);
    }

    // Otherwise use the business's country default
    return props.defaultVatRate;
});

// Get selected client's VAT scenario
const selectedClient = computed(() => {
    return props.clients?.find(c => c.id === form.client_id);
});

const clientVatScenario = computed(() => {
    return selectedClient.value?.vat_scenario || null;
});

// FEAT-108: remise permanente du client, annoncée dès la sélection.
// Purement informatif : c'est le serveur qui la recopie sur le document, pour
// que tous les chemins de création (interface, API, conversion d'un devis)
// suivent la même règle. Elle reste retirable sur le brouillon.
const clientDiscountNotice = computed(() => {
    const value = Number(selectedClient.value?.default_discount_value ?? 0);

    if (!(value > 0)) {
        return null;
    }

    return selectedClient.value.default_discount_type === 'amount'
        ? `${formatCurrency(value)} €`
        : `${value} %`;
});

const form = useForm({
    client_id: props.defaultClientId || '',
    title: '',
    issued_at: new Date().toISOString().split('T')[0],
    due_at: '',
    notes: '',
    currency: 'EUR',
    vat_mention: props.suggestedVatMention || '',
    custom_vat_mention: '',
    footer_message: '',
    retention_guarantee_rate: null,
    retention_release_date: '',
    items: [],
});

// Track custom VAT rates per item
const customVatRates = ref({});

// Retention guarantee toggle
const showRetention = ref(false);

const addItem = () => {
    const itemIndex = form.items.length;
    const client = form.client_id ? props.clients.find(c => c.id === form.client_id) : null;
    const defaultPrice = client?.default_hourly_rate ? parseFloat(client.default_hourly_rate) : 0;

    form.items.push({
        title: '',
        description: '',
        quantity: 1,
        unit: 'hour',
        unit_price: defaultPrice,
        discount_type: 'percent',
        discount_value: 0,
        vat_rate: effectiveDefaultVatRate.value,
        vat_rate_select: effectiveDefaultVatRate.value,
    });
    customVatRates.value[itemIndex] = '';
};

// Live net line total after discount (mirrors backend InvoiceItem::applyLineDiscount)
const lineNetHt = (item) => {
    const gross = (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
    const dv = parseFloat(item.discount_value) || 0;
    let discount = 0;
    if (dv > 0) {
        discount = item.discount_type === 'amount'
            ? Math.min(dv, gross)
            : gross * Math.min(dv, 100) / 100;
    }
    return Math.max(0, gross - discount);
};

const formatCurrency = (value) =>
    new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value || 0);

// Handle VAT rate selection change
const handleVatRateChange = (index, value) => {
    if (value === 'custom') {
        // Keep track that this item uses custom rate
        form.items[index].vat_rate_select = 'custom';
        form.items[index].vat_rate = customVatRates.value[index] || 0;
    } else {
        form.items[index].vat_rate_select = value;
        form.items[index].vat_rate = value;
    }
};

// Handle custom VAT rate input change
const handleCustomVatRateChange = (index, value) => {
    customVatRates.value[index] = value;
    if (form.items[index].vat_rate_select === 'custom') {
        form.items[index].vat_rate = parseFloat(value) || 0;
    }
};

// Pre-fill a line from a selected catalogue product (FEAT-095).
const applyProduct = (index, product) => {
    const item = form.items[index];
    item.title = product.designation;
    if (product.description) {
        item.description = product.description;
    }
    item.unit_price = Number(product.unit_price_ht);
    if (product.unit) {
        item.unit = product.unit;
    }
    // Le taux du catalogue ne doit pas écraser un taux imposé par le contexte :
    // franchise de TVA du vendeur, ou client étranger (autoliquidation/export).
    const scenario = clientVatScenario.value;
    const isForeignScenario = !!(scenario && ['reverse_charge', 'export'].includes(scenario.mention));
    const rate = (props.isVatExempt || isForeignScenario) ? 0 : Number(product.vat_rate);
    item.vat_rate = rate;
    const isStandard = props.vatRates.some((r) => Number(r.value) === rate);
    if (isStandard) {
        item.vat_rate_select = rate;
    } else {
        item.vat_rate_select = 'custom';
        customVatRates.value[index] = rate;
    }
};

// Watch for client changes to update default VAT rate on items and suggest VAT mention
// FEAT-100: adapte automatiquement la TVA au scénario du client (autoliquidation/export)
// + affiche une notice quand le client est à l'étranger.
const vatAdjustedNotice = ref(null);
const vatCheckNotice = ref(null); // franchise + client étranger → inviter à vérifier
const EU_COUNTRIES = ['AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK'];

watch(() => form.client_id, (newClientId) => {
    if (!newClientId) {
        vatAdjustedNotice.value = null;
        vatCheckNotice.value = null;
        return;
    }
    const client = props.clients.find(c => c.id === newClientId);
    const scenario = client?.vat_scenario;
    const isForeignScenario = !!(scenario && ['reverse_charge', 'export'].includes(scenario.mention));

    // Taux de TVA à appliquer aux lignes (hors lignes en taux personnalisé)
    let appliedRate = null;
    if (isForeignScenario) {
        appliedRate = 0; // autoliquidation intra-UE ou export = hors TVA
    } else if (client?.default_vat_rate !== null && client?.default_vat_rate !== undefined) {
        appliedRate = parseFloat(client.default_vat_rate);
    } else if (scenario && scenario.rate !== null && scenario.rate !== undefined) {
        appliedRate = Number(scenario.rate);
    }
    if (appliedRate !== null) {
        form.items.forEach((item) => {
            if (item.vat_rate_select !== 'custom') {
                item.vat_rate = appliedRate;
                item.vat_rate_select = appliedRate;
            }
        });
    }

    // Applique la mention TVA du scénario (s'adapte au client sélectionné)
    if (scenario) {
        form.vat_mention = scenario.mention || 'none';
    }

    // Pré-remplit le tarif horaire sur les lignes en heures sans prix
    if (client?.default_hourly_rate) {
        form.items.forEach((item) => {
            if (item.unit === 'hour' && (!item.unit_price || item.unit_price == 0)) {
                item.unit_price = parseFloat(client.default_hourly_rate);
            }
        });
    }

    // Notice quand la TVA a été adaptée automatiquement (vendeur assujetti)
    vatAdjustedNotice.value = isForeignScenario ? { mention: scenario.mention } : null;

    // Vendeur en franchise + client étranger : la franchise reste appliquée, mais la
    // règle transfrontalière peut différer → on invite à vérifier la mention.
    const isForeignCountry = client?.country_code && client.country_code !== 'LU';
    vatCheckNotice.value = (scenario && scenario.mention === 'franchise' && isForeignCountry)
        ? { region: EU_COUNTRIES.includes(client.country_code) ? 'eu' : 'foreign' }
        : null;
});

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const submit = () => {
    form.post(route('invoices.store'));
};

// Add first item by default
if (form.items.length === 0) {
    addItem();
}
</script>

<template>
    <Head :title="t('new_invoice')" />

    <AppLayout>
        <template #header>
            <Link
                :href="route('invoices.index')"
                class="text-slate-400 hover:text-slate-500 dark:text-slate-500 dark:hover:text-slate-400"
            >
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" /></svg>
            </Link>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('new_invoice') }}
            </h1>
        </template>

        <BillingNav class="mb-6" />

        <!-- Sans paramètres d'entreprise, la finalisation échouera en fin de parcours :
             on prévient dès la saisie plutôt qu'après tout le travail. -->
        <div
            v-if="businessSettingsMissing"
            class="mb-6 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-700 dark:bg-amber-900/30"
        >
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-amber-900 dark:text-amber-100">
                    {{ t('business_settings_required_notice') }}
                </p>
                <Link
                    :href="route('settings.business.edit')"
                    class="w-full shrink-0 rounded-lg bg-amber-600 px-4 py-1.5 text-center text-sm font-semibold text-white transition-colors hover:bg-amber-700 sm:w-auto"
                >
                    {{ t('business_settings_required_cta') }}
                </Link>
            </div>
        </div>

        <NumberingHintBanner
            v-if="numberingHint && numberingHint.preview_number"
            :preview-number="numberingHint.preview_number"
            document-type="invoice"
        />

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Client selection -->
            <div class="overflow-hidden rounded-xl bg-white shadow-sm border border-gray-200 dark:bg-surface-card dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('client') }}</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div data-tour="invoice-form-client">
                            <InputLabel for="client_id" :value="t('client')" />
                            <select
                                id="client_id"
                                v-model="form.client_id"
                                class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                required
                            >
                                <option value="">{{ t('select_client') }}</option>
                                <optgroup :label="t('crm.status_active') || 'Actifs'">
                                    <option v-for="client in clients.filter(c => c.status === 'active')" :key="client.id" :value="client.id">
                                        {{ client.name }}
                                    </option>
                                </optgroup>
                                <optgroup v-if="clients.some(c => c.status !== 'active')" :label="t('other') || 'Autres'">
                                    <option v-for="client in clients.filter(c => c.status !== 'active')" :key="client.id" :value="client.id">
                                        {{ client.name }}
                                    </option>
                                </optgroup>
                            </select>
                            <InputError :message="form.errors.client_id" class="mt-2" />
                            <!-- VAT Scenario indicator -->
                            <div v-if="clientVatScenario" class="mt-2">
                                <VatScenarioIndicator :scenario="clientVatScenario" size="sm" />
                            </div>
                            <!-- FEAT-108: remise permanente négociée avec ce client -->
                            <div v-if="clientDiscountNotice" class="mt-2 flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm dark:border-emerald-900/40 dark:bg-emerald-900/20">
                                <span aria-hidden="true">🏷️</span>
                                <p class="flex-1 text-emerald-800 dark:text-emerald-200">
                                    {{ t('client_discount_notice', { discount: clientDiscountNotice }) }}
                                </p>
                            </div>
                            <!-- FEAT-100: notice TVA adaptée automatiquement (client à l'étranger) -->
                            <div v-if="vatAdjustedNotice" class="mt-2 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm dark:border-amber-900/40 dark:bg-amber-900/20">
                                <span aria-hidden="true">ℹ️</span>
                                <div class="flex-1">
                                    <p class="font-medium text-amber-800 dark:text-amber-200">{{ t('vat_auto_adjusted_title') }}</p>
                                    <p class="text-amber-700 dark:text-amber-300">{{ vatAdjustedNotice.mention === 'export' ? t('vat_auto_export') : t('vat_auto_reverse_charge') }}</p>
                                </div>
                                <button type="button" class="text-amber-500 hover:text-amber-700" @click="vatAdjustedNotice = null">✕</button>
                            </div>
                            <!-- FEAT-100: avertissement (franchise + client étranger → vérifier la mention) -->
                            <div v-if="vatCheckNotice" class="mt-2 flex items-start gap-2 rounded-xl border border-orange-200 bg-orange-50 p-3 text-sm dark:border-orange-900/40 dark:bg-orange-900/20">
                                <span aria-hidden="true">⚠️</span>
                                <div class="flex-1">
                                    <p class="font-medium text-orange-800 dark:text-orange-200">{{ t('vat_check_title') }}</p>
                                    <p class="text-orange-700 dark:text-orange-300">{{ vatCheckNotice.region === 'eu' ? t('vat_check_eu') : t('vat_check_foreign') }}</p>
                                </div>
                                <button type="button" class="text-orange-500 hover:text-orange-700" @click="vatCheckNotice = null">✕</button>
                            </div>
                        </div>

                        <div>
                            <InputLabel for="title" :value="t('title_optional')" />
                            <input
                                id="title"
                                v-model="form.title"
                                type="text"
                                class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                :placeholder="t('example_placeholder')"
                            />
                            <InputError :message="form.errors.title" class="mt-2" />
                        </div>
                    </div>

                    <div data-tour="invoice-form-dates" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="issued_at" :value="t('issue_date')" />
                            <input
                                id="issued_at"
                                v-model="form.issued_at"
                                type="date"
                                class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                            <InputError :message="form.errors.issued_at" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="due_at" :value="t('due_date_optional')" />
                            <input
                                id="due_at"
                                v-model="form.due_at"
                                type="date"
                                class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                {{ t('default_30_days_after') }}
                            </p>
                            <InputError :message="form.errors.due_at" class="mt-2" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice items -->
            <div data-tour="invoice-form-items" class="overflow-hidden rounded-xl bg-white shadow-sm border border-gray-200 dark:bg-surface-card dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('invoice_lines') }}</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="space-y-4">
                        <div
                            v-for="(item, index) in form.items"
                            :key="index"
                            class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 space-y-3"
                        >
                            <div class="flex flex-wrap gap-4 items-end">
                                <div class="flex-1 min-w-[200px]">
                                    <InputLabel :for="`item-${index}-title`" :value="t('title_required')" />
                                    <ProductAutocomplete
                                        :input-id="`item-${index}-title`"
                                        v-model="item.title"
                                        :placeholder="t('service_title_placeholder')"
                                        required
                                        @select="applyProduct(index, $event)"
                                    />
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-4 items-end">
                                <div class="flex-1 min-w-[200px]">
                                    <InputLabel :for="`item-${index}-description`" :value="t('description_optional')" />
                                    <textarea
                                        :id="`item-${index}-description`"
                                        v-model="item.description"
                                        rows="2"
                                        class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                        :placeholder="t('additional_details')"
                                    ></textarea>
                                </div>

                            <div class="w-24">
                                <InputLabel :for="`item-${index}-quantity`" :value="t('qty')" />
                                <input
                                    :id="`item-${index}-quantity`"
                                    v-model.number="item.quantity"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    required
                                />
                            </div>

                            <div class="w-32">
                                <InputLabel :for="`item-${index}-unit`" :value="t('unit')" />
                                <select
                                    :id="`item-${index}-unit`"
                                    v-model="item.unit"
                                    class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                >
                                    <option value="">-</option>
                                    <option v-for="unit in units" :key="unit.value" :value="unit.value">
                                        {{ unit.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="w-32">
                                <InputLabel :for="`item-${index}-unit_price`" :value="t('price_ht')" />
                                <input
                                    :id="`item-${index}-unit_price`"
                                    v-model.number="item.unit_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    required
                                />
                            </div>

                            <div class="w-40">
                                <InputLabel :for="`item-${index}-discount`" :value="t('discount')" />
                                <div class="mt-1 flex">
                                    <input
                                        :id="`item-${index}-discount`"
                                        v-model.number="item.discount_value"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        :max="item.discount_type === 'percent' ? 100 : null"
                                        class="block w-full rounded-l-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                        placeholder="0"
                                    />
                                    <select
                                        v-model="item.discount_type"
                                        :aria-label="t('discount_type')"
                                        class="rounded-r-xl border border-l-0 border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    >
                                        <option value="percent">%</option>
                                        <option value="amount">€</option>
                                    </select>
                                </div>
                            </div>

                            <div class="w-32">
                                <InputLabel :for="`item-${index}-vat_rate`" :value="t('vat')" />
                                <select
                                    :id="`item-${index}-vat_rate`"
                                    :value="item.vat_rate_select ?? item.vat_rate"
                                    @change="handleVatRateChange(index, $event.target.value === 'custom' ? 'custom' : parseFloat($event.target.value))"
                                    class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    required
                                >
                                    <option v-for="rate in vatRates" :key="rate.value" :value="rate.value">
                                        {{ rate.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Custom VAT rate input -->
                            <div v-if="item.vat_rate_select === 'custom'" class="w-24">
                                <InputLabel :for="`item-${index}-custom_vat_rate`" value="%" />
                                <input
                                    :id="`item-${index}-custom_vat_rate`"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    :value="customVatRates[index]"
                                    @input="handleCustomVatRateChange(index, $event.target.value)"
                                    class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    placeholder="Ex: 12"
                                />
                            </div>

                            <button
                                v-if="form.items.length > 1"
                                type="button"
                                @click="removeItem(index)"
                                class="p-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.519.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 01.78.72l.5 6a.75.75 0 01-1.5.12l-.5-6a.75.75 0 01.72-.78zm3.62.72a.75.75 0 10-1.5-.12l-.5 6a.75.75 0 101.5.12l.5-6z" clip-rule="evenodd" /></svg>
                            </button>
                        </div>
                        <div class="flex justify-end text-sm text-slate-600 dark:text-slate-300">
                            <span>{{ t('line_total_ht') }} :
                                <s v-if="parseFloat(item.discount_value) > 0" class="text-slate-400 text-xs mr-1">{{ formatCurrency((parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0)) }} €</s>
                                <strong class="ml-1">{{ formatCurrency(lineNetHt(item)) }} €</strong>
                            </span>
                        </div>
                        </div>

                        <button
                            type="button"
                            @click="addItem"
                            class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300 dark:hover:bg-gray-800"
                        >
                            <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                            {{ t('add_line') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Notes & Options -->
            <div class="overflow-hidden rounded-xl bg-white shadow-sm border border-gray-200 dark:bg-surface-card dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('notes_optional') }}</h2>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <InputLabel for="notes" :value="t('notes')" />
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="3"
                            class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            :placeholder="t('notes_placeholder')"
                        ></textarea>
                        <InputError :message="form.errors.notes" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="vat_mention" :value="t('vat_mention_optional')" />
                        <select
                            id="vat_mention"
                            v-model="form.vat_mention"
                            class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
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

                    <div v-if="form.vat_mention === 'other'">
                        <InputLabel for="custom_vat_mention" :value="t('custom_vat_mention_label')" />
                        <textarea
                            id="custom_vat_mention"
                            v-model="form.custom_vat_mention"
                            rows="2"
                            class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            :placeholder="t('custom_vat_mention_placeholder')"
                        ></textarea>
                    </div>

                    <div>
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

                    <!-- Retenue de garantie (BTP) -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                        <div class="flex items-center gap-2 mb-3">
                            <input
                                id="enable_retention"
                                type="checkbox"
                                v-model="showRetention"
                                class="rounded border-gray-300 text-primary-500 focus:ring-primary-500"
                            />
                            <label for="enable_retention" class="text-sm font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
                                {{ t('retention_section_title') }}
                            </label>
                        </div>
                        <div v-if="showRetention" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="retention_rate" :value="t('retention_percentage_label')" />
                                <input
                                    id="retention_rate"
                                    v-model="form.retention_guarantee_rate"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    placeholder="5"
                                    class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />
                            </div>
                            <div>
                                <InputLabel for="retention_release" :value="t('retention_release_date_label')" />
                                <input
                                    id="retention_release"
                                    v-model="form.retention_release_date"
                                    type="date"
                                    class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />
                                <p class="mt-1 text-xs text-slate-500">{{ t('retention_release_date_hint') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Link
                    :href="route('invoices.index')"
                    class="inline-flex items-center justify-center w-full sm:w-auto rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300 dark:hover:bg-gray-800"
                >
                    {{ t('cancel') }}
                </Link>
                <PrimaryButton data-tour="invoice-form-submit" :disabled="form.processing" class="w-full sm:w-auto justify-center">
                    <span v-if="form.processing">{{ t('creating') }}</span>
                    <span v-else>{{ t('create_draft') }}</span>
                </PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
