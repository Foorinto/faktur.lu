<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import VatScenarioIndicator from '@/Components/VatScenarioIndicator.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

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

const form = useForm({
    client_id: props.defaultClientId || '',
    title: '',
    due_at: '',
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
    form.items.push({
        title: '',
        description: '',
        quantity: 1,
        unit: 'hour',
        unit_price: 0,
        vat_rate: effectiveDefaultVatRate.value,
        vat_rate_select: effectiveDefaultVatRate.value, // For select tracking
    });
    customVatRates.value[itemIndex] = '';
};

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

// Watch for client changes to update default VAT rate on items and suggest VAT mention
watch(() => form.client_id, (newClientId) => {
    if (newClientId) {
        const client = props.clients.find(c => c.id === newClientId);
        if (client?.default_vat_rate !== null && client?.default_vat_rate !== undefined) {
            // Update all items with 0 quantity (new items) to use client's default rate
            form.items.forEach((item, index) => {
                if (item.vat_rate_select !== 'custom') {
                    const newRate = parseFloat(client.default_vat_rate);
                    item.vat_rate = newRate;
                    item.vat_rate_select = newRate;
                }
            });
        }

        // Auto-suggest VAT mention based on client's VAT scenario
        if (!form.vat_mention && client?.vat_scenario?.mention) {
            form.vat_mention = client.vat_scenario.mention;
        }
    }
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
                    {{ t('new_invoice') }}
                </h1>
            </div>
        </template>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Client selection -->
            <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">Client</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="client_id" value="Client" />
                            <select
                                id="client_id"
                                v-model="form.client_id"
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                required
                            >
                                <option value="">{{ t('select_client') }}</option>
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
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                :placeholder="t('example_placeholder')"
                            />
                            <InputError :message="form.errors.title" class="mt-2" />
                        </div>
                    </div>

                    <div class="mt-4">
                        <InputLabel for="due_at" :value="t('due_date_optional')" />
                        <input
                            id="due_at"
                            v-model="form.due_at"
                            type="date"
                            class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white sm:max-w-xs"
                        />
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            {{ t('default_30_days_after') }}
                        </p>
                        <InputError :message="form.errors.due_at" class="mt-2" />
                    </div>
                </div>
            </div>

            <!-- Invoice items -->
            <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('invoice_lines') }}</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="space-y-4">
                        <div
                            v-for="(item, index) in form.items"
                            :key="index"
                            class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 space-y-3"
                        >
                            <div class="flex flex-wrap gap-4 items-end">
                                <div class="flex-1 min-w-[200px]">
                                    <InputLabel :for="`item-${index}-title`" :value="t('title_required')" />
                                    <input
                                        :id="`item-${index}-title`"
                                        v-model="item.title"
                                        type="text"
                                        class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                        :placeholder="t('service_title_placeholder')"
                                        required
                                    />
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-4 items-end">
                                <div class="flex-1 min-w-[200px]">
                                    <InputLabel :for="`item-${index}-description`" :value="t('description_optional')" />
                                    <input
                                        :id="`item-${index}-description`"
                                        v-model="item.description"
                                        type="text"
                                        class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                        :placeholder="t('additional_details')"
                                    />
                                </div>

                            <div class="w-24">
                                <InputLabel :for="`item-${index}-quantity`" :value="t('qty')" />
                                <input
                                    :id="`item-${index}-quantity`"
                                    v-model.number="item.quantity"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                    required
                                />
                            </div>

                            <div class="w-32">
                                <InputLabel :for="`item-${index}-unit`" :value="t('unit')" />
                                <select
                                    :id="`item-${index}-unit`"
                                    v-model="item.unit"
                                    class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
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
                                    class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                    required
                                />
                            </div>

                            <div class="w-32">
                                <InputLabel :for="`item-${index}-vat_rate`" :value="t('vat')" />
                                <select
                                    :id="`item-${index}-vat_rate`"
                                    :value="item.vat_rate_select ?? item.vat_rate"
                                    @change="handleVatRateChange(index, $event.target.value === 'custom' ? 'custom' : parseFloat($event.target.value))"
                                    class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
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
                                    class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                    placeholder="Ex: 12"
                                />
                            </div>

                            <button
                                v-if="form.items.length > 1"
                                type="button"
                                @click="removeItem(index)"
                                class="p-2 text-pink-600 hover:text-pink-800 dark:text-pink-400 dark:hover:text-pink-300"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.519.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                        </div>

                        <button
                            type="button"
                            @click="addItem"
                            class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600"
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
            <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('notes_optional') }}</h2>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <InputLabel for="notes" value="Notes" />
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="3"
                            class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                            :placeholder="t('notes_placeholder')"
                        ></textarea>
                        <InputError :message="form.errors.notes" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="vat_mention" :value="t('vat_mention_optional')" />
                        <select
                            id="vat_mention"
                            v-model="form.vat_mention"
                            class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
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
                            class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                            :placeholder="t('custom_vat_mention_placeholder')"
                        ></textarea>
                    </div>

                    <div>
                        <InputLabel for="footer_message" :value="t('footer_message_optional')" />
                        <textarea
                            id="footer_message"
                            v-model="form.footer_message"
                            rows="2"
                            class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                            :placeholder="defaultInvoiceFooter"
                        ></textarea>
                        <p v-if="defaultInvoiceFooter" class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            {{ t('empty_default_message') }} "{{ defaultInvoiceFooter }}"
                        </p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-3">
                <Link
                    :href="route('invoices.index')"
                    class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600"
                >
                    {{ t('cancel') }}
                </Link>
                <PrimaryButton :disabled="form.processing">
                    <span v-if="form.processing">{{ t('creating') }}</span>
                    <span v-else>{{ t('create_draft') }}</span>
                </PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
