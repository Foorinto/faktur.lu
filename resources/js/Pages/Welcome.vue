<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});

const features = [
    {
        title: 'Facturation conforme Luxembourg',
        description: 'Numérotation séquentielle légale, export FAIA pour l\'AED, et mentions obligatoires automatiques.',
        icon: 'document',
    },
    {
        title: 'Gestion des clients',
        description: 'Fichier clients complet avec validation TVA intracommunautaire et historique des transactions.',
        icon: 'users',
    },
    {
        title: 'Devis professionnels',
        description: 'Créez des devis en quelques clics et convertissez-les en factures instantanément.',
        icon: 'clipboard',
    },
    {
        title: 'Avoirs et corrections',
        description: 'Émettez des avoirs liés à vos factures avec traçabilité complète et conformité légale.',
        icon: 'refresh',
    },
    {
        title: 'Suivi du temps',
        description: 'Saisissez vos heures par projet ou client et facturez automatiquement le temps passé.',
        icon: 'clock',
    },
    {
        title: 'PDF professionnels',
        description: 'Génération automatique de PDF avec modèles personnalisables et envoi par email intégré.',
        icon: 'download',
    },
];

const benefits = [
    {
        title: '100% conforme Luxembourg',
        description: 'Export FAIA pour l\'Administration des contributions directes (AED)',
    },
    {
        title: 'Interface en français',
        description: 'Conçu pour les entrepreneurs francophones du Luxembourg',
    },
    {
        title: 'Simple et intuitif',
        description: 'Prise en main immédiate, sans formation nécessaire',
    },
    {
        title: 'Données sécurisées',
        description: 'Hébergement en Europe avec sauvegardes automatiques',
    },
];
</script>

