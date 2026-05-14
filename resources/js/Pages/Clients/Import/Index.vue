<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    availableFields: Object,
    session: Object,
});

const currentStep = ref(props.session ? (props.session.status === 'mapping' ? 2 : props.session.status === 'preview' ? 3 : props.session.status === 'completed' ? 4 : 1) : 1);
const session = ref(props.session);
const file = ref(null);
const uploading = ref(false);
const uploadError = ref(null);
const mapping = ref(props.session?.mapping || {});
const previewResult = ref(null);
const validating = ref(false);
const duplicateStrategy = ref('skip');
const defaultStatus = ref('prospect');
const importing = ref(false);

const statusOptions = [
    { value: 'prospect', label: 'Prospect' },
    { value: 'contacted', label: 'Contacté' },
    { value: 'discussing', label: 'En discussion' },
    { value: 'active', label: 'Client actif' },
    { value: 'inactive', label: 'Inactif' },
];
const dragover = ref(false);

const fieldOptions = computed(() => {
    return [
        { value: 'ignore', label: 'Ignorer cette colonne' },
        ...Object.entries(props.availableFields).map(([key, field]) => ({
            value: key,
            label: field.label + (field.required ? ' *' : ''),
        })),
    ];
});

const detectedCount = computed(() => {
    return Object.values(mapping.value).filter(v => v && v !== 'ignore').length;
});

const handleFileSelect = (event) => {
    const f = event.target.files?.[0] || event.dataTransfer?.files?.[0];
    if (f) {
        file.value = f;
        upload();
    }
};

const handleDrop = (event) => {
    event.preventDefault();
    dragover.value = false;
    handleFileSelect(event);
};

const upload = async () => {
    if (!file.value) return;
    uploading.value = true;
    uploadError.value = null;

    const formData = new FormData();
    formData.append('file', file.value);

    try {
        const response = await fetch(route('clients.import.upload'), {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Erreur lors de l\'upload');
        }

        const data = await response.json();
        session.value = data.session;
        mapping.value = data.session.mapping || {};
        currentStep.value = 2;
    } catch (e) {
        uploadError.value = e.message;
    } finally {
        uploading.value = false;
    }
};

const saveMapping = async () => {
    validating.value = true;
    try {
        const response = await fetch(route('clients.import.mapping', session.value.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ mapping: mapping.value }),
        });

        const data = await response.json();
        session.value = data.session;
        previewResult.value = data.preview;
        currentStep.value = 3;
    } catch (e) {
        alert('Erreur: ' + e.message);
    } finally {
        validating.value = false;
    }
};

const processImport = async () => {
    importing.value = true;
    try {
        const response = await fetch(route('clients.import.process', session.value.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                duplicate_strategy: duplicateStrategy.value,
                default_status: defaultStatus.value,
            }),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || `Erreur HTTP ${response.status}`);
        }

        const data = await response.json();
        session.value = data.session;
        currentStep.value = 4;
    } catch (e) {
        alert('Erreur: ' + e.message);
    } finally {
        importing.value = false;
    }
};

