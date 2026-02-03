<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});

const mobileMenuOpen = ref(false);

const features = [
    {
        title: 'Facturation conforme',
        description: 'Numérotation séquentielle légale et mentions obligatoires automatiques pour le Luxembourg.',
        icon: 'document',
        color: 'bg-[#9b5de5]',
    },
    {
        title: 'Gestion clients',
        description: 'Fichier clients complet avec validation TVA intracommunautaire.',
        icon: 'users',
        color: 'bg-[#00bbf9]',
    },
    {
        title: 'Devis en un clic',
        description: 'Créez des devis professionnels et convertissez-les en factures instantanément.',
        icon: 'clipboard',
        color: 'bg-[#f15bb5]',
    },
    {
        title: 'Avoirs liés',
        description: 'Émettez des avoirs avec traçabilité complète et conformité légale.',
        icon: 'refresh',
        color: 'bg-[#00f5d4]',
    },
    {
        title: 'Suivi du temps',
        description: 'Saisissez vos heures et facturez automatiquement le temps passé.',
        icon: 'clock',
        color: 'bg-[#fee440]',
    },
    {
        title: 'Export FAIA',
        description: 'Exportez vos données au format FAIA pour l\'Administration des contributions.',
        icon: 'download',
        color: 'bg-[#9b5de5]',
    },
];
</script>

