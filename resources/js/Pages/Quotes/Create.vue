<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import BillingNav from '@/Components/BillingNav.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import NumberingHintBanner from '@/Components/Numbering/NumberingHintBanner.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ProductAutocomplete from '@/Components/ProductAutocomplete.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import { computed, ref, watch, onMounted } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useTour } from '@/Composables/useTour';
import PriceHtTtcFields from '@/Components/PriceHtTtcFields.vue';

const { t } = useTranslations();
const { startTour } = useTour();

onMounted(() => setTimeout(() => startTour('quoteCreate'), 600));

const props = defineProps({
    clients: Array,
    vatRates: Array,
    units: Array,
    defaultClientId: [String, Number],
    isVatExempt: Boolean,
    defaultVatRate: {
        type: Number,
        default: 17,
    },
    vatMentionOptions: Array,
    defaultVatMention: String,
    suggestedVatMention: String,
    defaultQuoteFooter: String,
    numberingHint: { type: Object, default: null },
});

// Get selected client
const selectedClient = computed(() => {
    return props.clients?.find(c => c.id === form.client_id);
});

// FEAT-108: remise permanente du client, annoncée dès la sélection. Le serveur
// la recopie sur le devis ; elle reste retirable tant qu'il est en brouillon.
const clientDiscountNotice = computed(() => {
    const value = Number(selectedClient.value?.default_discount_value ?? 0);

    if (!(value > 0)) {
        return null;
    }

    return selectedClient.value.default_discount_type === 'amount'
        ? `${formatCurrency(value)} €`
        : `${value} %`;
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

const form = useForm({
    client_id: props.defaultClientId || '',
    valid_until: '',
    deposit_type: 'percent',
    deposit_value: null,
    notes: '',
    currency: 'EUR',
    vat_mention: props.suggestedVatMention || '',
    custom_vat_mention: '',
    footer_message: '',
    items: [],
});

// Track custom VAT rates per item
const customVatRates = ref({});

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
    // Le compte suit l'article : c'est lui qui distingue un frais
    // refacturé d'une vente, et il doit être figé sur la ligne — reclasser
    // l'article plus tard ne doit pas réécrire une facture déjà émise.
    item.pcn_account = product.pcn_account ?? null;
    if (product.unit) {
        item.unit = product.unit;
    }
    // En franchise de TVA, le taux du catalogue ne doit pas écraser le 0 %.
    const rate = props.isVatExempt ? 0 : Number(product.vat_rate);
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
watch(() => form.client_id, (newClientId) => {
    if (newClientId) {
        const client = props.clients.find(c => c.id === newClientId);
        if (client?.default_vat_rate !== null && client?.default_vat_rate !== undefined) {
            form.items.forEach((item) => {
                if (item.vat_rate_select !== 'custom') {
                    const newRate = parseFloat(client.default_vat_rate);
                    item.vat_rate = newRate;
                    item.vat_rate_select = newRate;
                }
            });
        }

        // Pre-fill hourly rate on items with unit 'hour' and no price set
        if (client?.default_hourly_rate) {
            form.items.forEach((item) => {
                if (item.unit === 'hour' && (!item.unit_price || item.unit_price == 0)) {
                    item.unit_price = parseFloat(client.default_hourly_rate);
                }
            });
        }

        // Auto-suggest VAT mention based on client type and country
        // Only suggest if no mention is already set
        if (!form.vat_mention && client) {
            // Check if it's an intra-EU B2B client
            const isIntraEu = client.country_code && client.country_code !== 'LU' && ['AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK'].includes(client.country_code);
            if (client.type === 'b2b' && client.vat_number && isIntraEu) {
                form.vat_mention = 'reverse_charge';
            }
        }
    }
});

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const submit = () => {
    form.post(route('quotes.store'));
};

// Add first item by default
if (form.items.length === 0) {
    addItem();
}
</script>

<template>
    <Head :title="t('new_quote')" />

    <AppLayout>
        <template #header>
            <Link
                :href="route('quotes.index')"
                class="text-slate-400 hover:text-slate-500 dark:text-slate-500 dark:hover:text-slate-400"
            >
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                </svg>
            </Link>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('new_quote') }}
            </h1>
        </template>

        <BillingNav class="mb-6" />

        <NumberingHintBanner
            v-if="numberingHint && numberingHint.preview_number"
            :preview-number="numberingHint.preview_number"
            document-type="quote"
        />

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Client selection -->
            <div class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-surface-card">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">Client</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div data-tour="quote-form-client">
                            <div class="flex items-center justify-between">
                                <InputLabel for="client_id" value="Client" />
                                <!-- Ouvre la fiche du client sélectionné, dans un nouvel onglet
                                     pour ne pas perdre la saisie en cours. -->
                                <a
                                    v-if="selectedClient?.id"
                                    :href="route('clients.show', selectedClient.id)"
                                    target="_blank"
                                    rel="noopener"
                                    class="text-slate-400 hover:text-primary-600 dark:hover:text-primary-400"
                                    :title="t('view_client')"
                                >
                                    <span class="sr-only">{{ t('view_client') }}</span>
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                            </div>
                            <select
                                id="client_id"
                                v-model="form.client_id"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
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
                            <!-- FEAT-108: remise permanente négociée avec ce client -->
                            <div v-if="clientDiscountNotice" class="mt-2 flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm dark:border-emerald-900/40 dark:bg-emerald-900/20">
                                <span aria-hidden="true">🏷️</span>
                                <p class="flex-1 text-emerald-800 dark:text-emerald-200">
                                    {{ t('client_discount_notice', { discount: clientDiscountNotice }) }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <InputLabel for="valid_until" :value="t('valid_until_optional')" />
                            <input
                                id="valid_until"
                                v-model="form.valid_until"
                                type="date"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                {{ t('default_30_days') }}
                            </p>
                            <InputError :message="form.errors.valid_until" class="mt-2" />
                        </div>

                        <!--
                            Acompte demandé à la commande.
                            ⚠️ Une demande, pas un encaissement : le total du
                            devis ne bouge pas. Le versement réel s'enregistre
                            sur la facture, une fois émise.
                        -->
                        <div>
                            <InputLabel :value="t('deposit_requested_label')" />
                            <div class="mt-1 flex gap-2">
                                <input
                                    v-model="form.deposit_value"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    :placeholder="t('deposit_none')"
                                    class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />
                                <select
                                    v-model="form.deposit_type"
                                    class="rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                >
                                    <option value="percent">%</option>
                                    <option value="amount">€</option>
                                </select>
                            </div>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                {{ t('deposit_requested_help') }}
                            </p>
                            <InputError :message="form.errors.deposit_value" class="mt-2" />
                        </div>

                    </div>
                </div>
            </div>

            <!-- Quote items -->
            <div data-tour="quote-form-items" class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-surface-card">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('quote_lines') }}</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="space-y-4">
                        <div
                            v-for="(item, index) in form.items"
                            :key="index"
                            class="p-4 rounded-2xl border border-gray-200 dark:border-gray-700"
                        >
                            <div class="space-y-3">
                                <div class="flex-1">
                                    <InputLabel :for="`item-${index}-title`" :value="t('title')" />
                                    <ProductAutocomplete
                                        :input-id="`item-${index}-title`"
                                        v-model="item.title"
                                        :placeholder="t('service_title_placeholder')"
                                        required
                                        @select="applyProduct(index, $event)"
                                    />
                                </div>

                                <div class="flex-1">
                                    <InputLabel :for="`item-${index}-description`" :value="t('description_optional')" />
                                    <textarea
                                        :id="`item-${index}-description`"
                                        v-model="item.description"
                                        rows="2"
                                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                        :placeholder="t('detailed_description_placeholder')"
                                    ></textarea>
                                </div>

                                <div class="flex flex-wrap gap-4 items-end">
                                    <div class="w-24">
                                        <InputLabel :for="`item-${index}-quantity`" :value="t('qty')" />
                                        <input
                                            :id="`item-${index}-quantity`"
                                            v-model.number="item.quantity"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                            required
                                        />
                                    </div>

                                    <div class="w-32">
                                        <InputLabel :for="`item-${index}-unit`" :value="t('unit')" />
                                        <select
                                            :id="`item-${index}-unit`"
                                            v-model="item.unit"
                                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                        >
                                            <option value="">-</option>
                                            <option v-for="unit in units" :key="unit.value" :value="unit.value">
                                                {{ unit.label }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="w-56">
                                        <PriceHtTtcFields
                                            v-model="item.unit_price"
                                            :vat-rate="item.vat_rate"
                                            :input-id="`item-${index}-unit_price`"
                                            input-class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
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
                                                class="block w-full rounded-l-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                                placeholder="0"
                                            />
                                            <select
                                                v-model="item.discount_type"
                                                :aria-label="t('discount_type')"
                                                class="rounded-r-xl border border-l-0 border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                            >
                                                <option value="percent">%</option>
                                                <option value="amount">€</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="w-32">
                                        <InputLabel :for="`item-${index}-vat_rate`" :value="t('vat')" />
                                        <select
                                            v-if="!isVatExempt"
                                            :id="`item-${index}-vat_rate`"
                                            :value="item.vat_rate_select ?? item.vat_rate"
                                            @change="handleVatRateChange(index, $event.target.value === 'custom' ? 'custom' : parseFloat($event.target.value))"
                                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                            required
                                        >
                                            <option v-for="rate in vatRates" :key="rate.value" :value="rate.value">
                                                {{ rate.label }}
                                            </option>
                                        </select>
                                        <div
                                            v-else
                                            class="mt-1 block w-full rounded-xl border border-gray-300 bg-slate-100 px-3 py-2 text-slate-500 dark:border-gray-700 dark:bg-slate-600 dark:text-slate-400"
                                        >
                                            {{ t('vat_rates.exempt') }}
                                        </div>
                                    </div>

                                    <!-- Custom VAT rate input -->
                                    <div v-if="item.vat_rate_select === 'custom' && !isVatExempt" class="w-24">
                                        <InputLabel :for="`item-${index}-custom_vat_rate`" value="%" />
                                        <input
                                            :id="`item-${index}-custom_vat_rate`"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            :value="customVatRates[index]"
                                            @input="handleCustomVatRateChange(index, $event.target.value)"
                                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                            placeholder="Ex: 12"
                                        />
                                    </div>

                                    <button
                                        v-if="form.items.length > 1"
                                        type="button"
                                        @click="removeItem(index)"
                                        class="p-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                    >
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.519.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="flex justify-end text-sm text-slate-600 dark:text-slate-300">
                                    <span>{{ t('line_total_ht') }} :
                                        <s v-if="parseFloat(item.discount_value) > 0" class="text-slate-400 text-xs mr-1">{{ formatCurrency((parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0)) }} €</s>
                                        <strong class="ml-1">{{ formatCurrency(lineNetHt(item)) }} €</strong>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="addItem"
                            class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300 dark:hover:bg-gray-800"
                        >
                            <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                            </svg>
                            {{ t('add_line') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Notes & Options -->
            <div class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-surface-card">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('notes_optional') }}</h2>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <InputLabel for="notes" value="Notes / Conditions" />
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="3"
                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            :placeholder="t('special_conditions')"
                        ></textarea>
                        <InputError :message="form.errors.notes" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="vat_mention" value="Mention TVA (optionnel)" />
                        <select
                            id="vat_mention"
                            v-model="form.vat_mention"
                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">{{ t('default_mention_option') }}</option>
                            <option v-for="option in vatMentionOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Cette mention apparaîtra sur le PDF du devis.
                        </p>
                    </div>

                    <div v-if="form.vat_mention === 'other'">
                        <InputLabel for="custom_vat_mention" value="Mention TVA personnalisée" />
                        <textarea
                            id="custom_vat_mention"
                            v-model="form.custom_vat_mention"
                            rows="2"
                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            placeholder="Entrez votre mention TVA personnalisée..."
                        ></textarea>
                    </div>

                    <div>
                        <InputLabel for="footer_message" value="Message de pied de page (optionnel)" />
                        <RichTextEditor use-company-link-color v-model="form.footer_message" class="mt-1" />
                        <p v-if="defaultQuoteFooter" class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Si vide, le message par défaut sera utilisé : "{{ defaultQuoteFooter }}"
                        </p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Link
                    :href="route('quotes.index')"
                    class="inline-flex items-center justify-center w-full sm:w-auto rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300 dark:hover:bg-gray-800"
                >
                    {{ t('cancel') }}
                </Link>
                <PrimaryButton data-tour="quote-form-submit" :disabled="form.processing" class="w-full sm:w-auto justify-center">
                    <span v-if="form.processing">{{ t('creating') }}</span>
                    <span v-else>{{ t('create_quote') }}</span>
                </PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
