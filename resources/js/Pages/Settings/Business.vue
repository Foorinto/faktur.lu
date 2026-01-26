<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';

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
    email: props.settings?.email ?? '',
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

watch(() => form.vat_regime, (newValue) => {
    if (newValue === 'franchise') {
        form.vat_number = '';
    }
});

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
    if (confirm('Supprimer le logo ?')) {
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
    <Head title="Réglages - Entreprise" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                Réglages de l'entreprise
            </h1>
        </template>

        <div class="mx-auto max-w-3xl space-y-8">
            <!-- Logo -->
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        Logo
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Votre logo apparaîtra en haut à droite de vos factures.
                    </p>
                </div>
                <div class="px-6 py-4">
                    <div class="flex items-start space-x-6">
                        <!-- Logo preview -->
                        <div class="flex-shrink-0">
                            <div
                                v-if="logoPreview"
                                class="w-32 h-32 rounded-lg border border-gray-200 dark:border-gray-600 overflow-hidden bg-white flex items-center justify-center"
                            >
                                <img
                                    :src="logoPreview"
                                    alt="Logo"
                                    class="max-w-full max-h-full object-contain"
                                />
                            </div>
                            <div
                                v-else
                                class="w-32 h-32 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center"
                            >
                                <svg class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                Enregistrez d'abord les informations de l'entreprise avant d'ajouter un logo.
                            </div>

                            <div v-else-if="logoForm.logo" class="space-y-3">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Nouveau fichier sélectionné : <span class="font-medium">{{ logoForm.logo.name }}</span>
                                </p>
                                <div class="flex space-x-3">
                                    <button
                                        type="button"
                                        @click="uploadLogo"
                                        :disabled="logoForm.processing"
                                        class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                                    >
                                        <svg v-if="logoForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Enregistrer
                                    </button>
                                    <button
                                        type="button"
                                        @click="cancelLogoUpload"
                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                    >
                                        Annuler
                                    </button>
                                </div>
                                <InputError :message="logoForm.errors.logo" />
                            </div>

                            <div v-else class="space-y-3">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Format recommandé : PNG ou SVG avec fond transparent.<br>
                                    Taille maximale : 2 Mo.
                                </p>
                                <div class="flex space-x-3">
                                    <button
                                        type="button"
                                        @click="selectLogo"
                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                    >
                                        <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                        {{ settings?.logo_path ? 'Changer le logo' : 'Ajouter un logo' }}
                                    </button>
                                    <button
                                        v-if="settings?.logo_path"
                                        type="button"
                                        @click="deleteLogo"
                                        class="inline-flex items-center rounded-md border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50 dark:border-red-600 dark:bg-gray-700 dark:text-red-400"
                                    >
                                        <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Supprimer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <!-- Informations légales -->
                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                            Informations légales
                        </h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Ces informations apparaîtront sur vos factures.
                        </p>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="company_name" value="Nom commercial" />
                                <TextInput
                                    id="company_name"
                                    v-model="form.company_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    placeholder="LuxDev Consulting"
                                />
                                <InputError :message="form.errors.company_name" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="legal_name" value="Nom légal complet" />
                                <TextInput
                                    id="legal_name"
                                    v-model="form.legal_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    placeholder="Alexandre Beaudier"
                                />
                                <InputError :message="form.errors.legal_name" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="address" value="Adresse" />
                            <textarea
                                id="address"
                                v-model="form.address"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                rows="2"
                                required
                                placeholder="12 Rue de la Gare"
                            ></textarea>
                            <InputError :message="form.errors.address" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <InputLabel for="postal_code" value="Code postal" />
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
                                <InputLabel for="city" value="Ville" />
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
                                <InputLabel for="country_code" value="Pays" />
                                <select
                                    id="country_code"
                                    v-model="form.country_code"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
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
                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                            Identifiants fiscaux
                        </h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Informations requises par l'administration luxembourgeoise.
                        </p>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="matricule" value="Matricule (11-13 chiffres)" />
                                <TextInput
                                    id="matricule"
                                    v-model="form.matricule"
                                    type="text"
                                    class="mt-1 block w-full font-mono"
                                    required
                                    maxlength="13"
                                    placeholder="1234567890123"
                                />
                                <InputError :message="form.errors.matricule" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="vat_number">
                                    N° TVA intracommunautaire
                                    <span v-if="isVatRequired" class="text-red-500">*</span>
                                    <span v-else class="text-gray-400 text-xs">(optionnel)</span>
                                </InputLabel>
                                <TextInput
                                    id="vat_number"
                                    v-model="form.vat_number"
                                    type="text"
                                    class="mt-1 block w-full font-mono"
                                    :required="isVatRequired"
                                    :disabled="!isVatRequired"
                                    maxlength="14"
                                    placeholder="LU12345678 ou FR12345678901"
                                    :class="{ 'bg-gray-100 dark:bg-gray-600': !isVatRequired }"
                                />
                                <InputError :message="form.errors.vat_number" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Régime TVA" />
                            <div class="mt-2 space-y-3">
                                <label
                                    v-for="regime in vatRegimes"
                                    :key="regime.value"
                                    class="flex items-start p-4 rounded-lg border cursor-pointer transition-colors"
                                    :class="[
                                        form.vat_regime === regime.value
                                            ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20'
                                            : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                                    ]"
                                >
                                    <input
                                        type="radio"
                                        v-model="form.vat_regime"
                                        :value="regime.value"
                                        class="mt-0.5 h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <div class="ml-3">
                                        <span class="block text-sm font-medium text-gray-900 dark:text-white">
                                            {{ regime.label }}
                                        </span>
                                        <span class="block text-sm text-gray-500 dark:text-gray-400">
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
                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                            Coordonnées bancaires
                        </h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Pour le paiement de vos factures.
                        </p>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <InputLabel for="bank_name" value="Nom de la banque (optionnel)" />
                            <TextInput
                                id="bank_name"
                                v-model="form.bank_name"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Hello Bank, BGL BNP Paribas, ..."
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
                                    placeholder="LU12 3456 7890 1234 5678"
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
                                    placeholder="BNPAFRPPXXX"
                                />
                                <InputError :message="form.errors.bic" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarification -->
                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                            Tarification
                        </h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Taux horaire par défaut utilisé lors de la facturation du temps.
                        </p>
                    </div>
                    <div class="px-6 py-4">
                        <div class="max-w-xs">
                            <InputLabel for="default_hourly_rate" value="Taux horaire par défaut (€/h)" />
                            <div class="mt-1 relative rounded-md shadow-sm">
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
                                    <span class="text-gray-500 dark:text-gray-400 sm:text-sm">€/h</span>
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Ce taux sera utilisé par défaut si aucun taux n'est défini sur le client ou l'entrée de temps.
                            </p>
                            <InputError :message="form.errors.default_hourly_rate" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Personnalisation des factures -->
                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                            Personnalisation des factures
                        </h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Messages affichés sur vos factures.
                        </p>
                    </div>
                    <div class="px-6 py-4 space-y-6">
                        <!-- VAT Mention -->
                        <div>
                            <InputLabel for="default_vat_mention" value="Mention TVA par défaut" />
                            <select
                                id="default_vat_mention"
                                v-model="form.default_vat_mention"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                <option v-for="option in vatMentionOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Cette mention apparaîtra en bas du tableau des totaux sur vos factures.
                            </p>
                            <InputError :message="form.errors.default_vat_mention" class="mt-2" />
                        </div>

                        <!-- Custom VAT Mention (shown only when "other" is selected) -->
                        <div v-if="form.default_vat_mention === 'other'">
                            <InputLabel for="default_custom_vat_mention" value="Mention TVA personnalisée" />
                            <textarea
                                id="default_custom_vat_mention"
                                v-model="form.default_custom_vat_mention"
                                rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="Saisissez votre mention TVA personnalisée..."
                            ></textarea>
                            <InputError :message="form.errors.default_custom_vat_mention" class="mt-2" />
                        </div>

                        <!-- PDF Color -->
                        <div>
                            <InputLabel value="Couleur du PDF" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 mb-3">
                                Cette couleur sera utilisée pour les titres, en-têtes de tableau et accents sur vos factures.
                            </p>
                            <div class="flex flex-wrap gap-2 mb-3">
                                <button
                                    v-for="preset in pdfColorPresets"
                                    :key="preset.value"
                                    type="button"
                                    @click="form.default_pdf_color = preset.value"
                                    class="w-10 h-10 rounded-lg border-2 transition-all focus:outline-none focus:ring-2 focus:ring-offset-2"
                                    :class="form.default_pdf_color === preset.value ? 'border-gray-900 dark:border-white ring-2 ring-offset-2' : 'border-gray-300 dark:border-gray-600 hover:border-gray-400'"
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
                                <label class="text-sm text-gray-600 dark:text-gray-400">Couleur personnalisée :</label>
                                <input
                                    type="color"
                                    v-model="form.default_pdf_color"
                                    class="w-10 h-10 rounded cursor-pointer border border-gray-300 dark:border-gray-600"
                                />
                                <input
                                    type="text"
                                    v-model="form.default_pdf_color"
                                    class="w-28 px-2 py-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-mono uppercase"
                                    placeholder="#7c3aed"
                                    maxlength="7"
                                />
                            </div>
                            <InputError :message="form.errors.default_pdf_color" class="mt-2" />
                        </div>

                        <!-- Footer Message -->
                        <div>
                            <InputLabel for="default_invoice_footer" value="Message de pied de page par défaut" />
                            <textarea
                                id="default_invoice_footer"
                                v-model="form.default_invoice_footer"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="Merci pour votre confiance !"
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Ce message apparaîtra en bas de toutes vos factures. Vous pouvez le personnaliser pour chaque facture individuellement.
                            </p>
                            <InputError :message="form.errors.default_invoice_footer" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Contact -->
                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                            Contact
                        </h2>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="email" value="Email" />
                                <TextInput
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    required
                                    placeholder="contact@example.lu"
                                />
                                <InputError :message="form.errors.email" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="phone" value="Téléphone (optionnel)" />
                                <TextInput
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    class="mt-1 block w-full"
                                    placeholder="+352 123 456 789"
                                />
                                <InputError :message="form.errors.phone" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3">
                    <PrimaryButton
                        :disabled="form.processing"
                        :class="{ 'opacity-25': form.processing }"
                    >
                        <span v-if="form.processing">Enregistrement...</span>
                        <span v-else>Enregistrer</span>
                    </PrimaryButton>
                </div>

                <!-- Success message -->
                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-green-600 dark:text-green-400"
                    >
                        Paramètres enregistrés.
                    </p>
                </Transition>
            </form>
        </div>
    </AppLayout>
</template>
