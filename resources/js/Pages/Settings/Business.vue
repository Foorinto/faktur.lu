<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    settings: {
        type: Object,
        default: null,
    },
    vatRegimes: {
        type: Array,
        required: true,
    },
    vatMentionOptions: {
        type: Array,
        default: () => [],
    },
    pdfColorPresets: {
        type: Array,
        default: () => [],
    },
    defaultPdfColor: {
        type: String,
        default: '#7c3aed',
    },
    peppolSchemes: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    company_name: props.settings?.company_name ?? '',
    legal_name: props.settings?.legal_name ?? '',
    address: props.settings?.address ?? '',
    postal_code: props.settings?.postal_code ?? '',
    city: props.settings?.city ?? '',
    country_code: props.settings?.country_code ?? 'LU',
    vat_number: props.settings?.vat_number ?? '',
    matricule: props.settings?.matricule ?? '',
    rcs_number: props.settings?.rcs_number ?? '',
    establishment_authorization: props.settings?.establishment_authorization ?? '',
    iban: props.settings?.iban ?? '',
    bic: props.settings?.bic ?? '',
    bank_name: props.settings?.bank_name ?? '',
    vat_regime: props.settings?.vat_regime ?? 'franchise',
    default_hourly_rate: props.settings?.default_hourly_rate ?? '',
    default_invoice_footer: props.settings?.default_invoice_footer ?? 'Merci pour votre confiance !',
    default_vat_mention: props.settings?.default_vat_mention ?? 'franchise',
    default_custom_vat_mention: props.settings?.default_custom_vat_mention ?? '',
    default_pdf_color: props.settings?.default_pdf_color ?? props.defaultPdfColor,
    phone: props.settings?.phone ?? '',
    show_phone_on_invoice: props.settings?.show_phone_on_invoice ?? false,
    email: props.settings?.email ?? '',
    show_email_on_invoice: props.settings?.show_email_on_invoice ?? false,
    peppol_endpoint_scheme: props.settings?.peppol_endpoint_scheme ?? '',
    peppol_endpoint_id: props.settings?.peppol_endpoint_id ?? '',
});

const isCustomColor = computed(() => {
    return !props.pdfColorPresets.some(p => p.value === form.default_pdf_color);
});

const logoForm = useForm({
    logo: null,
});

const logoInput = ref(null);
const logoPreview = ref(props.settings?.logo_url ?? null);

const isVatRequired = computed(() => form.vat_regime === 'assujetti');

const submit = () => {
    form.put(route('settings.business.update'), {
        preserveScroll: true,
    });
};

const selectLogo = () => {
    logoInput.value.click();
};

const handleLogoSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        logoForm.logo = file;
        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            logoPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const uploadLogo = () => {
    logoForm.post(route('settings.business.logo.upload'), {
        preserveScroll: true,
        onSuccess: () => {
            logoForm.reset();
            logoInput.value.value = '';
        },
    });
};

const deleteLogo = () => {
    if (confirm(t('delete_logo'))) {
        router.delete(route('settings.business.logo.delete'), {
            preserveScroll: true,
            onSuccess: () => {
                logoPreview.value = null;
            },
        });
    }
};

const cancelLogoUpload = () => {
    logoForm.reset();
    logoPreview.value = props.settings?.logo_url ?? null;
    logoInput.value.value = '';
};
</script>

