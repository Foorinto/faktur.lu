<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import VatScenarioIndicator from '@/Components/VatScenarioIndicator.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();
const page = usePage();

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    clientTypes: {
        type: Array,
        required: true,
    },
    currencies: {
        type: Array,
        required: true,
    },
    countries: {
        type: Array,
        required: true,
    },
    peppolSchemes: {
        type: Array,
        default: () => [],
    },
    submitLabel: {
        type: String,
        default: null,
    },
    cancelRoute: {
        type: String,
        default: 'clients.index',
    },
    cancelRouteParams: {
        type: [Object, Number, String],
        default: null,
    },
    sellerVatRegime: {
        type: String,
        default: 'franchise',
    },
});

const emit = defineEmits(['submit']);

const isB2B = computed(() => props.form.type === 'b2b');

// EU country codes
const euCountries = ['AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK'];

const isEuCountry = computed(() => euCountries.includes(props.form.country_code));
const isLuxembourg = computed(() => props.form.country_code === 'LU');
const isIntraEu = computed(() => isEuCountry.value && !isLuxembourg.value);
const hasVatNumber = computed(() => props.form.vat_number && props.form.vat_number.trim() !== '');

// Calculate VAT scenario based on form data
const vatScenario = computed(() => {
    // If seller is VAT exempt (franchise)
    if (props.sellerVatRegime === 'franchise') {
        return {
            key: 'FRANCHISE',
            label: t('vat_scenario_franchise'),
            description: t('vat_scenario_franchise_desc'),
            rate: 0,
            mention: 'franchise',
            color: 'gray',
        };
    }

    const countryCode = props.form.country_code || 'LU';
    const type = props.form.type || 'b2b';

    // Luxembourg client
    if (countryCode === 'LU') {
        return {
            key: type === 'b2b' ? 'B2B_LU' : 'B2C_LU',
            label: type === 'b2b' ? t('vat_scenario_b2b_lu') : t('vat_scenario_b2c_lu'),
            description: t('vat_scenario_lu_desc'),
            rate: 17,
            mention: null,
            color: 'green',
        };
    }

    // EU client (not Luxembourg)
    if (euCountries.includes(countryCode)) {
        if (type === 'b2b' && hasVatNumber.value) {
            return {
                key: 'B2B_INTRA_EU',
                label: t('vat_scenario_b2b_intra'),
                description: t('vat_scenario_b2b_intra_desc'),
                rate: 0,
                mention: 'reverse_charge',
                color: 'blue',
            };
        }
        // B2B without VAT or B2C = Luxembourg VAT applies
        return {
            key: type === 'b2b' ? 'B2B_LU' : 'B2C_LU',
            label: type === 'b2b' ? t('vat_scenario_b2b_eu_no_vat') : t('vat_scenario_b2c_eu'),
            description: t('vat_scenario_eu_desc'),
            rate: 17,
            mention: null,
            color: 'yellow',
        };
    }

    // Non-EU client = export
    return {
        key: 'EXPORT',
        label: t('vat_scenario_export'),
        description: t('vat_scenario_export_desc'),
        rate: 0,
        mention: 'export',
        color: 'purple',
    };
});

// Show warning if B2B EU client without VAT number
const showVatWarning = computed(() => {
    return isB2B.value && isIntraEu.value && !hasVatNumber.value;
});

watch(() => props.form.type, (newType) => {
    if (newType === 'b2c') {
        props.form.vat_number = '';
    }
});

