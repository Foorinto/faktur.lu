<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    availableFields: Object,
    session: Object,
});

const stepFromStatus = (status) => ({ mapping: 2, preview: 3, completed: 4 })[status] ?? 1;

const stepLabels = computed(() => [
    t('products.import.step_upload'),
    t('products.import.step_mapping'),
    t('products.import.step_preview'),
    t('products.import.step_result'),
]);

const currentStep = ref(props.session ? stepFromStatus(props.session.status) : 1);
const session = ref(props.session);
const file = ref(null);
const uploading = ref(false);
const uploadError = ref(null);
const mapping = ref(props.session?.mapping || {});
const previewResult = ref(null);
const validating = ref(false);
const duplicateStrategy = ref('skip');
const importing = ref(false);
const dragover = ref(false);

const fieldOptions = computed(() => [
    { value: 'ignore', label: t('products.import.ignore_column') },
    ...Object.entries(props.availableFields).map(([key, field]) => ({
        value: key,
        label: field.label + (field.required ? ' *' : ''),
    })),
]);

const detectedCount = computed(
    () => Object.values(mapping.value).filter((v) => v && v !== 'ignore').length,
);

const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

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
        const response = await fetch(route('products.import.upload'), {
            method: 'POST',
            body: formData,
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
        });

        if (!response.ok) {
            const error = await response.json().catch(() => ({}));
            throw new Error(error.message || t('products.import.upload_failed'));
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
        const response = await fetch(route('products.import.mapping', session.value.id), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            body: JSON.stringify({ mapping: mapping.value }),
        });

        const data = await response.json();
        session.value = data.session;
        previewResult.value = data.preview;
        currentStep.value = 3;
    } catch (e) {
        uploadError.value = e.message;
    } finally {
        validating.value = false;
    }
};

const processImport = async () => {
    importing.value = true;
    try {
        const response = await fetch(route('products.import.process', session.value.id), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            body: JSON.stringify({ duplicate_strategy: duplicateStrategy.value }),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || `HTTP ${response.status}`);
        }

        const data = await response.json();
        session.value = data.session;
        currentStep.value = 4;
    } catch (e) {
        uploadError.value = e.message;
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
    uploadError.value = null;
};

