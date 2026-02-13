<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const file = ref(null);
const isDragging = ref(false);
const isValidating = ref(false);
const result = ref(null);
const error = ref(null);

const handleDragOver = (e) => {
    e.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = () => {
    isDragging.value = false;
};

const handleDrop = (e) => {
    e.preventDefault();
    isDragging.value = false;

    const files = e.dataTransfer.files;
    if (files.length > 0) {
        selectFile(files[0]);
    }
};

const handleFileSelect = (e) => {
    if (e.target.files.length > 0) {
        selectFile(e.target.files[0]);
    }
};

const selectFile = (selectedFile) => {
    // Check extension
    if (!selectedFile.name.toLowerCase().endsWith('.xml')) {
        error.value = 'Seuls les fichiers .xml sont acceptés.';
        file.value = null;
        return;
    }

    // Check size (50 MB)
    if (selectedFile.size > 50 * 1024 * 1024) {
        error.value = 'Le fichier ne doit pas dépasser 50 Mo.';
        file.value = null;
        return;
    }

    file.value = selectedFile;
    error.value = null;
    result.value = null;
};

const formatFileSize = (bytes) => {
    if (bytes >= 1048576) {
        return (bytes / 1048576).toFixed(2) + ' Mo';
    } else if (bytes >= 1024) {
        return (bytes / 1024).toFixed(2) + ' Ko';
    }
    return bytes + ' octets';
};

const validate = async () => {
    if (!file.value) return;

    isValidating.value = true;
    error.value = null;
    result.value = null;

    const formData = new FormData();
    formData.append('file', file.value);

    try {
        const response = await axios.post(route('faia-validator.validate'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        result.value = response.data;
    } catch (e) {
        if (e.response?.data?.message) {
            error.value = e.response.data.message;
        } else if (e.response?.data?.errors?.file) {
            error.value = e.response.data.errors.file[0];
        } else {
            error.value = 'Erreur de connexion. Veuillez réessayer.';
        }
    } finally {
        isValidating.value = false;
    }
};

const reset = () => {
    file.value = null;
    result.value = null;
    error.value = null;
};

const scoreColor = computed(() => {
    if (!result.value) return 'text-slate-600';
    if (result.value.score >= 80) return 'text-green-500';
    if (result.value.score >= 50) return 'text-yellow-500';
    return 'text-red-500';
});

const scoreBarColor = computed(() => {
    if (!result.value) return 'bg-slate-400';
    if (result.value.score >= 80) return 'bg-green-500';
    if (result.value.score >= 50) return 'bg-yellow-500';
    return 'bg-red-500';
});

const statusIcon = computed(() => {
    if (!result.value) return null;
    if (result.value.status === 'valid') return 'check';
    if (result.value.status === 'valid_with_warnings') return 'warning';
    return 'error';
});
</script>

<template>
    <Head>
        <title>Validateur FAIA gratuit - Vérifiez vos fichiers Luxembourg | faktur.lu</title>
        <meta name="description" content="Validez gratuitement vos fichiers FAIA (Fichier d'Audit Informatisé AED) pour le Luxembourg. Vérification de conformité, structure XML, numérotation des factures et calculs TVA." />
    </Head>

    <MarketingLayout>
        <!-- Main content -->
        <div class="py-12 sm:py-16">
            <div class="mx-auto max-w-4xl px-6 lg:px-8">
                <!-- Title -->
                <div class="text-center mb-10">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#00f5d4]/10 text-[#00a896] text-sm font-medium mb-4">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Outil gratuit
                    </div>
                    <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">
                        Validateur FAIA Luxembourg
                    </h1>
                    <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                        Vérifiez la conformité de vos fichiers d'audit FAIA avant de les soumettre à l'Administration des contributions directes.
                    </p>
                </div>

                <!-- Upload zone -->
                <div
                    v-if="!result"
                    @dragover="handleDragOver"
                    @dragleave="handleDragLeave"
                    @drop="handleDrop"
                    :class="[
                        'relative border-2 border-dashed rounded-2xl p-8 sm:p-12 text-center transition-all cursor-pointer',
                        isDragging
                            ? 'border-[#9b5de5] bg-[#9b5de5]/5'
                            : file
                                ? 'border-[#00f5d4] bg-[#00f5d4]/5'
                                : 'border-slate-300 bg-white hover:border-slate-400',
                    ]"
                    @click="$refs.fileInput.click()"
                >
                    <input
                        ref="fileInput"
                        type="file"
                        accept=".xml"
                        class="hidden"
                        @change="handleFileSelect"
                    />

                    <div v-if="!file" class="space-y-4">
                        <div class="mx-auto w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center">
                            <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-lg font-medium text-slate-700">
                                Glissez votre fichier FAIA ici
                            </p>
                            <p class="text-slate-500 mt-1">
                                ou <span class="text-[#9b5de5] font-medium">cliquez pour parcourir</span>
                            </p>
                        </div>
                        <p class="text-sm text-slate-400">
                            Format accepté : .xml (max 50 Mo)
                        </p>
                    </div>

                    <div v-else class="space-y-4">
                        <div class="mx-auto w-16 h-16 rounded-full bg-[#00f5d4]/20 flex items-center justify-center">
                            <svg class="w-8 h-8 text-[#00a896]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-lg font-medium text-slate-900">{{ file.name }}</p>
                            <p class="text-slate-500">{{ formatFileSize(file.size) }}</p>
                        </div>
                        <button
                            @click.stop="file = null"
                            class="text-sm text-slate-500 hover:text-slate-700"
                        >
                            Changer de fichier
                        </button>
                    </div>
                </div>

                <!-- Error message -->
                <div v-if="error" class="mt-4 p-4 rounded-xl bg-red-50 border border-red-200">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-red-700">{{ error }}</p>
                    </div>
                </div>

                <!-- Validate button -->
                <div v-if="file && !result" class="mt-6 text-center">
                    <button
                        @click="validate"
                        :disabled="isValidating"
                        class="inline-flex items-center gap-2 bg-[#9b5de5] hover:bg-[#8b4ed5] disabled:bg-slate-400 text-white font-semibold px-8 py-3.5 rounded-xl transition-colors"
                    >
                        <svg v-if="isValidating" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ isValidating ? 'Validation en cours...' : 'Valider le fichier' }}
                    </button>
                </div>

                <!-- Results -->
                <div v-if="result" class="mt-8 space-y-6">
                    <!-- Score card -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8">
                        <div class="flex flex-col sm:flex-row items-center gap-6">
                            <!-- Score circle -->
                            <div class="relative">
                                <svg class="w-32 h-32 transform -rotate-90">
                                    <circle
                                        cx="64"
                                        cy="64"
                                        r="56"
                                        stroke="currentColor"
                                        stroke-width="8"
                                        fill="none"
                                        class="text-slate-200"
                                    />
                                    <circle
                                        cx="64"
                                        cy="64"
                                        r="56"
                                        stroke="currentColor"
                                        stroke-width="8"
                                        fill="none"
                                        :class="scoreBarColor"
                                        :stroke-dasharray="`${result.score * 3.52} 352`"
                                        stroke-linecap="round"
                                    />
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span :class="['text-3xl font-bold', scoreColor]">{{ result.score }}%</span>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="flex-1 text-center sm:text-left">
                                <div class="flex items-center justify-center sm:justify-start gap-3 mb-2">
                                    <svg v-if="statusIcon === 'check'" class="w-8 h-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <svg v-else-if="statusIcon === 'warning'" class="w-8 h-8 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <svg v-else class="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <h2 class="text-2xl font-bold text-slate-900">{{ result.statusMessage }}</h2>
                                </div>
                                <p class="text-slate-600">
                                    {{ result.fileInfo?.name }} ({{ result.fileInfo?.sizeFormatted }})
                                </p>
                                <div class="flex items-center justify-center sm:justify-start gap-4 mt-4 text-sm">
                                    <span class="flex items-center gap-1.5">
                                        <span class="w-3 h-3 rounded-full bg-red-500"></span>
                                        {{ result.summary.errors }} erreur(s)
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                                        {{ result.summary.warnings }} avertissement(s)
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <span class="w-3 h-3 rounded-full bg-green-500"></span>
                                        {{ result.summary.info }} info(s)
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Errors -->
                    <div v-if="result.errors.length > 0" class="bg-white rounded-2xl border border-red-200 overflow-hidden">
                        <div class="bg-red-50 px-6 py-4 border-b border-red-200">
                            <h3 class="font-semibold text-red-800 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Erreurs ({{ result.errors.length }})
                            </h3>
                        </div>
                        <ul class="divide-y divide-red-100">
                            <li v-for="(err, index) in result.errors" :key="index" class="px-6 py-4">
                                <div class="flex items-start gap-3">
                                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">
                                        {{ err.category }}
                                    </span>
                                    <span class="text-slate-700">{{ err.message }}</span>
                                    <span v-if="err.line" class="text-slate-400 text-sm">Ligne {{ err.line }}</span>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Warnings -->
                    <div v-if="result.warnings.length > 0" class="bg-white rounded-2xl border border-yellow-200 overflow-hidden">
                        <div class="bg-yellow-50 px-6 py-4 border-b border-yellow-200">
                            <h3 class="font-semibold text-yellow-800 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                Avertissements ({{ result.warnings.length }})
                            </h3>
                        </div>
                        <ul class="divide-y divide-yellow-100">
                            <li v-for="(warn, index) in result.warnings" :key="index" class="px-6 py-4">
                                <div class="flex items-start gap-3">
                                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700">
                                        {{ warn.category }}
                                    </span>
                                    <span class="text-slate-700">{{ warn.message }}</span>
                                    <span v-if="warn.line" class="text-slate-400 text-sm">Ligne {{ warn.line }}</span>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Info -->
                    <div v-if="result.info.length > 0" class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                            <h3 class="font-semibold text-slate-700 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Informations ({{ result.info.length }})
                            </h3>
                        </div>
                        <ul class="divide-y divide-slate-100">
                            <li v-for="(info, index) in result.info" :key="index" class="px-6 py-4">
                                <div class="flex items-start gap-3">
                                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">
                                        {{ info.category }}
                                    </span>
                                    <span class="text-slate-700">{{ info.message }}</span>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <button
                            @click="reset"
                            class="inline-flex items-center gap-2 px-6 py-3 border border-slate-300 rounded-xl text-slate-700 font-medium hover:bg-slate-50 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Valider un autre fichier
                        </button>
                    </div>

                    <!-- CTA -->
                    <div class="bg-gradient-to-br from-[#9b5de5] to-[#7c3aed] rounded-2xl p-8 text-center">
                        <h3 class="text-xl font-bold text-white mb-2">
                            Générez vos fichiers FAIA automatiquement
                        </h3>
                        <p class="text-white/80 mb-6">
                            Créez des factures conformes et exportez au format FAIA en un clic avec faktur.lu
                        </p>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-white text-[#9b5de5] font-semibold rounded-xl hover:bg-slate-50 transition-colors"
                        >
                            Créer un compte gratuit
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </Link>
                    </div>
                </div>

                <!-- Features section -->
                <div v-if="!result" class="mt-16">
                    <h2 class="text-xl font-bold text-slate-900 mb-6 text-center">Validations effectuées</h2>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="bg-white rounded-xl p-5 border border-slate-200">
                            <div class="w-10 h-10 rounded-lg bg-[#9b5de5]/10 flex items-center justify-center mb-3">
                                <svg class="w-5 h-5 text-[#9b5de5]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-slate-900 mb-1">Structure XML</h3>
                            <p class="text-sm text-slate-600">Validation de la syntaxe XML et de l'encodage UTF-8</p>
                        </div>
                        <div class="bg-white rounded-xl p-5 border border-slate-200">
                            <div class="w-10 h-10 rounded-lg bg-[#00bbf9]/10 flex items-center justify-center mb-3">
                                <svg class="w-5 h-5 text-[#00bbf9]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-slate-900 mb-1">Schéma FAIA 2.01</h3>
                            <p class="text-sm text-slate-600">Conformité au schéma XSD officiel de l'AED</p>
                        </div>
                        <div class="bg-white rounded-xl p-5 border border-slate-200">
                            <div class="w-10 h-10 rounded-lg bg-[#f15bb5]/10 flex items-center justify-center mb-3">
                                <svg class="w-5 h-5 text-[#f15bb5]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-slate-900 mb-1">Données société</h3>
                            <p class="text-sm text-slate-600">Vérification du matricule et numéro TVA</p>
                        </div>
                        <div class="bg-white rounded-xl p-5 border border-slate-200">
                            <div class="w-10 h-10 rounded-lg bg-[#00f5d4]/10 flex items-center justify-center mb-3">
                                <svg class="w-5 h-5 text-[#00a896]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-slate-900 mb-1">Numérotation</h3>
                            <p class="text-sm text-slate-600">Détection des ruptures de séquence</p>
                        </div>
                        <div class="bg-white rounded-xl p-5 border border-slate-200">
                            <div class="w-10 h-10 rounded-lg bg-[#fee440]/10 flex items-center justify-center mb-3">
                                <svg class="w-5 h-5 text-[#d4a500]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-slate-900 mb-1">Calculs TVA</h3>
                            <p class="text-sm text-slate-600">Cohérence des totaux HT, TVA et TTC</p>
                        </div>
                        <div class="bg-white rounded-xl p-5 border border-slate-200">
                            <div class="w-10 h-10 rounded-lg bg-[#9b5de5]/10 flex items-center justify-center mb-3">
                                <svg class="w-5 h-5 text-[#9b5de5]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-slate-900 mb-1">Clients & Fournisseurs</h3>
                            <p class="text-sm text-slate-600">Validation des identifiants et TVA</p>
                        </div>
                    </div>
                </div>

                <!-- FAQ / Info section -->
                <div v-if="!result" class="mt-16 bg-white rounded-2xl border border-slate-200 p-8">
                    <h2 class="text-xl font-bold text-slate-900 mb-6">Qu'est-ce que le FAIA ?</h2>
                    <div class="prose prose-slate max-w-none">
                        <p>
                            Le <strong>FAIA (Fichier d'Audit Informatisé AED)</strong> est un format standard XML exigé par
                            l'Administration des contributions directes du Luxembourg pour les contrôles fiscaux des entreprises.
                        </p>
                        <p>
                            Depuis 2015, les entreprises luxembourgeoises utilisant un système informatisé de comptabilité
                            doivent être en mesure de fournir leurs données comptables dans ce format lors d'un contrôle fiscal.
                        </p>
                        <h3>Que contient un fichier FAIA ?</h3>
                        <ul>
                            <li>Informations sur l'entreprise (matricule, TVA, adresse)</li>
                            <li>Plan comptable utilisé</li>
                            <li>Liste des clients et fournisseurs</li>
                            <li>Factures de vente avec leur détail</li>
                            <li>Écritures comptables (journal)</li>
                        </ul>
                        <h3>Ressources officielles</h3>
                        <p>
                            <a href="https://pfi.public.lu/fr/e-file/faia.html" target="_blank" rel="noopener noreferrer" class="text-[#9b5de5] hover:underline">
                                Documentation FAIA - Administration des contributions
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </MarketingLayout>
</template>