const restart = () => {
    currentStep.value = 1;
    session.value = null;
    file.value = null;
    mapping.value = {};
    previewResult.value = null;
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">
                    Importer des clients
                </h2>
                <Link :href="route('clients.index')" class="text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400">
                    ← Retour aux clients
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Stepper -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div v-for="step in 4" :key="step" class="flex items-center flex-1">
                            <div :class="[
                                'flex items-center justify-center w-10 h-10 rounded-full font-semibold transition-colors',
                                currentStep >= step
                                    ? 'bg-primary-500 text-white'
                                    : 'bg-slate-200 dark:bg-gray-700 text-slate-500'
                            ]">
                                {{ step }}
                            </div>
                            <div v-if="step < 4" :class="[
                                'flex-1 h-1 mx-2',
                                currentStep > step ? 'bg-primary-500' : 'bg-slate-200 dark:bg-gray-700'
                            ]"></div>
                        </div>
                    </div>
                    <div class="flex justify-between mt-2 text-xs text-slate-500">
                        <span :class="{ 'text-slate-900 font-semibold dark:text-white': currentStep === 1 }">Upload</span>
                        <span :class="{ 'text-slate-900 font-semibold dark:text-white': currentStep === 2 }">Mapping</span>
                        <span :class="{ 'text-slate-900 font-semibold dark:text-white': currentStep === 3 }">Aperçu</span>
                        <span :class="{ 'text-slate-900 font-semibold dark:text-white': currentStep === 4 }">Résultat</span>
                    </div>
                </div>

                <!-- Step 1: Upload -->
                <div v-if="currentStep === 1" class="bg-white dark:bg-surface-card rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-8">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-6">
                        Étape 1 : Téléverser votre fichier
                    </h3>

                    <div
                        @dragover.prevent="dragover = true"
                        @dragleave="dragover = false"
                        @drop="handleDrop"
                        :class="[
                            'border-2 border-dashed rounded-xl p-12 text-center transition-colors',
                            dragover ? 'border-primary-500 bg-primary-500/5' : 'border-gray-300 dark:border-gray-600'
                        ]"
                    >
                        <svg class="w-16 h-16 text-slate-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                        </svg>

                        <p class="text-slate-600 dark:text-slate-400 mb-4">
                            Glissez votre fichier ici ou
                            <label class="text-primary-500 hover:text-primary-600 cursor-pointer font-semibold">
                                cliquez pour parcourir
                                <input type="file" accept=".xlsx,.xls,.csv,.ods" @change="handleFileSelect" class="hidden" />
                            </label>
                        </p>
                        <p class="text-xs text-slate-500">Formats supportés : Excel (.xlsx, .xls), CSV, ODS — Maximum 10 MB</p>

                        <div v-if="uploading" class="mt-6">
                            <div class="inline-flex items-center text-primary-500">
                                <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                Analyse du fichier...
                            </div>
                        </div>

                        <p v-if="uploadError" class="mt-4 text-sm text-red-600">{{ uploadError }}</p>
                    </div>
                </div>

                <!-- Step 2: Mapping -->
                <div v-if="currentStep === 2" class="bg-white dark:bg-surface-card rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-8">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">
                        Étape 2 : Associer les colonnes
                    </h3>
                    <p class="text-sm text-slate-500 mb-6">
                        💡 Détection automatique : {{ detectedCount }}/{{ session.headers.length }} colonnes détectées
                    </p>

                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        <div v-for="header in session.headers" :key="header" class="grid grid-cols-2 gap-4 items-center">
                            <div class="text-sm text-slate-700 dark:text-slate-300 font-medium">
                                {{ header }}
                                <p v-if="session.preview_data?.[0]" class="text-xs text-slate-400 mt-0.5 truncate">
                                    Ex : {{ session.preview_data[0][session.headers.indexOf(header)] }}
                                </p>
                            </div>
                            <select
                                v-model="mapping[header]"
                                class="border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500"
                            >
                                <option v-for="opt in fieldOptions" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-between">
                        <button @click="currentStep = 1" class="text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400">
                            ← Précédent
                        </button>
                        <button
                            @click="saveMapping"
                            :disabled="validating"
                            class="px-6 py-2.5 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-xl disabled:opacity-50"
                        >
                            <span v-if="validating">{{ t('validating') }}</span>
                            <span v-else>{{ t('next_preview') }}</span>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Preview -->
                <div v-if="currentStep === 3 && previewResult" class="bg-white dark:bg-surface-card rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-8">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-6">
                        Étape 3 : Vérifier les données
                    </h3>

                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-4">
                            <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ session.valid_rows }}</p>
                            <p class="text-sm text-emerald-600 dark:text-emerald-400">Clients valides</p>
                        </div>
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-4">
                            <p class="text-2xl font-bold text-amber-700 dark:text-amber-300">{{ session.duplicate_rows }}</p>
                            <p class="text-sm text-amber-600 dark:text-amber-400">Doublons détectés</p>
                        </div>
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-4">
                            <p class="text-2xl font-bold text-red-700 dark:text-red-300">{{ session.error_rows }}</p>
                            <p class="text-sm text-red-600 dark:text-red-400">Lignes en erreur</p>
                        </div>
                    </div>

                    <!-- Statut par défaut -->
                    <div class="mb-6 p-4 bg-slate-50 dark:bg-gray-800 rounded-xl">
                        <label class="block text-sm font-semibold text-slate-900 dark:text-white mb-2">
                            Statut à attribuer aux clients importés :
                        </label>
                        <select v-model="defaultStatus" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500">
                            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>

                    <div v-if="session.duplicate_rows > 0" class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
                        <p class="text-sm font-semibold text-amber-900 dark:text-amber-200 mb-2">Stratégie pour les doublons :</p>
                        <div class="space-y-2">
                            <label class="flex items-center text-sm text-amber-800 dark:text-amber-300">
                                <input type="radio" v-model="duplicateStrategy" value="skip" class="mr-2" />
                                Ignorer (ne pas importer les doublons)
                            </label>
                            <label class="flex items-center text-sm text-amber-800 dark:text-amber-300">
                                <input type="radio" v-model="duplicateStrategy" value="update" class="mr-2" />
                                Mettre à jour les clients existants
                            </label>
                            <label class="flex items-center text-sm text-amber-800 dark:text-amber-300">
                                <input type="radio" v-model="duplicateStrategy" value="create" class="mr-2" />
                                Créer quand même (autoriser les doublons)
                            </label>
                        </div>
                    </div>

                    <div v-if="previewResult.errors?.length" class="mb-6">
                        <p class="text-sm font-semibold text-red-600 mb-2">Erreurs ({{ previewResult.errors.length }}) :</p>
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-3 max-h-60 overflow-y-auto text-xs space-y-2">
                            <div v-for="(err, i) in previewResult.errors.slice(0, 20)" :key="i" class="text-red-700 dark:text-red-300 border-b border-red-100 dark:border-red-900/50 pb-1">
                                <strong>Ligne {{ err.row }}</strong> : {{ err.error }}
                                <div class="text-red-500 dark:text-red-400 text-[10px] mt-0.5">
                                    {{ err.data?.name || '(sans nom)' }}
                                    <span v-if="err.data?.email"> — {{ err.data.email }}</span>
                                </div>
                            </div>
                            <div v-if="previewResult.errors.length > 20" class="text-red-500 italic pt-1">
                                ... et {{ previewResult.errors.length - 20 }} autres
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between">
                        <button @click="currentStep = 2" class="text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400">
                            ← Précédent
                        </button>
                        <button
                            @click="processImport"
                            :disabled="importing || session.valid_rows === 0"
                            class="px-6 py-2.5 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-xl disabled:opacity-50"
                        >
                            <span v-if="importing">{{ t('import_in_progress') }}</span>
                            <span v-else>Importer {{ session.valid_rows }} clients →</span>
                        </button>
                    </div>
                </div>

                <!-- Step 4: Result -->
                <div v-if="currentStep === 4" class="bg-white dark:bg-surface-card rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
                    <div class="w-20 h-20 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>

                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">
                        Import terminé !
                    </h3>
                    <p class="text-slate-600 dark:text-slate-400 mb-8">
                        Vos clients ont été importés avec succès.
                    </p>

                    <div class="grid grid-cols-3 gap-4 mb-8 max-w-md mx-auto">
                        <div>
                            <p class="text-3xl font-bold text-emerald-600">{{ session.imported_count }}</p>
                            <p class="text-xs text-slate-500">Créés</p>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-blue-600">{{ session.updated_count }}</p>
                            <p class="text-xs text-slate-500">Mis à jour</p>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-slate-400">{{ session.skipped_count }}</p>
                            <p class="text-xs text-slate-500">Ignorés</p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <Link :href="route('clients.index')" class="px-6 py-2.5 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-xl">
                            Voir mes clients
                        </Link>
                        <button @click="restart" class="px-6 py-2.5 border border-gray-300 dark:border-gray-600 text-slate-700 dark:text-slate-300 font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800">
                            Importer un autre fichier
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