<template>
    <Head title="Facturation simplifiée pour le Luxembourg" />

    <div class="min-h-screen bg-white dark:bg-gray-900">
        <!-- Header -->
        <header class="fixed top-0 left-0 right-0 z-50 bg-white/95 dark:bg-gray-900/95 backdrop-blur-sm border-b border-gray-100 dark:border-gray-800">
            <nav class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <Link href="/" class="flex items-center space-x-2">
                            <svg class="h-8 w-8 text-indigo-600 dark:text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="text-xl font-bold text-gray-900 dark:text-white">faktur.lu</span>
                        </Link>
                    </div>

                    <!-- Navigation -->
                    <div class="hidden md:flex items-center space-x-8">
                        <a href="#features" class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors">
                            Fonctionnalités
                        </a>
                        <a href="#pricing" class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors">
                            Tarifs
                        </a>
                    </div>

                    <!-- Auth links -->
                    <div v-if="canLogin" class="flex items-center space-x-4">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors"
                        >
                            Tableau de bord
                        </Link>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors"
                            >
                                Connexion
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition-colors"
                            >
                                Créer un compte
                            </Link>
                        </template>
                    </div>
                </div>
            </nav>
        </header>

        <!-- Hero Section -->
        <section class="relative pt-32 pb-20 sm:pt-40 sm:pb-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl md:text-6xl">
                        La facturation
                        <span class="text-indigo-600 dark:text-indigo-400">simplifiée</span>
                        <br />
                        pour le Luxembourg
                    </h1>
                    <p class="mx-auto mt-6 max-w-2xl text-lg text-gray-600 dark:text-gray-300">
                        Créez des factures conformes à la législation luxembourgeoise en quelques clics.
                        Gérez vos clients, devis et avoirs en toute simplicité.
                    </p>
                    <div class="mt-10 flex items-center justify-center gap-x-6">
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="rounded-lg bg-indigo-600 px-6 py-3 text-base font-semibold text-white shadow-lg hover:bg-indigo-500 transition-all hover:shadow-xl"
                        >
                            Commencer gratuitement
                        </Link>
                        <Link
                            v-if="canLogin && !$page.props.auth.user"
                            :href="route('login')"
                            class="text-base font-semibold text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                        >
                            Se connecter <span aria-hidden="true">→</span>
                        </Link>
                    </div>
                </div>

                <!-- Hero illustration -->
                <div class="mt-16 flex justify-center">
                    <div class="relative w-full max-w-3xl">
                        <div class="absolute inset-0 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-2xl blur-3xl opacity-20 dark:opacity-30"></div>
                        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 p-8">
                            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-4 mb-6">
                                <div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Facture</div>
                                    <div class="text-xl font-bold text-gray-900 dark:text-white">F-2026-001</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Total TTC</div>
                                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">1 287,00 €</div>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-300">Développement web - 10h</span>
                                    <span class="text-gray-900 dark:text-white">850,00 €</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-300">Design UI/UX - 5h</span>
                                    <span class="text-gray-900 dark:text-white">250,00 €</span>
                                </div>
                                <div class="border-t border-gray-200 dark:border-gray-700 pt-3 mt-3">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500 dark:text-gray-400">TVA (17%)</span>
                                        <span class="text-gray-600 dark:text-gray-300">187,00 €</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end">
                                <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900/30 px-3 py-1 text-sm font-medium text-green-700 dark:text-green-400">
                                    <svg class="mr-1.5 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    Payée
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="py-20 bg-gray-50 dark:bg-gray-800/50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white sm:text-4xl">
                        Tout ce dont vous avez besoin
                    </h2>
                    <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                        Une solution complète pour gérer votre facturation au Luxembourg
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="relative bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow"
                    >
                        <div class="flex items-center justify-center h-12 w-12 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 mb-4">
                            <!-- Document icon -->
                            <svg v-if="feature.icon === 'document'" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <!-- Users icon -->
                            <svg v-else-if="feature.icon === 'users'" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <!-- Clipboard icon -->
                            <svg v-else-if="feature.icon === 'clipboard'" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <!-- Refresh icon -->
                            <svg v-else-if="feature.icon === 'refresh'" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <!-- Clock icon -->
                            <svg v-else-if="feature.icon === 'clock'" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <!-- Download icon -->
                            <svg v-else-if="feature.icon === 'download'" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            {{ feature.title }}
                        </h3>
                        <p class="text-gray-600 dark:text-gray-300 text-sm">
                            {{ feature.description }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Benefits Section -->
        <section class="py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white sm:text-4xl">
                        Pourquoi choisir faktur.lu ?
                    </h2>
                </div>

                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="benefit in benefits"
                        :key="benefit.title"
                        class="text-center"
                    >
                        <div class="flex items-center justify-center h-16 w-16 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 mx-auto mb-4">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            {{ benefit.title }}
                        </h3>
                        <p class="text-gray-600 dark:text-gray-300 text-sm">
                            {{ benefit.description }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section id="pricing" class="py-20 bg-gray-50 dark:bg-gray-800/50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white sm:text-4xl">
                        Tarification
                    </h2>
                    <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                        Des offres adaptées à vos besoins
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-8 lg:grid-cols-3 max-w-5xl mx-auto">
                    <!-- Plan Gratuit -->
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm border border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Découverte</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Pour démarrer</p>
                        <p class="mt-6">
                            <span class="text-4xl font-bold text-gray-900 dark:text-white">Gratuit</span>
                        </p>
                        <ul class="mt-8 space-y-4">
                            <li class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                                <svg class="h-5 w-5 text-indigo-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                5 factures / mois
                            </li>
                            <li class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                                <svg class="h-5 w-5 text-indigo-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                3 clients
                            </li>
                            <li class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                                <svg class="h-5 w-5 text-indigo-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                Export PDF
                            </li>
                        </ul>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="mt-8 block w-full rounded-lg border border-indigo-600 py-3 text-center text-sm font-semibold text-indigo-600 hover:bg-indigo-50 dark:border-indigo-400 dark:text-indigo-400 dark:hover:bg-indigo-900/20 transition-colors"
                        >
                            Commencer
                        </Link>
                    </div>

                    <!-- Plan Pro -->
                    <div class="relative bg-indigo-600 rounded-2xl p-8 shadow-lg">
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                            <span class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1 text-xs font-semibold text-white">
                                Populaire
                            </span>
                        </div>
                        <h3 class="text-lg font-semibold text-white">Professionnel</h3>
                        <p class="mt-2 text-sm text-indigo-200">Pour les indépendants</p>
                        <p class="mt-6">
                            <span class="text-4xl font-bold text-white">À venir</span>
                        </p>
                        <ul class="mt-8 space-y-4">
                            <li class="flex items-center text-sm text-indigo-100">
                                <svg class="h-5 w-5 text-white mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                Factures illimitées
                            </li>
                            <li class="flex items-center text-sm text-indigo-100">
                                <svg class="h-5 w-5 text-white mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                Clients illimités
                            </li>
                            <li class="flex items-center text-sm text-indigo-100">
                                <svg class="h-5 w-5 text-white mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                Export FAIA
                            </li>
                            <li class="flex items-center text-sm text-indigo-100">
                                <svg class="h-5 w-5 text-white mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                Suivi du temps
                            </li>
                        </ul>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="mt-8 block w-full rounded-lg bg-white py-3 text-center text-sm font-semibold text-indigo-600 hover:bg-indigo-50 transition-colors"
                        >
                            S'inscrire
                        </Link>
                    </div>

                    <!-- Plan Entreprise -->
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm border border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Entreprise</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Pour les équipes</p>
                        <p class="mt-6">
                            <span class="text-4xl font-bold text-gray-900 dark:text-white">À venir</span>
                        </p>
                        <ul class="mt-8 space-y-4">
                            <li class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                                <svg class="h-5 w-5 text-indigo-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                Multi-utilisateurs
                            </li>
                            <li class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                                <svg class="h-5 w-5 text-indigo-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                Gestion des rôles
                            </li>
                            <li class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                                <svg class="h-5 w-5 text-indigo-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                Support prioritaire
                            </li>
                            <li class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                                <svg class="h-5 w-5 text-indigo-500 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                API access
                            </li>
                        </ul>
                        <button
                            disabled
                            class="mt-8 block w-full rounded-lg border border-gray-300 dark:border-gray-600 py-3 text-center text-sm font-semibold text-gray-400 dark:text-gray-500 cursor-not-allowed"
                        >
                            Bientôt disponible
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="relative rounded-3xl bg-indigo-600 px-8 py-16 sm:px-16 sm:py-20 overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-500 to-purple-600"></div>
                    <div class="relative text-center">
                        <h2 class="text-3xl font-bold text-white sm:text-4xl">
                            Prêt à simplifier votre facturation ?
                        </h2>
                        <p class="mt-4 text-lg text-indigo-100">
                            Rejoignez les entrepreneurs luxembourgeois qui font confiance à faktur.lu
                        </p>
                        <div class="mt-8 flex items-center justify-center gap-x-6">
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="rounded-lg bg-white px-6 py-3 text-base font-semibold text-indigo-600 shadow-lg hover:bg-indigo-50 transition-colors"
                            >
                                Créer mon compte gratuit
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-gray-200 dark:border-gray-800 py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex items-center space-x-2">
                        <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">faktur.lu</span>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Conforme aux exigences luxembourgeoises (FAIA / AED)
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        © 2026 faktur.lu. Tous droits réservés.
                    </div>
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