// Les lignes refusées au titre du plafond n'ont pas de numéro de ligne : elles
// résument un blocage global plutôt qu'un défaut de la ligne.
const quotaNotice = computed(
    () => (session.value?.errors || []).find((e) => e && e.row === null)?.message ?? null,
);
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2">
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ t('products.import.title') }}
                </h2>
                <Link :href="route('products.index')" class="text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400">
                    ← {{ t('products.import.back') }}
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <!-- Stepper -->
                <!-- Chaque étape est une colonne « pastille + libellé » : le
                     libellé est ainsi centré sous sa pastille par construction.
                     Deux rangées séparées réparties en justify-between ne
                     s'alignent pas, la dernière pastille n'ayant pas de trait
                     de liaison à sa droite. -->
                <div class="mb-8 flex items-start">
                    <template v-for="(label, i) in stepLabels" :key="label">
                        <div class="flex w-20 flex-col items-center">
                            <div
                                :class="[
                                    'flex h-10 w-10 items-center justify-center rounded-full font-semibold transition-colors',
                                    currentStep >= i + 1 ? 'bg-primary-500 text-white' : 'bg-slate-200 text-slate-500 dark:bg-gray-700',
                                ]"
                            >
                                {{ i + 1 }}
                            </div>
                            <span
                                class="mt-2 text-center text-xs"
                                :class="currentStep === i + 1 ? 'font-semibold text-slate-900 dark:text-white' : 'text-slate-500'"
                            >
                                {{ label }}
                            </span>
                        </div>
                        <div
                            v-if="i < stepLabels.length - 1"
                            :class="['mt-5 h-1 flex-1', currentStep > i + 1 ? 'bg-primary-500' : 'bg-slate-200 dark:bg-gray-700']"
                        ></div>
                    </template>
                </div>

                <!-- Étape 1 : dépôt du fichier -->
                <div v-if="currentStep === 1" class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm dark:border-gray-700 dark:bg-surface-card">
                    <h3 class="mb-2 text-lg font-semibold text-slate-900 dark:text-white">
                        {{ t('products.import.step1_title') }}
                    </h3>
                    <p class="mb-6 text-sm text-slate-500 dark:text-slate-400">
                        {{ t('products.import.no_format_needed') }}
                        <a :href="route('products.import.template')" class="font-medium text-primary-500 hover:text-primary-600">
                            {{ t('products.import.download_template') }}
                        </a>
                    </p>

                    <div
                        @dragover.prevent="dragover = true"
                        @dragleave="dragover = false"
                        @drop="handleDrop"
                        :class="[
                            'rounded-xl border-2 border-dashed p-12 text-center transition-colors',
                            dragover ? 'border-primary-500 bg-primary-500/5' : 'border-gray-300 dark:border-gray-600',
                        ]"
                    >
                        <svg class="mx-auto mb-4 h-16 w-16 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                        </svg>

                        <p class="mb-4 text-slate-600 dark:text-slate-400">
                            {{ t('products.import.drop_here') }}
                            <label class="cursor-pointer font-semibold text-primary-500 hover:text-primary-600">
                                {{ t('products.import.browse') }}
                                <input type="file" accept=".xlsx,.xls,.csv,.ods" @change="handleFileSelect" class="hidden" />
                            </label>
                        </p>
                        <p class="text-xs text-slate-500">{{ t('products.import.formats') }}</p>

                        <div v-if="uploading" class="mt-6">
                            <div class="inline-flex items-center text-primary-500">
                                <svg class="mr-2 h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                {{ t('products.import.analyzing') }}
                            </div>
                        </div>

                        <p v-if="uploadError" class="mt-4 text-sm text-red-600">{{ uploadError }}</p>
                    </div>
                </div>

                <!-- Étape 2 : correspondance des colonnes -->
                <div v-if="currentStep === 2" class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm dark:border-gray-700 dark:bg-surface-card">
                    <h3 class="mb-2 text-lg font-semibold text-slate-900 dark:text-white">
                        {{ t('products.import.step2_title') }}
                    </h3>
                    <p class="mb-6 text-sm text-slate-500">
                        {{ t('products.import.detected', { count: detectedCount, total: session.headers.length }) }}
                    </p>

                    <div class="max-h-96 space-y-3 overflow-y-auto">
                        <div v-for="header in session.headers" :key="header" class="grid grid-cols-2 items-center gap-4">
                            <div class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                {{ header }}
                                <p v-if="session.preview_data?.[0]" class="mt-0.5 truncate text-xs text-slate-400">
                                    {{ t('products.import.example') }} {{ session.preview_data[0][session.headers.indexOf(header)] }}
                                </p>
                            </div>
                            <select
                                v-model="mapping[header]"
                                class="rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            >
                                <option v-for="opt in fieldOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-between">
                        <button @click="currentStep = 1" class="text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400">
                            ← {{ t('previous') }}
                        </button>
                        <button
                            @click="saveMapping"
                            :disabled="validating"
                            class="rounded-xl bg-primary-500 px-6 py-2.5 font-semibold text-white hover:bg-primary-600 disabled:opacity-50"
                        >
                            {{ validating ? t('validating') : t('next_preview') }}
                        </button>
                    </div>
                </div>

                <!-- Étape 3 : aperçu -->
                <div v-if="currentStep === 3 && previewResult" class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm dark:border-gray-700 dark:bg-surface-card">
                    <h3 class="mb-6 text-lg font-semibold text-slate-900 dark:text-white">
                        {{ t('products.import.step3_title') }}
                    </h3>

                    <div class="mb-6 grid grid-cols-3 gap-4">
                        <div class="rounded-xl bg-emerald-50 p-4 dark:bg-emerald-900/20">
                            <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ session.valid_rows }}</p>
                            <p class="text-sm text-emerald-600 dark:text-emerald-400">{{ t('products.import.valid_items') }}</p>
                        </div>
                        <div class="rounded-xl bg-amber-50 p-4 dark:bg-amber-900/20">
                            <p class="text-2xl font-bold text-amber-700 dark:text-amber-300">{{ session.duplicate_rows }}</p>
                            <p class="text-sm text-amber-600 dark:text-amber-400">{{ t('products.import.duplicates') }}</p>
                        </div>
                        <div class="rounded-xl bg-red-50 p-4 dark:bg-red-900/20">
                            <p class="text-2xl font-bold text-red-700 dark:text-red-300">{{ session.error_rows }}</p>
                            <p class="text-sm text-red-600 dark:text-red-400">{{ t('products.import.error_rows') }}</p>
                        </div>
                    </div>

                    <!-- Corrections appliquées : ce ne sont pas des erreurs,
                         les lignes seront importées. Les mêler aux erreurs
                         laisserait croire qu'elles sont perdues. -->
                    <div
                        v-if="previewResult.notices?.length"
                        class="mb-6 rounded-xl bg-blue-50 p-4 text-sm text-blue-800 dark:bg-blue-900/20 dark:text-blue-300"
                    >
                        <p v-for="(notice, i) in previewResult.notices" :key="i">{{ notice }}</p>
                    </div>

                    <div v-if="session.duplicate_rows > 0" class="mb-6 rounded-xl bg-amber-50 p-4 dark:bg-amber-900/20">
                        <p class="mb-2 text-sm font-semibold text-amber-900 dark:text-amber-200">{{ t('products.import.duplicate_strategy') }}</p>
                        <div class="space-y-2">
                            <label class="flex items-center text-sm text-amber-800 dark:text-amber-300">
                                <input type="radio" v-model="duplicateStrategy" value="skip" class="mr-2" />
                                {{ t('products.import.strategy_skip') }}
                            </label>
                            <label class="flex items-center text-sm text-amber-800 dark:text-amber-300">
                                <input type="radio" v-model="duplicateStrategy" value="update" class="mr-2" />
                                {{ t('products.import.strategy_update') }}
                            </label>
                            <label class="flex items-center text-sm text-amber-800 dark:text-amber-300">
                                <input type="radio" v-model="duplicateStrategy" value="create" class="mr-2" />
                                {{ t('products.import.strategy_create') }}
                            </label>
                        </div>
                    </div>

                    <div v-if="previewResult.errors?.length" class="mb-6">
                        <p class="mb-2 text-sm font-semibold text-red-600">{{ t('products.import.error_rows') }} ({{ previewResult.errors.length }})</p>
                        <div class="max-h-60 space-y-2 overflow-y-auto rounded-xl bg-red-50 p-3 text-xs dark:bg-red-900/20">
                            <div
                                v-for="(err, i) in previewResult.errors.slice(0, 20)"
                                :key="i"
                                class="border-b border-red-100 pb-1 text-red-700 dark:border-red-900/50 dark:text-red-300"
                            >
                                <strong>{{ t('products.import.line') }} {{ err.row }}</strong> : {{ err.error }}
                                <div class="mt-0.5 text-[10px] text-red-500 dark:text-red-400">
                                    {{ err.data?.designation || t('products.import.no_designation') }}
                                </div>
                            </div>
                            <div v-if="previewResult.errors.length > 20" class="pt-1 italic text-red-500">
                                {{ t('products.import.and_more', { count: previewResult.errors.length - 20 }) }}
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between">
                        <button @click="currentStep = 2" class="text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400">
                            ← {{ t('previous') }}
                        </button>
                        <button
                            @click="processImport"
                            :disabled="importing || session.valid_rows === 0"
                            class="rounded-xl bg-primary-500 px-6 py-2.5 font-semibold text-white hover:bg-primary-600 disabled:opacity-50"
                        >
                            {{ importing ? t('import_in_progress') : t('products.import.import_count', { count: session.valid_rows }) }}
                        </button>
                    </div>
                </div>

                <!-- Étape 4 : résultat -->
                <div v-if="currentStep === 4" class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm dark:border-gray-700 dark:bg-surface-card">
                    <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                        <svg class="h-10 w-10 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>

                    <h3 class="mb-2 text-2xl font-bold text-slate-900 dark:text-white">{{ t('products.import.done_title') }}</h3>

                    <div class="mx-auto mb-8 mt-6 grid max-w-md grid-cols-3 gap-4">
                        <div>
                            <p class="text-3xl font-bold text-emerald-600">{{ session.imported_count }}</p>
                            <p class="text-xs text-slate-500">{{ t('products.import.created') }}</p>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-blue-600">{{ session.updated_count }}</p>
                            <p class="text-xs text-slate-500">{{ t('products.import.updated') }}</p>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-slate-400">{{ session.skipped_count }}</p>
                            <p class="text-xs text-slate-500">{{ t('products.import.skipped') }}</p>
                        </div>
                    </div>

                    <!-- Le plafond du plan n'est pas une erreur de fichier : il
                         mérite son propre encart, sinon il se perd dans la liste. -->
                    <p v-if="quotaNotice" class="mx-auto mb-6 max-w-lg rounded-xl bg-amber-50 p-4 text-sm text-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                        {{ quotaNotice }}
                    </p>

                    <div class="flex flex-col justify-center gap-3 sm:flex-row">
                        <Link :href="route('products.index')" class="rounded-xl bg-primary-500 px-6 py-2.5 font-semibold text-white hover:bg-primary-600">
                            {{ t('products.import.see_catalogue') }}
                        </Link>
                        <button
                            @click="restart"
                            class="rounded-xl border border-gray-300 px-6 py-2.5 font-semibold text-slate-700 hover:bg-gray-50 dark:border-gray-600 dark:text-slate-300 dark:hover:bg-gray-800"
                        >
                            {{ t('products.import.import_another') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
