<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import EntrepriseNav from '@/Components/EntrepriseNav.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import NumberingSettingsSection from '@/Components/Numbering/NumberingSettingsSection.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import { computed, watch, ref, onMounted } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useTour } from '@/Composables/useTour';

const { startTour } = useTour();

// Scroll to section based on URL hash and highlight it
onMounted(() => {
    if (window.location.hash) {
        setTimeout(() => {
            const targetId = window.location.hash.substring(1) + '-section';
            const element = document.getElementById(targetId);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'start' });
                element.classList.add('ring-4', 'ring-primary-500', 'ring-offset-2');
                setTimeout(() => {
                    element.classList.remove('ring-4', 'ring-primary-500', 'ring-offset-2');
                }, 3000);
            }
        }, 300);
    } else {
        setTimeout(() => startTour('settings'), 600);
    }
});

const { t } = useTranslations();

const props = defineProps({
    settings: {
        type: Object,
        default: null,
    },
    countries: {
        type: Array,
        default: () => [],
    },
    countriesConfig: {
        type: Object,
        default: () => ({}),
    },
    activityTypes: {
        type: Array,
        default: () => [],
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
    numbering: {
        type: Object,
        default: () => ({
            editability: { invoice: true, credit_note: true, quote: true },
            finalized_counts: { invoice: 0, credit_note: 0, quote: 0 },
            current_year: new Date().getFullYear(),
            placeholders: ['{prefix}', '{year}', '{yy}', '{month}', '{day}', '{number}', '{client_name}'],
            default_template: '{prefix}-{year}-{number}',
        }),
    },
});

const form = useForm({
    company_name: props.settings?.company_name ?? '',
    legal_name: props.settings?.legal_name ?? '',
    address: props.settings?.address ?? '',
    postal_code: props.settings?.postal_code ?? '',
    city: props.settings?.city ?? '',
    country_code: props.settings?.country_code ?? 'LU',
    activity_type: props.settings?.activity_type ?? 'services',
    vat_number: props.settings?.vat_number ?? '',
    matricule: props.settings?.matricule ?? '',
    rcs_number: props.settings?.rcs_number ?? '',
    establishment_authorization: props.settings?.establishment_authorization ?? '',
    iban: props.settings?.iban ?? '',
    bic: props.settings?.bic ?? '',
    bank_name: props.settings?.bank_name ?? '',
    vat_regime: props.settings?.vat_regime ?? 'franchise',
    default_hourly_rate: props.settings?.default_hourly_rate ?? '',
    // Laisse vide par défaut : le PDF utilise alors la traduction "Merci pour votre confiance !" / "Thank you for your business!" / etc.
    // selon la langue de la facture. Si l'utilisateur saisit une valeur, elle remplace ce message dans toutes les langues.
    default_invoice_footer: props.settings?.default_invoice_footer ?? '',
    default_vat_mention: props.settings?.default_vat_mention ?? 'franchise',
    default_custom_vat_mention: props.settings?.default_custom_vat_mention ?? '',
    default_pdf_color: props.settings?.default_pdf_color ?? props.defaultPdfColor,
    phone: props.settings?.phone ?? '',
    show_phone_on_invoice: props.settings?.show_phone_on_invoice ?? false,
    email: props.settings?.email ?? '',
    show_email_on_invoice: props.settings?.show_email_on_invoice ?? false,
    peppol_endpoint_scheme: props.settings?.peppol_endpoint_scheme ?? '',
    peppol_endpoint_id: props.settings?.peppol_endpoint_id ?? '',
    show_payment_qrcode: props.settings?.show_payment_qrcode ?? false,
    default_payment_methods: props.settings?.default_payment_methods ?? [],
    payment_instructions: props.settings?.payment_instructions ?? '',
    show_payment_conditions: props.settings?.show_payment_conditions ?? true,
    late_penalty_text: props.settings?.late_penalty_text ?? '',
    recovery_fee_amount: props.settings?.recovery_fee_amount ?? null,
    discount_terms: props.settings?.discount_terms ?? '',
    // Numbering customization
    number_format: props.settings?.number_format ?? '{prefix}-{year}-{number}',
    invoice_prefix: props.settings?.invoice_prefix ?? 'F',
    credit_note_prefix: props.settings?.credit_note_prefix ?? 'AV',
    quote_prefix: props.settings?.quote_prefix ?? 'DEV',
    invoice_starting_number: props.settings?.invoice_starting_number ?? null,
    credit_note_starting_number: props.settings?.credit_note_starting_number ?? null,
    quote_starting_number: props.settings?.quote_starting_number ?? null,
    number_padding: props.settings?.number_padding ?? 3,
});

// Two-way binding for the NumberingSettingsSection (composite object editor)
const numberingValues = computed({
    get: () => ({
        number_format: form.number_format,
        invoice_prefix: form.invoice_prefix,
        credit_note_prefix: form.credit_note_prefix,
        quote_prefix: form.quote_prefix,
        invoice_starting_number: form.invoice_starting_number,
        credit_note_starting_number: form.credit_note_starting_number,
        quote_starting_number: form.quote_starting_number,
        number_padding: form.number_padding,
    }),
    set: (v) => {
        form.number_format = v.number_format;
        form.invoice_prefix = v.invoice_prefix;
        form.credit_note_prefix = v.credit_note_prefix;
        form.quote_prefix = v.quote_prefix;
        form.invoice_starting_number = v.invoice_starting_number;
        form.credit_note_starting_number = v.credit_note_starting_number;
        form.quote_starting_number = v.quote_starting_number;
        form.number_padding = v.number_padding;
    },
});

const isCustomColor = computed(() => {
    return !props.pdfColorPresets.some(p => p.value === form.default_pdf_color);
});

const logoForm = useForm({
    logo: null,
});

const logoInput = ref(null);
const logoPreview = ref(props.settings?.logo_url ?? null);

const paymentQrcodeForm = useForm({
    payment_qrcode: null,
});

const paymentQrcodeInput = ref(null);
const paymentQrcodePreview = ref(props.settings?.payment_qrcode_url ?? null);

// FEAT-098: moyens de paiement affichés sur les factures
const paymentMethodOptions = ['transfer', 'payconiq', 'cash', 'card', 'check'];

const isVatRequired = computed(() => form.vat_regime === 'assujetti');

// Show activity type selector for France (different thresholds for services vs goods)
const showActivityType = computed(() => form.country_code === 'FR');

// Get the current country configuration
const currentCountryConfig = computed(() => {
    return props.countriesConfig[form.country_code] || props.countriesConfig['LU'] || {};
});

// Get the franchise threshold based on country and activity type
const franchiseThreshold = computed(() => {
    const config = currentCountryConfig.value;
    if (!config.franchise) return 50000;

    // For France, threshold depends on activity type
    if (form.country_code === 'FR') {
        if (form.activity_type === 'goods') {
            return config.franchise.threshold_goods || 85000;
        }
        return config.franchise.threshold_services || 37500;
    }

    return config.franchise.threshold || 50000;
});

// Get formatted franchise threshold
const formattedFranchiseThreshold = computed(() => {
    return new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(franchiseThreshold.value);
});

// Get the franchise legal reference
const franchiseLegalReference = computed(() => {
    const config = currentCountryConfig.value;
    return config.franchise?.legal_reference || 'Art. 57 du Code de la TVA luxembourgeois';
});

// Get dynamic VAT mention options based on selected country
const dynamicVatMentionOptions = computed(() => {
    const config = currentCountryConfig.value;
    const mentions = config.vat_mentions || {};

    const options = [];

    // Add country-specific mentions
    if (mentions.franchise) {
        options.push({ value: 'franchise', label: mentions.franchise });
    }
    if (mentions.reverse_charge) {
        options.push({ value: 'reverse_charge', label: mentions.reverse_charge });
    }
    if (mentions.intra_eu) {
        options.push({ value: 'intra_eu', label: mentions.intra_eu });
    }
    if (mentions.export) {
        options.push({ value: 'export', label: mentions.export });
    }

    // Add static options
    options.push({ value: 'none', label: t('business_settings_no_footer_option') });
    options.push({ value: 'other', label: t('business_settings_other_footer_option') });

    return options;
});

// Get fiscal identifiers configuration for the selected country
const fiscalIdentifiers = computed(() => {
    const config = currentCountryConfig.value;
    return config.fiscal_identifiers || {
        primary: {
            label: 'Matricule',
            placeholder: '0000000000000',
            help: '13 chiffres',
            maxlength: 13,
            required: true,
        },
        secondary: {
            label: 'N° RCS',
            placeholder: 'B123456',
            help: 'Registre du Commerce',
            maxlength: 20,
            required: false,
        },
        has_establishment_authorization: true,
    };
});

// Check if establishment authorization field should be shown (only for Luxembourg)
const showEstablishmentAuthorization = computed(() => {
    return fiscalIdentifiers.value.has_establishment_authorization === true;
});

// Get country flag
const getCountryFlag = (code) => {
    const flags = { LU: '🇱🇺', FR: '🇫🇷', BE: '🇧🇪', DE: '🇩🇪' };
    return flags[code] || '🏳️';
};

const submit = () => {
    form.put(route('settings.business.update'), {
        preserveScroll: true,
        preserveState: true,
        onError: () => {
            // Scroll to first error for better UX
            const firstError = document.querySelector('.text-red-600, .text-pink-600');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        },
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

const selectPaymentQrcode = () => {
    paymentQrcodeInput.value.click();
};

const handlePaymentQrcodeSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        paymentQrcodeForm.payment_qrcode = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            paymentQrcodePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const uploadPaymentQrcode = () => {
    paymentQrcodeForm.post(route('settings.business.payment-qrcode.upload'), {
        preserveScroll: true,
        onSuccess: () => {
            paymentQrcodeForm.reset();
            paymentQrcodeInput.value.value = '';
        },
    });
};

const deletePaymentQrcode = () => {
    if (confirm(t('delete_payment_qrcode_confirm'))) {
        router.delete(route('settings.business.payment-qrcode.delete'), {
            preserveScroll: true,
            onSuccess: () => {
                paymentQrcodePreview.value = null;
            },
        });
    }
};

const cancelPaymentQrcodeUpload = () => {
    paymentQrcodeForm.reset();
    paymentQrcodePreview.value = props.settings?.payment_qrcode_url ?? null;
    paymentQrcodeInput.value.value = '';
};
</script>

<template>
    <Head :title="t('settings_business')" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('business') }}
            </h1>
        </template>

        <EntrepriseNav class="mb-6" />

        <div class="mx-auto max-w-3xl space-y-8">
            <!-- Logo -->
            <div id="logo-section" data-tour="settings-logo" class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50 scroll-mt-20">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">
                        {{ t('logo') }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ t('logo_appears_on_invoices') }}
                    </p>
                </div>
                <div class="px-6 py-4">
                    <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-start sm:gap-6">
                        <!-- Logo preview -->
                        <div class="flex-shrink-0">
                            <div
                                v-if="logoPreview"
                                class="w-24 h-24 sm:w-32 sm:h-32 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-white flex items-center justify-center"
                            >
                                <img
                                    :src="logoPreview"
                                    alt="Logo"
                                    class="max-w-full max-h-full object-contain"
                                />
                            </div>
                            <div
                                v-else
                                class="w-24 h-24 sm:w-32 sm:h-32 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-700 flex items-center justify-center"
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
                                <div class="flex flex-wrap gap-2">
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
                                        class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300"
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
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        @click="selectLogo"
                                        class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300"
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
                                        class="inline-flex items-center rounded-xl border border-pink-300 bg-white px-3 py-2 text-sm font-medium text-pink-700 shadow-sm hover:bg-pink-50 dark:border-pink-600 dark:bg-gray-800 dark:text-pink-400"
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
                <div id="company-section" data-tour="settings-company" class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50 scroll-mt-20">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
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
                                class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
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
                                    class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    required
                                >
                                    <option
                                        v-for="country in countries"
                                        :key="country.value"
                                        :value="country.value"
                                    >
                                        {{ country.flag }} {{ country.label }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.country_code" class="mt-2" />
                            </div>

                            <!-- Activity Type (for France) -->
                            <div v-if="showActivityType">
                                <InputLabel for="activity_type" value="Type d'activité" />
                                <select
                                    id="activity_type"
                                    v-model="form.activity_type"
                                    class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                >
                                    <option
                                        v-for="type in activityTypes"
                                        :key="type.value"
                                        :value="type.value"
                                    >
                                        {{ type.label }}
                                    </option>
                                </select>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    Le seuil de franchise dépend du type d'activité en France
                                </p>
                                <InputError :message="form.errors.activity_type" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Identifiants fiscaux -->
                <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
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
                                <InputLabel for="matricule" :value="fiscalIdentifiers.primary.label" />
                                <TextInput
                                    id="matricule"
                                    v-model="form.matricule"
                                    type="text"
                                    class="mt-1 block w-full font-mono"
                                    :required="fiscalIdentifiers.primary.required"
                                    :maxlength="fiscalIdentifiers.primary.maxlength"
                                    :placeholder="fiscalIdentifiers.primary.placeholder"
                                />
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ fiscalIdentifiers.primary.help }}
                                </p>
                                <InputError :message="form.errors.matricule" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="rcs_number">
                                    {{ fiscalIdentifiers.secondary.label }}
                                    <span v-if="!fiscalIdentifiers.secondary.required" class="text-slate-400 text-xs">({{ t('optional') }})</span>
                                </InputLabel>
                                <TextInput
                                    id="rcs_number"
                                    v-model="form.rcs_number"
                                    type="text"
                                    class="mt-1 block w-full font-mono uppercase"
                                    :maxlength="fiscalIdentifiers.secondary.maxlength"
                                    :placeholder="fiscalIdentifiers.secondary.placeholder"
                                />
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ fiscalIdentifiers.secondary.help }}
                                </p>
                                <InputError :message="form.errors.rcs_number" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4" :class="showEstablishmentAuthorization ? 'sm:grid-cols-2' : ''">
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
                                    :placeholder="currentCountryConfig.vat_number?.example || 'LU00000000'"
                                />
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ t('vat_format_help') }}
                                    <span v-if="!isVatRequired"> ({{ t('kept_for_reference') }})</span>
                                </p>
                                <InputError :message="form.errors.vat_number" class="mt-2" />
                            </div>

                            <div v-if="showEstablishmentAuthorization">
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
                                <!-- Franchise option with dynamic threshold -->
                                <label
                                    class="flex items-start p-4 rounded-xl border cursor-pointer transition-colors"
                                    :class="[
                                        form.vat_regime === 'franchise'
                                            ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                                            : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-slate-500'
                                    ]"
                                >
                                    <input
                                        type="radio"
                                        v-model="form.vat_regime"
                                        value="franchise"
                                        class="mt-0.5 h-4 w-4 border-gray-300 text-primary-600 focus:ring-primary-500"
                                    />
                                    <div class="ml-3">
                                        <span class="block text-sm font-medium text-slate-900 dark:text-white">
                                            Franchise (&lt; {{ formattedFranchiseThreshold }} €/an)
                                        </span>
                                        <span class="block text-sm text-slate-500 dark:text-slate-400">
                                            Exonéré de TVA
                                        </span>
                                        <span class="block text-xs text-slate-400 dark:text-slate-500 mt-1">
                                            {{ franchiseLegalReference }}
                                        </span>
                                    </div>
                                </label>

                                <!-- Assujetti option -->
                                <label
                                    class="flex items-start p-4 rounded-xl border cursor-pointer transition-colors"
                                    :class="[
                                        form.vat_regime === 'assujetti'
                                            ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                                            : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-slate-500'
                                    ]"
                                >
                                    <input
                                        type="radio"
                                        v-model="form.vat_regime"
                                        value="assujetti"
                                        class="mt-0.5 h-4 w-4 border-gray-300 text-primary-600 focus:ring-primary-500"
                                    />
                                    <div class="ml-3">
                                        <span class="block text-sm font-medium text-slate-900 dark:text-white">
                                            Assujetti
                                        </span>
                                        <span class="block text-sm text-slate-500 dark:text-slate-400">
                                            TVA collectée et déductible
                                        </span>
                                    </div>
                                </label>
                            </div>
                            <InputError :message="form.errors.vat_regime" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Coordonnées bancaires -->
                <div id="bank-section" data-tour="settings-bank" class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50 scroll-mt-20">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
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

                        <!-- Accepted payment methods (FEAT-098) -->
                        <div class="mt-4">
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('payment_methods_setting_title') }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">{{ t('payment_methods_setting_help') }}</p>
                            <div class="flex flex-wrap gap-2">
                                <label
                                    v-for="method in paymentMethodOptions"
                                    :key="method"
                                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-1.5 text-sm cursor-pointer dark:border-gray-700"
                                >
                                    <input
                                        type="checkbox"
                                        :value="method"
                                        v-model="form.default_payment_methods"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800"
                                    />
                                    <span class="text-slate-700 dark:text-slate-300">{{ t('payment_methods.' + method) }}</span>
                                </label>
                            </div>

                            <!-- Payment instructions free text (chèque, lien CB, etc.) -->
                            <div class="mt-3">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('payment_instructions_setting_title') }}</label>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">{{ t('payment_instructions_setting_help') }}</p>
                                <RichTextEditor use-company-link-color v-model="form.payment_instructions" class="mt-1" />
                                <InputError :message="form.errors.payment_instructions" class="mt-1" />
                            </div>
                        </div>

                        <!-- Payment conditions / legal mentions (FEAT-099) -->
                        <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-800">
                            <label class="flex items-start cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.show_payment_conditions"
                                    class="mt-0.5 rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800"
                                />
                                <span class="ml-2">
                                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ t('payment_conditions_setting_title') }}</span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">{{ t('payment_conditions_setting_help') }}</span>
                                </span>
                            </label>
                            <div v-if="form.show_payment_conditions" class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div>
                                    <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">{{ t('payment_conditions_late_penalty') }}</label>
                                    <input type="text" v-model="form.late_penalty_text" class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">{{ t('payment_conditions_recovery_fee') }}</label>
                                    <input type="number" step="0.01" min="0" v-model="form.recovery_fee_amount" placeholder="40" class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">{{ t('payment_conditions_discount') }}</label>
                                    <input type="text" v-model="form.discount_terms" class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm" />
                                </div>
                            </div>
                        </div>

                        <label class="mt-2 flex items-start cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.show_payment_qrcode"
                                class="mt-0.5 rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800"
                            />
                            <span class="ml-2">
                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ t('show_payment_qrcode') }}</span>
                                <span class="block text-xs text-slate-500 dark:text-slate-400">{{ t('show_payment_qrcode_help') }}</span>
                            </span>
                        </label>

                        <!-- Custom Payment QR Code upload (Payconiq, PayPal, etc.) -->
                        <div v-if="form.show_payment_qrcode" class="mt-4 p-4 rounded-xl bg-slate-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700">
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('payment_qrcode_title') }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">{{ t('payment_qrcode_help') }}</p>

                            <div class="flex flex-col sm:flex-row items-start gap-4">
                                <!-- Preview -->
                                <div class="flex-shrink-0">
                                    <div
                                        v-if="paymentQrcodePreview"
                                        class="w-24 h-24 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden bg-white flex items-center justify-center"
                                    >
                                        <img :src="paymentQrcodePreview" alt="QR Code" class="max-w-full max-h-full object-contain" />
                                    </div>
                                    <div
                                        v-else
                                        class="w-24 h-24 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-700 flex items-center justify-center"
                                    >
                                        <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75H16.5v-.75z" />
                                        </svg>
                                    </div>
                                </div>

                                <!-- Upload controls -->
                                <div class="flex-1">
                                    <input
                                        ref="paymentQrcodeInput"
                                        type="file"
                                        accept="image/png,image/jpeg,image/jpg,image/webp"
                                        class="hidden"
                                        @change="handlePaymentQrcodeSelect"
                                    />

                                    <div v-if="paymentQrcodeForm.payment_qrcode" class="space-y-2">
                                        <p class="text-xs text-slate-600 dark:text-slate-400">
                                            {{ paymentQrcodeForm.payment_qrcode.name }}
                                        </p>
                                        <div class="flex flex-wrap gap-2">
                                            <button
                                                type="button"
                                                @click="uploadPaymentQrcode"
                                                :disabled="paymentQrcodeForm.processing"
                                                class="inline-flex items-center rounded-lg bg-primary-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-primary-500 disabled:opacity-50"
                                            >
                                                <svg v-if="paymentQrcodeForm.processing" class="animate-spin -ml-0.5 mr-1.5 h-3 w-3 text-white" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                {{ t('save') }}
                                            </button>
                                            <button
                                                type="button"
                                                @click="cancelPaymentQrcodeUpload"
                                                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300"
                                            >
                                                {{ t('cancel') }}
                                            </button>
                                        </div>
                                        <InputError :message="paymentQrcodeForm.errors.payment_qrcode" />
                                    </div>

                                    <div v-else class="space-y-2">
                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                            PNG, JPG, WebP - max 1 Mo
                                        </p>
                                        <div class="flex flex-wrap gap-2">
                                            <button
                                                type="button"
                                                @click="selectPaymentQrcode"
                                                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300"
                                            >
                                                <svg class="h-3.5 w-3.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                                </svg>
                                                {{ settings?.payment_qrcode_path ? t('change') : t('add_image') }}
                                            </button>
                                            <button
                                                v-if="settings?.payment_qrcode_path"
                                                type="button"
                                                @click="deletePaymentQrcode"
                                                class="inline-flex items-center rounded-lg border border-pink-300 bg-white px-2.5 py-1.5 text-xs font-medium text-pink-700 shadow-sm hover:bg-pink-50 dark:border-pink-600 dark:bg-gray-800 dark:text-pink-400"
                                            >
                                                <svg class="h-3.5 w-3.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                </div>

                <!-- Peppol e-Invoicing -->
                <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
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
                                    class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
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
                <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
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
                <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
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
                                class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            >
                                <option v-for="option in dynamicVatMentionOptions" :key="option.value" :value="option.value">
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
                                class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
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
                                    :class="form.default_pdf_color === preset.value ? 'border-slate-900 dark:border-white ring-2 ring-offset-2' : 'border-gray-300 dark:border-gray-700 hover:border-slate-400'"
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
                                    class="w-10 h-10 rounded-xl cursor-pointer border border-gray-300 dark:border-gray-700"
                                />
                                <input
                                    type="text"
                                    v-model="form.default_pdf_color"
                                    class="w-28 px-2 py-1 rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm font-mono uppercase"
                                    placeholder="#7c3aed"
                                    maxlength="7"
                                />
                            </div>
                            <InputError :message="form.errors.default_pdf_color" class="mt-2" />
                        </div>

                        <!-- Footer Message -->
                        <div>
                            <InputLabel for="default_invoice_footer" :value="t('default_footer_message')" />
                            <RichTextEditor use-company-link-color v-model="form.default_invoice_footer" class="mt-1" />
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                {{ t('footer_message_help') }}
                            </p>
                            <InputError :message="form.errors.default_invoice_footer" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Contact -->
                <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">
                            {{ t('contact_label') }}
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
                                        class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800"
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
                                        class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800"
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

                <!-- Numbering customization -->
                <NumberingSettingsSection
                    v-model="numberingValues"
                    :editability="props.numbering.editability"
                    :finalized-counts="props.numbering.finalized_counts"
                    :current-year="props.numbering.current_year"
                    :placeholders="props.numbering.placeholders"
                    :default-template="props.numbering.default_template"
                    :errors="form.errors"
                />

                <!-- Actions -->
                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
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
                        class="w-full sm:w-auto justify-center"
                    >
                        <span v-if="form.processing">{{ t('saving') }}</span>
                        <span v-else>{{ t('save') }}</span>
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