<template>
    <Head title="Facturation simplifiée pour le Luxembourg" />

    <div class="min-h-screen bg-slate-50">
        <!-- Header -->
        <header class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-sm border-b border-slate-200">
            <nav class="mx-auto max-w-6xl px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <!-- Logo -->
                    <Link href="/" class="flex items-center space-x-2.5">
                        <div class="bg-[#9b5de5] p-2 rounded-xl">
                            <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <span class="text-lg font-bold text-slate-900">faktur.lu</span>
                    </Link>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center space-x-8">
                        <a href="#features" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            Fonctionnalités
                        </a>
                        <a href="#pricing" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            Tarifs
                        </a>
                    </div>

                    <!-- Auth links -->
                    <div v-if="canLogin" class="hidden md:flex items-center space-x-4">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors"
                        >
                            Tableau de bord
                        </Link>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors"
                            >
                                Connexion
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="bg-[#9b5de5] hover:bg-[#8b4ed5] text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors"
                            >
                                Créer un compte
                            </Link>
                        </template>
                    </div>

                    <!-- Mobile menu button -->
                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="md:hidden p-2 text-slate-600 hover:text-slate-900 rounded-lg hover:bg-slate-100"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Mobile menu -->
                <div v-if="mobileMenuOpen" class="md:hidden py-4 border-t border-slate-200">
                    <div class="flex flex-col space-y-3">
                        <a href="#features" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">
                            Fonctionnalités
                        </a>
                        <a href="#pricing" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">
                            Tarifs
                        </a>
                        <template v-if="canLogin && !$page.props.auth.user">
                            <Link :href="route('login')" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">
                                Connexion
                            </Link>
                            <Link v-if="canRegister" :href="route('register')" class="bg-[#9b5de5] text-white text-sm font-semibold px-5 py-3 rounded-xl text-center">
                                Créer un compte
                            </Link>
                        </template>
                    </div>
                </div>
            </nav>
        </header>

        <!-- Hero Section -->
        <section class="pt-32 pb-16 sm:pt-40 sm:pb-24">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">
                <div class="max-w-3xl">
                    <!-- Badge -->
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#00f5d4]/10 text-[#00a896] text-sm font-medium mb-6">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        Conforme Luxembourg
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-slate-900 leading-tight">
                        La facturation
                        <span class="text-[#9b5de5]">simplifiée</span>
                        pour le Luxembourg
                    </h1>

                    <p class="mt-6 text-lg text-slate-600 leading-relaxed max-w-2xl">
                        Créez des factures conformes en quelques clics. Gérez clients, devis et avoirs depuis une interface moderne et intuitive.
                    </p>

                    <div class="mt-10 flex flex-col sm:flex-row items-start gap-4">
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="inline-flex items-center gap-2 bg-[#9b5de5] hover:bg-[#8b4ed5] text-white font-semibold px-6 py-3.5 rounded-xl transition-colors"
                        >
                            Commencer gratuitement
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </Link>
                        <Link
                            v-if="canLogin && !$page.props.auth.user"
                            :href="route('login')"
                            class="inline-flex items-center gap-2 text-slate-700 hover:text-slate-900 font-medium px-6 py-3.5 transition-colors"
                        >
                            Se connecter
                            <span class="text-[#00bbf9]">→</span>
                        </Link>
                    </div>
                </div>

                <!-- Hero Card -->
                <div class="mt-16 lg:mt-20">
                    <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-200 p-6 sm:p-8 max-w-2xl">
                        <!-- Header -->
                        <div class="flex items-center justify-between pb-6 border-b border-slate-100">
                            <div class="flex items-center gap-4">
                                <div class="bg-[#9b5de5] p-3 rounded-2xl">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm text-slate-500">Facture</p>
                                    <p class="text-lg font-bold text-slate-900">F-2026-001</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-[#00f5d4]/10 text-[#00a896] text-sm font-medium">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                Payée
                            </span>
                        </div>

                        <!-- Content -->
                        <div class="py-6 space-y-3">
                            <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-50">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-[#00bbf9] flex items-center justify-center text-white text-sm font-bold">10h</div>
                                    <span class="text-slate-700">Développement web</span>
                                </div>
                                <span class="text-slate-900 font-semibold">850,00 €</span>
                            </div>
                            <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-50">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-[#f15bb5] flex items-center justify-center text-white text-sm font-bold">5h</div>
                                    <span class="text-slate-700">Design UI/UX</span>
                                </div>
                                <span class="text-slate-900 font-semibold">250,00 €</span>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="pt-6 border-t border-slate-100 flex items-end justify-between">
                            <div>
                                <p class="text-sm text-slate-500">TVA 17%</p>
                                <p class="text-slate-700">187,00 €</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-slate-500">Total TTC</p>
                                <p class="text-3xl font-bold text-[#9b5de5]">1 287,00 €</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="py-20 bg-white">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">
                <div class="max-w-2xl mb-16">
                    <p class="text-[#f15bb5] font-semibold mb-3">Fonctionnalités</p>
                    <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">
                        Tout ce qu'il vous faut
                    </h2>
                    <p class="text-lg text-slate-600">
                        Une solution complète pour gérer votre facturation au Luxembourg.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="bg-slate-50 rounded-2xl p-6 hover:bg-slate-100/80 transition-colors"
                    >
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-5" :class="feature.color">
                            <!-- Document icon -->
                            <svg v-if="feature.icon === 'document'" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <!-- Users icon -->
                            <svg v-else-if="feature.icon === 'users'" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <!-- Clipboard icon -->
                            <svg v-else-if="feature.icon === 'clipboard'" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <!-- Refresh icon -->
                            <svg v-else-if="feature.icon === 'refresh'" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <!-- Clock icon -->
                            <svg v-else-if="feature.icon === 'clock'" class="h-6 w-6 text-slate-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <!-- Download icon -->
                            <svg v-else-if="feature.icon === 'download'" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900 mb-2">
                            {{ feature.title }}
                        </h3>
                        <p class="text-slate-600 leading-relaxed">
                            {{ feature.description }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="py-20">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">
                <div class="bg-white rounded-3xl shadow-lg shadow-slate-200/50 border border-slate-200 p-10 sm:p-12">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
                        <div class="text-center">
                            <p class="text-4xl sm:text-5xl font-bold text-[#9b5de5]">100%</p>
                            <p class="mt-2 text-slate-600">Conforme FAIA</p>
                        </div>
                        <div class="text-center">
                            <p class="text-4xl sm:text-5xl font-bold text-[#f15bb5]">17%</p>
                            <p class="mt-2 text-slate-600">TVA Luxembourg</p>
                        </div>
                        <div class="text-center">
                            <p class="text-4xl sm:text-5xl font-bold text-[#00bbf9]">∞</p>
                            <p class="mt-2 text-slate-600">Factures possibles</p>
                        </div>
                        <div class="text-center">
                            <p class="text-4xl sm:text-5xl font-bold text-[#00f5d4]">24/7</p>
                            <p class="mt-2 text-slate-600">Accès en ligne</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section id="pricing" class="py-20 bg-white">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">
                <div class="text-center mb-16">
                    <p class="text-[#00bbf9] font-semibold mb-3">Tarification</p>
                    <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">
                        Simple et transparent
                    </h2>
                    <p class="text-lg text-slate-600">
                        Des offres adaptées à votre activité.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 max-w-5xl mx-auto">
                    <!-- Plan Gratuit -->
                    <div class="bg-slate-50 rounded-3xl p-8">
                        <div class="mb-6">
                            <h3 class="text-xl font-semibold text-slate-900">Découverte</h3>
                            <p class="text-slate-500 mt-1">Pour démarrer</p>
                        </div>
                        <div class="mb-6">
                            <span class="text-4xl font-bold text-slate-900">Gratuit</span>
                        </div>
                        <ul class="space-y-4 mb-8">
                            <li class="flex items-center gap-3 text-slate-700">
                                <svg class="w-5 h-5 text-[#00f5d4] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                5 factures / mois
                            </li>
                            <li class="flex items-center gap-3 text-slate-700">
                                <svg class="w-5 h-5 text-[#00f5d4] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                3 clients
                            </li>
                            <li class="flex items-center gap-3 text-slate-700">
                                <svg class="w-5 h-5 text-[#00f5d4] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                Export PDF
                            </li>
                        </ul>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="block w-full py-3.5 text-center font-semibold text-slate-700 border-2 border-slate-200 rounded-xl hover:bg-slate-100 transition-colors"
                        >
                            Commencer
                        </Link>
                    </div>

                    <!-- Plan Pro -->
                    <div class="bg-[#9b5de5] rounded-3xl p-8 relative">
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                            <span class="px-4 py-1.5 text-xs font-bold bg-[#fee440] text-slate-900 rounded-full">
                                POPULAIRE
                            </span>
                        </div>
                        <div class="mb-6">
                            <h3 class="text-xl font-semibold text-white">Professionnel</h3>
                            <p class="text-white/70 mt-1">Pour les indépendants</p>
                        </div>
                        <div class="mb-6">
                            <span class="text-4xl font-bold text-white">À venir</span>
                        </div>
                        <ul class="space-y-4 mb-8">
                            <li class="flex items-center gap-3 text-white/90">
                                <svg class="w-5 h-5 text-[#fee440] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                Factures illimitées
                            </li>
                            <li class="flex items-center gap-3 text-white/90">
                                <svg class="w-5 h-5 text-[#fee440] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                Clients illimités
                            </li>
                            <li class="flex items-center gap-3 text-white/90">
                                <svg class="w-5 h-5 text-[#fee440] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                Export FAIA
                            </li>
                            <li class="flex items-center gap-3 text-white/90">
                                <svg class="w-5 h-5 text-[#fee440] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                Suivi du temps
                            </li>
                        </ul>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="block w-full py-3.5 text-center font-semibold text-[#9b5de5] bg-white rounded-xl hover:bg-slate-50 transition-colors"
                        >
                            S'inscrire
                        </Link>
                    </div>

                    <!-- Plan Entreprise -->
                    <div class="bg-slate-50 rounded-3xl p-8">
                        <div class="mb-6">
                            <h3 class="text-xl font-semibold text-slate-900">Entreprise</h3>
                            <p class="text-slate-500 mt-1">Pour les équipes</p>
                        </div>
                        <div class="mb-6">
                            <span class="text-4xl font-bold text-slate-400">À venir</span>
                        </div>
                        <ul class="space-y-4 mb-8">
                            <li class="flex items-center gap-3 text-slate-500">
                                <svg class="w-5 h-5 text-[#00bbf9] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                Multi-utilisateurs
                            </li>
                            <li class="flex items-center gap-3 text-slate-500">
                                <svg class="w-5 h-5 text-[#00bbf9] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                Gestion des rôles
                            </li>
                            <li class="flex items-center gap-3 text-slate-500">
                                <svg class="w-5 h-5 text-[#00bbf9] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                Support prioritaire
                            </li>
                            <li class="flex items-center gap-3 text-slate-500">
                                <svg class="w-5 h-5 text-[#00bbf9] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                API access
                            </li>
                        </ul>
                        <button
                            disabled
                            class="block w-full py-3.5 text-center font-semibold text-slate-400 border-2 border-slate-200 rounded-xl cursor-not-allowed"
                        >
                            Bientôt disponible
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">
                <div class="bg-[#9b5de5] rounded-3xl px-8 py-16 sm:px-16 sm:py-20 text-center">
                    <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">
                        Prêt à simplifier votre facturation ?
                    </h2>
                    <p class="text-lg text-white/80 mb-10 max-w-xl mx-auto">
                        Rejoignez les entrepreneurs luxembourgeois qui font confiance à faktur.lu
                    </p>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="inline-flex items-center gap-2 px-8 py-4 bg-white text-[#9b5de5] font-semibold rounded-xl hover:bg-slate-50 transition-colors"
                    >
                        Créer mon compte gratuit
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-slate-200 py-12 bg-white">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">
                <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="flex items-center space-x-2.5">
                        <div class="bg-[#9b5de5] p-2 rounded-xl">
                            <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <span class="font-semibold text-slate-900">faktur.lu</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#00f5d4]/10 text-[#00a896] text-xs font-medium">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            FAIA
                        </span>
                        Conforme aux exigences luxembourgeoises
                    </div>
                    <p class="text-sm text-slate-500">
                        © 2026 faktur.lu
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>

<style>
html {
    scroll-behavior: smooth;
}
</style>
