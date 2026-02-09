<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    settings: Object,
    providers: Object,
    smtpFields: Object,
    brevoFields: Object,
    postmarkFields: Object,
    resendFields: Object,
    userEmail: String,
});

const form = useForm({
    provider: props.settings.provider || 'faktur',
    from_address: props.settings.from_address || '',
    from_name: props.settings.from_name || '',
    config: {},
});

const testing = ref(false);
const showPassword = ref({});

// Get current provider config fields
const currentConfigFields = computed(() => {
    switch (form.provider) {
        case 'smtp':
            return props.smtpFields;
        case 'brevo':
            return props.brevoFields;
        case 'postmark':
            return props.postmarkFields;
        case 'resend':
            return props.resendFields;
        default:
            return {};
    }
});

// Initialize config with defaults when provider changes
watch(() => form.provider, (newProvider) => {
    form.config = {};
    const fields = currentConfigFields.value;
    Object.keys(fields).forEach(key => {
        if (fields[key].default !== undefined) {
            form.config[key] = fields[key].default;
        }
    });
}, { immediate: true });

const needsConfiguration = computed(() => {
    return ['smtp', 'brevo', 'postmark', 'resend'].includes(form.provider);
});

const providerDescriptions = {
    faktur: 'Envoi via les serveurs de faktur.lu. Aucune configuration requise.',
    smtp: 'Utilisez votre propre serveur SMTP (Gmail, Office 365, OVH, etc.).',
    brevo: 'Service d\'email marketing et transactionnel (anciennement Sendinblue).',
    postmark: 'Service d\'email transactionnel premium avec excellent taux de délivrabilité.',
    resend: 'API email moderne et simple à configurer.',
};

const submit = () => {
    form.put(route('settings.email.provider.update'), {
        preserveScroll: true,
    });
};

const testConfiguration = () => {
    testing.value = true;
    router.post(route('settings.email.provider.test'), {}, {
        preserveScroll: true,
        onFinish: () => {
            testing.value = false;
        },
    });
};

const togglePassword = (field) => {
    showPassword.value[field] = !showPassword.value[field];
};

const getInputType = (field, fieldConfig) => {
    if (fieldConfig.type === 'password') {
        return showPassword.value[field] ? 'text' : 'password';
    }
    return fieldConfig.type || 'text';
};
</script>

<template>
    <Head title="Configuration Email" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                {{ t('settings') }}
            </h1>
        </template>

        <!-- Settings Navigation -->
        <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
            <nav class="flex space-x-8" aria-label="Settings tabs">
                <Link
                    :href="route('settings.business.edit')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300"
                >
                    {{ t('business_settings') }}
                </Link>
                <Link
                    :href="route('settings.email')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300"
                >
                    {{ t('email_settings') }}
                </Link>
                <Link
                    :href="route('settings.email.provider')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-indigo-500 text-indigo-600 dark:text-indigo-400"
                >
                    Fournisseur Email
                </Link>
                <Link
                    :href="route('settings.accountant')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300"
                >
                    Accès Comptable
                </Link>
            </nav>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Provider Selection -->
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Provider d'envoi</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Choisissez comment envoyer vos factures par email.
                    </p>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div
                        v-for="(label, key) in providers"
                        :key="key"
                        class="relative flex items-start p-4 border rounded-lg cursor-pointer transition-colors"
                        :class="form.provider === key
                            ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 dark:border-indigo-400'
                            : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'"
                        @click="form.provider = key"
                    >
                        <div class="flex items-center h-5">
                            <input
                                :id="'provider-' + key"
                                type="radio"
                                :value="key"
                                v-model="form.provider"
                                class="h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500"
                            />
                        </div>
                        <div class="ml-3">
                            <label :for="'provider-' + key" class="font-medium text-gray-900 dark:text-white cursor-pointer">
                                {{ label }}
                                <span v-if="key === 'faktur'" class="ml-2 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                                    Recommandé
                                </span>
                            </label>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ providerDescriptions[key] }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Provider Configuration -->
            <div v-if="needsConfiguration" class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        Configuration {{ providers[form.provider] }}
                    </h2>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div v-for="(fieldConfig, field) in currentConfigFields" :key="field">
                        <label :for="field" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ fieldConfig.label }}
                            <span v-if="fieldConfig.required" class="text-red-500">*</span>
                        </label>

                        <!-- Select field -->
                        <select
                            v-if="fieldConfig.type === 'select'"
                            :id="field"
                            v-model="form.config[field]"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option v-for="(optLabel, optValue) in fieldConfig.options" :key="optValue" :value="optValue">
                                {{ optLabel }}
                            </option>
                        </select>

                        <!-- Password field with toggle -->
                        <div v-else-if="fieldConfig.type === 'password'" class="relative mt-1">
                            <input
                                :id="field"
                                :type="getInputType(field, fieldConfig)"
                                v-model="form.config[field]"
                                :required="fieldConfig.required"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white pr-10"
                            />
                            <button
                                type="button"
                                @click="togglePassword(field)"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-500"
                            >
                                <svg v-if="showPassword[field]" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                                <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>

                        <!-- Other input fields -->
                        <input
                            v-else
                            :id="field"
                            :type="fieldConfig.type || 'text'"
                            v-model="form.config[field]"
                            :required="fieldConfig.required"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />

                        <!-- Help text -->
                        <p v-if="fieldConfig.help" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ fieldConfig.help }}
                        </p>
                    </div>

                    <p v-if="form.errors.config" class="text-sm text-red-600">
                        {{ form.errors.config }}
                    </p>
                </div>
            </div>

            <!-- From Address -->
            <div v-if="needsConfiguration" class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Expéditeur</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Personnalisez l'adresse et le nom de l'expéditeur.
                    </p>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label for="from_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Adresse email
                        </label>
                        <input
                            id="from_address"
                            type="email"
                            v-model="form.from_address"
                            placeholder="contact@votreentreprise.lu"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                    <div>
                        <label for="from_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Nom de l'expéditeur
                        </label>
                        <input
                            id="from_name"
                            type="text"
                            v-model="form.from_name"
                            placeholder="Votre Entreprise"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                </div>
            </div>

            <!-- Verification Status -->
            <div v-if="settings.provider !== 'faktur'" class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div
                                class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
                                :class="settings.provider_verified ? 'bg-green-100 dark:bg-green-900' : 'bg-yellow-100 dark:bg-yellow-900'"
                            >
                                <svg v-if="settings.provider_verified" class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <svg v-else class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ settings.provider_verified ? 'Configuration vérifiée' : 'Configuration non vérifiée' }}
                                </p>
                                <p v-if="settings.last_test_at" class="text-sm text-gray-500 dark:text-gray-400">
                                    Dernier test : {{ settings.last_test_at }}
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="testConfiguration"
                            :disabled="testing || form.processing"
                            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
                        >
                            <svg v-if="testing" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ testing ? 'Envoi en cours...' : 'Envoyer un email de test' }}
                        </button>
                    </div>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Un email de test sera envoyé à {{ userEmail }}.
                    </p>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end space-x-3">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                >
                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ form.processing ? t('saving') : t('save') }}
                </button>
            </div>
        </form>
    </AppLayout>
</template>