<template>
    <Head :title="t('settings_business')" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('settings') }}
            </h1>
        </template>

        <!-- Settings Navigation -->
        <div class="mb-6 border-b border-slate-200 dark:border-slate-700">
            <nav class="flex space-x-8" aria-label="Settings tabs">
                <Link
                    :href="route('settings.business.edit')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-primary-500 text-primary-600 dark:text-primary-400"
                >
                    {{ t('business_settings') }}
                </Link>
                <Link
                    :href="route('settings.email')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300"
                >
                    {{ t('email_settings') }}
                </Link>
                <Link
                    :href="route('settings.email.provider')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300"
                >
                    Fournisseur Email
                </Link>
                <Link
                    :href="route('settings.accountant')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300"
                >
                    Accès Comptable
                </Link>
                <Link
                    :href="route('subscription.index')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300"
                >
                    Abonnement
                </Link>
            </nav>
        </div>

        <div class="mx-auto max-w-3xl space-y-8">
            <!-- Logo -->
            <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">
                        {{ t('logo') }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ t('logo_appears_on_invoices') }}
                    </p>
                </div>
                <div class="px-6 py-4">
                    <div class="flex items-start space-x-6">
                        <!-- Logo preview -->
                        <div class="flex-shrink-0">
                            <div
                                v-if="logoPreview"
                                class="w-32 h-32 rounded-xl border border-slate-200 dark:border-slate-600 overflow-hidden bg-white flex items-center justify-center"
                            >
                                <img
                                    :src="logoPreview"
                                    alt="Logo"
                                    class="max-w-full max-h-full object-contain"
                                />
                            </div>
                            <div
                                v-else
                                class="w-32 h-32 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-600 flex items-center justify-center"
                            >
                                <svg class="h-10 w-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Upload controls -->
                        <div class="flex-1">
                            <input
                                ref="logoInput"
                                type="file"
                                accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp"
                                class="hidden"
                                @change="handleLogoSelect"
                            />

                            <div v-if="!settings" class="text-sm text-amber-600 dark:text-amber-400 mb-3">
                                {{ t('save_company_first') }}
                            </div>

                            <div v-else-if="logoForm.logo" class="space-y-3">
                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                    {{ t('new_file_selected') }} <span class="font-medium">{{ logoForm.logo.name }}</span>
                                </p>
                                <div class="flex space-x-3">
                                    <button
                                        type="button"
                                        @click="uploadLogo"
                                        :disabled="logoForm.processing"
                                        class="inline-flex items-center rounded-xl bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 disabled:opacity-50"
                                    >
                                        <svg v-if="logoForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ t('save') }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="cancelLogoUpload"
                                        class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300"
                                    >
                                        {{ t('cancel') }}
                                    </button>
                                </div>
                                <InputError :message="logoForm.errors.logo" />
                            </div>

                            <div v-else class="space-y-3">
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    {{ t('logo_format_info') }}<br>
                                    {{ t('max_size') }} 2 Mo.
                                </p>
                                <div class="flex space-x-3">
                                    <button
                                        type="button"
                                        @click="selectLogo"
                                        class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300"
                                    >
                                        <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                        {{ settings?.logo_path ? t('change_logo') : t('add_logo') }}
                                    </button>
                                    <button
                                        v-if="settings?.logo_path"
                                        type="button"
                                        @click="deleteLogo"
                                        class="inline-flex items-center rounded-xl border border-pink-300 bg-white px-3 py-2 text-sm font-medium text-pink-700 shadow-sm hover:bg-pink-50 dark:border-pink-600 dark:bg-slate-700 dark:text-pink-400"
                                    >
                                        <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        {{ t('delete') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <!-- Informations légales -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">
                            {{ t('legal_information') }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{ t('legal_info_appear_invoices') }}
                        </p>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="company_name" :value="t('commercial_name')" />
                                <TextInput
                                    id="company_name"
                                    v-model="form.company_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    placeholder="Ma Société SARL"
                                />
                                <InputError :message="form.errors.company_name" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="legal_name" :value="t('legal_name')" />
                                <TextInput
                                    id="legal_name"
                                    v-model="form.legal_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    placeholder="Prénom Nom"
                                />
                                <InputError :message="form.errors.legal_name" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="address" :value="t('address')" />
                            <textarea
                                id="address"
                                v-model="form.address"
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                rows="2"
                                required
                                placeholder="1 Rue Exemple"
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
                                    required
                                    placeholder="L-1234"
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
                                    required
                                    placeholder="Luxembourg"
                                />
                                <InputError :message="form.errors.city" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="country_code" :value="t('country')" />
                                <select
                                    id="country_code"
                                    v-model="form.country_code"
                                    class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                    required
                                >
                                    <option value="LU">Luxembourg</option>
                                    <option value="BE">Belgique</option>
                                    <option value="FR">France</option>
                                    <option value="DE">Allemagne</option>
                                </select>
                                <InputError :message="form.errors.country_code" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Identifiants fiscaux -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">
                            {{ t('tax_identifiers') }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{ t('tax_info_required') }}
                        </p>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="matricule" :value="t('matricule_label')" />
                                <TextInput
                                    id="matricule"
                                    v-model="form.matricule"
                                    type="text"
                                    class="mt-1 block w-full font-mono"
                                    required
                                    maxlength="13"
                                    placeholder="0000000000000"
                                />
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ t('matricule_help') }}
                                </p>
                                <InputError :message="form.errors.matricule" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="rcs_number">
                                    {{ t('rcs_number_label') }}
                                    <span class="text-slate-400 text-xs">({{ t('optional') }})</span>
                                </InputLabel>
                                <TextInput
                                    id="rcs_number"
                                    v-model="form.rcs_number"
                                    type="text"
                                    class="mt-1 block w-full font-mono uppercase"
                                    maxlength="20"
                                    placeholder="A00000"
                                />
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ t('rcs_help') }}
                                </p>
                                <InputError :message="form.errors.rcs_number" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="vat_number">
                                    {{ t('vat_number') }}
                                    <span v-if="isVatRequired" class="text-pink-500">*</span>
                                    <span v-else class="text-slate-400 text-xs">({{ t('optional') }})</span>
                                </InputLabel>
                                <TextInput
                                    id="vat_number"
                                    v-model="form.vat_number"
                                    type="text"
                                    class="mt-1 block w-full font-mono uppercase"
                                    :required="isVatRequired"
                                    maxlength="20"
                                    placeholder="LU00000000"
                                />
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ t('vat_format_help') }}
                                    <span v-if="!isVatRequired"> ({{ t('kept_for_reference') }})</span>
                                </p>
                                <InputError :message="form.errors.vat_number" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="establishment_authorization">
                                    {{ t('establishment_authorization') }}
                                    <span class="text-slate-400 text-xs">({{ t('optional') }})</span>
                                </InputLabel>
                                <TextInput
                                    id="establishment_authorization"
                                    v-model="form.establishment_authorization"
                                    type="text"
                                    class="mt-1 block w-full"
                                    maxlength="50"
                                    placeholder="N° d'autorisation"
                                />
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ t('establishment_authorization_help') }}
                                </p>
                                <InputError :message="form.errors.establishment_authorization" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel :value="t('vat_regime')" />
                            <div class="mt-2 space-y-3">
                                <label
                                    v-for="regime in vatRegimes"
                                    :key="regime.value"
                                    class="flex items-start p-4 rounded-xl border cursor-pointer transition-colors"
                                    :class="[
                                        form.vat_regime === regime.value
                                            ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                                            : 'border-slate-200 dark:border-slate-600 hover:border-slate-300 dark:hover:border-slate-500'
                                    ]"
                                >
                                    <input
                                        type="radio"
                                        v-model="form.vat_regime"
                                        :value="regime.value"
                                        class="mt-0.5 h-4 w-4 border-slate-300 text-primary-600 focus:ring-primary-500"
                                    />
                                    <div class="ml-3">
                                        <span class="block text-sm font-medium text-slate-900 dark:text-white">
                                            {{ regime.label }}
                                        </span>
                                        <span class="block text-sm text-slate-500 dark:text-slate-400">
                                            {{ regime.description }}
                                        </span>
                                    </div>
                                </label>
                            </div>
                            <InputError :message="form.errors.vat_regime" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Coordonnées bancaires -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">
                            {{ t('bank_details') }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{ t('bank_details_help') }}
                        </p>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <InputLabel for="bank_name" :value="`${t('bank_name')} (${t('optional')})`" />
                            <TextInput
                                id="bank_name"
                                v-model="form.bank_name"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Nom de votre banque"
                            />
                            <InputError :message="form.errors.bank_name" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="iban" value="IBAN" />
                                <TextInput
                                    id="iban"
                                    v-model="form.iban"
                                    type="text"
                                    class="mt-1 block w-full font-mono uppercase"
                                    required
                                    placeholder="LU00 0000 0000 0000 0000"
                                />
                                <InputError :message="form.errors.iban" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="bic" value="BIC/SWIFT" />
                                <TextInput
                                    id="bic"
                                    v-model="form.bic"
                                    type="text"
                                    class="mt-1 block w-full font-mono uppercase"
                                    required
                                    placeholder="AAAABBCCXXX"
                                />
                                <InputError :message="form.errors.bic" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Peppol e-Invoicing -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">
                            Peppol e-Invoicing
                        </h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Configurez votre identifiant Peppol pour exporter des factures au format Peppol BIS 3.0
                        </p>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="peppol_endpoint_scheme" value="Schéma d'identifiant" />
                                <select
                                    id="peppol_endpoint_scheme"
                                    v-model="form.peppol_endpoint_scheme"
                                    class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                >
                                    <option value="">-- Sélectionner --</option>
                                    <option v-for="scheme in peppolSchemes" :key="scheme.value" :value="scheme.value">
                                        {{ scheme.label }}
                                    </option>
                                </select>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    Code ISO 6523 ICD. Pour Luxembourg, utilisez 0184 (TVA).
                                </p>
                                <InputError :message="form.errors.peppol_endpoint_scheme" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="peppol_endpoint_id" value="Identifiant Peppol" />
                                <TextInput
                                    id="peppol_endpoint_id"
                                    v-model="form.peppol_endpoint_id"
                                    type="text"
                                    class="mt-1 block w-full font-mono uppercase"
                                    maxlength="50"
                                    placeholder="LU12345678"
                                />
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    Votre numéro de TVA ou autre identifiant selon le schéma choisi.
                                </p>
                                <InputError :message="form.errors.peppol_endpoint_id" class="mt-2" />
                            </div>
                        </div>

                        <div class="rounded-xl bg-sky-50 dark:bg-sky-900/20 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-sky-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-sky-700 dark:text-sky-300">
                                        L'export Peppol génère un fichier XML que vous pouvez télécharger et importer manuellement dans votre Access Point Peppol (ex: Peppol.lu, Basware, etc.).
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarification -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">
                            {{ t('pricing') }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{ t('pricing_help') }}
                        </p>
                    </div>
                    <div class="px-6 py-4">
                        <div class="max-w-xs">
                            <InputLabel for="default_hourly_rate" :value="t('default_hourly_rate')" />
                            <div class="mt-1 relative rounded-xl shadow-sm">
                                <TextInput
                                    id="default_hourly_rate"
                                    v-model="form.default_hourly_rate"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="block w-full pr-12"
                                    placeholder="100.00"
                                />
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <span class="text-slate-500 dark:text-slate-400 sm:text-sm">€/h</span>
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                {{ t('hourly_rate_help') }}
                            </p>
                            <InputError :message="form.errors.default_hourly_rate" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Personnalisation des factures -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">
                            {{ t('invoice_customization') }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{ t('invoice_customization_help') }}
                        </p>
                    </div>
                    <div class="px-6 py-4 space-y-6">
                        <!-- VAT Mention -->
                        <div>
                            <InputLabel for="default_vat_mention" :value="t('default_vat_mention')" />
                            <select
                                id="default_vat_mention"
                                v-model="form.default_vat_mention"
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                            >
                                <option v-for="option in vatMentionOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                {{ t('vat_mention_help') }}
                            </p>
                            <InputError :message="form.errors.default_vat_mention" class="mt-2" />
                        </div>

                        <!-- Custom VAT Mention (shown only when "other" is selected) -->
                        <div v-if="form.default_vat_mention === 'other'">
                            <InputLabel for="default_custom_vat_mention" :value="t('custom_vat_mention')" />
                            <textarea
                                id="default_custom_vat_mention"
                                v-model="form.default_custom_vat_mention"
                                rows="2"
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                :placeholder="t('custom_vat_placeholder')"
                            ></textarea>
                            <InputError :message="form.errors.default_custom_vat_mention" class="mt-2" />
                        </div>

                        <!-- PDF Color -->
                        <div>
                            <InputLabel :value="t('pdf_color')" />
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 mb-3">
                                {{ t('pdf_color_help') }}
                            </p>
                            <div class="flex flex-wrap gap-2 mb-3">
                                <button
                                    v-for="preset in pdfColorPresets"
                                    :key="preset.value"
                                    type="button"
                                    @click="form.default_pdf_color = preset.value"
                                    class="w-10 h-10 rounded-xl border-2 transition-all focus:outline-none focus:ring-2 focus:ring-offset-2"
                                    :class="form.default_pdf_color === preset.value ? 'border-slate-900 dark:border-white ring-2 ring-offset-2' : 'border-slate-300 dark:border-slate-600 hover:border-slate-400'"
                                    :style="{ backgroundColor: preset.value }"
                                    :title="preset.label"
                                >
                                    <span class="sr-only">{{ preset.label }}</span>
                                    <svg
                                        v-if="form.default_pdf_color === preset.value"
                                        class="w-5 h-5 mx-auto text-white"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="text-sm text-slate-600 dark:text-slate-400">{{ t('custom_color') }}</label>
                                <input
                                    type="color"
                                    v-model="form.default_pdf_color"
                                    class="w-10 h-10 rounded-xl cursor-pointer border border-slate-300 dark:border-slate-600"
                                />
                                <input
                                    type="text"
                                    v-model="form.default_pdf_color"
                                    class="w-28 px-2 py-1 rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm font-mono uppercase"
                                    placeholder="#7c3aed"
                                    maxlength="7"
                                />
                            </div>
                            <InputError :message="form.errors.default_pdf_color" class="mt-2" />
                        </div>

                        <!-- Footer Message -->
                        <div>
                            <InputLabel for="default_invoice_footer" :value="t('default_footer_message')" />
                            <textarea
                                id="default_invoice_footer"
                                v-model="form.default_invoice_footer"
                                rows="3"
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                placeholder="Merci pour votre confiance !"
                            ></textarea>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                {{ t('footer_message_help') }}
                            </p>
                            <InputError :message="form.errors.default_invoice_footer" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Contact -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">
                            {{ t('contact') }}
                        </h2>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="email" :value="t('email')" />
                                <TextInput
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    required
                                    placeholder="email@exemple.lu"
                                />
                                <label class="mt-2 flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.show_email_on_invoice"
                                        class="rounded border-slate-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700"
                                    />
                                    <span class="ml-2 text-sm text-slate-600 dark:text-slate-400">
                                        {{ t('show_on_invoices') }}
                                    </span>
                                </label>
                                <InputError :message="form.errors.email" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="phone" :value="t('phone_optional')" />
                                <TextInput
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    class="mt-1 block w-full"
                                    placeholder="+352 000 000 000"
                                />
                                <label class="mt-2 flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.show_phone_on_invoice"
                                        class="rounded border-slate-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700"
                                    />
                                    <span class="ml-2 text-sm text-slate-600 dark:text-slate-400">
                                        {{ t('show_on_invoices') }}
                                    </span>
                                </label>
                                <InputError :message="form.errors.phone" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end items-center gap-4">
                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p
                            v-if="form.recentlySuccessful"
                            class="text-sm text-emerald-600 dark:text-emerald-400"
                        >
                            {{ t('saved') }}
                        </p>
                    </Transition>
                    <PrimaryButton
                        :disabled="form.processing"
                        :class="{ 'opacity-25': form.processing }"
                    >
                        <span v-if="form.processing">{{ t('saving') }}</span>
                        <span v-else>{{ t('save') }}</span>
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