const submit = () => {
    emit('submit');
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-8">
        <!-- Type de client -->
        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ t('client_type') }}
                </h2>
            </div>
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <label
                        v-for="clientType in clientTypes"
                        :key="clientType.value"
                        class="flex items-start p-4 rounded-lg border cursor-pointer transition-colors"
                        :class="[
                            form.type === clientType.value
                                ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20'
                                : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                        ]"
                    >
                        <input
                            type="radio"
                            v-model="form.type"
                            :value="clientType.value"
                            class="mt-0.5 h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <div class="ml-3">
                            <span class="block text-sm font-medium text-gray-900 dark:text-white">
                                {{ clientType.label }}
                            </span>
                            <span class="block text-sm text-gray-500 dark:text-gray-400">
                                {{ clientType.description }}
                            </span>
                        </div>
                    </label>
                </div>
                <InputError :message="form.errors.type" class="mt-2" />
            </div>
        </div>

        <!-- Informations du client -->
        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ t('client_info') }}
                </h2>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="name" :value="isB2B ? t('company_name') : t('full_name')" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                            :placeholder="isB2B ? 'Société Exemple SARL' : 'Marie Durand'"
                        />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div v-if="isB2B">
                        <InputLabel for="contact_name" :value="`${t('director_contact')} (${t('optional')})`" />
                        <TextInput
                            id="contact_name"
                            v-model="form.contact_name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Pierre Exemple"
                        />
                        <InputError :message="form.errors.contact_name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="email" :value="t('email')" />
                        <TextInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full"
                            required
                            placeholder="contact@example.com"
                        />
                        <InputError :message="form.errors.email" class="mt-2" />
                    </div>
                </div>

                <div>
                    <InputLabel for="address" :value="t('address')" />
                    <textarea
                        id="address"
                        v-model="form.address"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        rows="2"
                        placeholder="10 Rue Fictive"
                    ></textarea>
                    <InputError :message="form.errors.address" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <InputLabel for="postal_code" :value="t('postal_code')" />
                        <TextInput
                            id="postal_code"
                            v-model="form.postal_code"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="L-2000"
                        />
                        <InputError :message="form.errors.postal_code" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="city" :value="t('city')" />
                        <TextInput
                            id="city"
                            v-model="form.city"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Luxembourg"
                        />
                        <InputError :message="form.errors.city" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="country_code" :value="t('country')" />
                        <select
                            id="country_code"
                            v-model="form.country_code"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            required
                        >
                            <option v-for="country in countries" :key="country.code" :value="country.code">
                                {{ country.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.country_code" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="phone" :value="`${t('phone')} (${t('optional')})`" />
                        <TextInput
                            id="phone"
                            v-model="form.phone"
                            type="tel"
                            class="mt-1 block w-full"
                            placeholder="+352 000 000 000"
                        />
                        <InputError :message="form.errors.phone" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="vat_number">
                            {{ t('vat_number') }}
                            <span v-if="isB2B && isIntraEu" class="text-amber-600 text-xs font-medium">({{ t('vat_required_for_reverse_charge') }})</span>
                            <span v-else-if="isB2B" class="text-gray-400 text-xs">({{ t('vat_recommended_b2b') }})</span>
                            <span v-else class="text-gray-400 text-xs">({{ t('optional') }})</span>
                        </InputLabel>
                        <TextInput
                            id="vat_number"
                            v-model="form.vat_number"
                            type="text"
                            class="mt-1 block w-full font-mono uppercase"
                            placeholder="LU00000000"
                            :class="{ 'bg-gray-100 dark:bg-gray-600': !isB2B }"
                        />
                        <InputError :message="form.errors.vat_number" class="mt-2" />
                    </div>
                </div>

                <!-- VAT Scenario Indicator -->
                <div class="rounded-lg border p-4" :class="[
                    vatScenario.rate === 0
                        ? 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20'
                        : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800'
                ]">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ t('vat_scenario_detected') }}
                            </h4>
                            <VatScenarioIndicator :scenario="vatScenario" size="sm" class="mt-2" />
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-bold" :class="[
                                vatScenario.rate === 0 ? 'text-blue-600 dark:text-blue-400' : 'text-gray-900 dark:text-white'
                            ]">
                                {{ vatScenario.rate }}%
                            </span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('vat_rate') }}</p>
                        </div>
                    </div>
                    <p v-if="showVatWarning" class="mt-3 text-sm text-amber-600 dark:text-amber-400">
                        <svg class="inline h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        {{ t('vat_warning_no_number') }}
                    </p>
                </div>

                <div v-if="isB2B" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="registration_number" :value="`${t('registration_number')} (${t('optional')})`" />
                        <TextInput
                            id="registration_number"
                            v-model="form.registration_number"
                            type="text"
                            class="mt-1 block w-full font-mono"
                            placeholder="B000000"
                        />
                        <InputError :message="form.errors.registration_number" class="mt-2" />
                    </div>
                </div>

                <!-- Peppol Endpoint (B2B only) -->
                <div v-if="isB2B && peppolSchemes.length > 0" class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Identifiant Peppol (optionnel)
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                        Nécessaire pour l'envoi de factures électroniques via le réseau Peppol.
                    </p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="peppol_endpoint_scheme" value="Schéma" />
                            <select
                                id="peppol_endpoint_scheme"
                                v-model="form.peppol_endpoint_scheme"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                            >
                                <option value="">Aucun</option>
                                <option v-for="scheme in peppolSchemes" :key="scheme.value" :value="scheme.value">
                                    {{ scheme.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.peppol_endpoint_scheme" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="peppol_endpoint_id" value="Identifiant" />
                            <TextInput
                                id="peppol_endpoint_id"
                                v-model="form.peppol_endpoint_id"
                                type="text"
                                class="mt-1 block w-full font-mono"
                                placeholder="12345678"
                                :disabled="!form.peppol_endpoint_scheme"
                            />
                            <InputError :message="form.errors.peppol_endpoint_id" class="mt-2" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Facturation -->
        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ t('billing_settings') }}
                </h2>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="currency" :value="t('default_currency')" />
                        <select
                            id="currency"
                            v-model="form.currency"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            required
                        >
                            <option v-for="currency in currencies" :key="currency.value" :value="currency.value">
                                {{ currency.label }}
                            </option>
                        </select>
                        <InputError :message="form.errors.currency" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="locale" :value="t('document_language')" />
                        <select
                            id="locale"
                            v-model="form.locale"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option v-for="(label, code) in page.props.availableLocales" :key="code" :value="code">
                                {{ label }}
                            </option>
                        </select>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ t('document_language_help') }}
                        </p>
                        <InputError :message="form.errors.locale" class="mt-2" />
                    </div>
                </div>

                <div>
                    <InputLabel for="notes" :value="`${t('internal_notes')} (${t('optional')})`" />
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        rows="3"
                        :placeholder="t('internal_notes')"
                    ></textarea>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ t('notes_not_visible') }}
                    </p>
                    <InputError :message="form.errors.notes" class="mt-2" />
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3">
            <Link
                :href="cancelRouteParams ? route(cancelRoute, cancelRouteParams) : route(cancelRoute)"
                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
            >
                {{ t('cancel') }}
            </Link>
            <PrimaryButton
                :disabled="form.processing"
                :class="{ 'opacity-25': form.processing }"
            >
                <span v-if="form.processing">{{ t('saving') }}</span>
                <span v-else>{{ submitLabel || t('save') }}</span>
            </PrimaryButton>
        </div>
    </form>
</template>
