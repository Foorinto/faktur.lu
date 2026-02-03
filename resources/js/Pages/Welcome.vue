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

const steps = [
    {
        number: '01',
        title: 'Créez votre compte',
        description: 'Inscription gratuite en quelques secondes. Configurez vos informations d\'entreprise.',
    },
    {
        number: '02',
        title: 'Ajoutez vos clients',
        description: 'Importez ou créez votre fichier clients avec leurs informations de facturation.',
    },
    {
        number: '03',
        title: 'Facturez en un clic',
        description: 'Créez des factures conformes, générez le PDF et envoyez par email.',
    },
];

const faqs = [
    {
        question: 'Qu\'est-ce que le format FAIA ?',
        answer: 'Le FAIA (Fichier d\'Audit Informatisé AED) est le format standard exigé par l\'Administration des contributions directes du Luxembourg pour les contrôles fiscaux. faktur.lu génère automatiquement ce fichier à partir de vos factures.',
    },
    {
        question: 'Les factures sont-elles conformes à la législation luxembourgeoise ?',
        answer: 'Oui, toutes les factures générées incluent automatiquement les mentions obligatoires : numéro de TVA, numéro RCS, matricule, numérotation séquentielle, et toutes les informations requises par la loi.',
    },
    {
        question: 'Puis-je créer des avoirs ?',
        answer: 'Oui, vous pouvez créer des avoirs (notes de crédit) liés à vos factures existantes. Le système maintient la traçabilité complète entre factures et avoirs.',
    },
    {
        question: 'Comment fonctionne le suivi du temps ?',
        answer: 'Vous pouvez enregistrer vos heures de travail par client et par projet. Ensuite, en un clic, transformez ces entrées de temps en lignes de facture avec le tarif horaire défini.',
    },
    {
        question: 'Mes données sont-elles sécurisées ?',
        answer: 'Vos données sont hébergées en Europe sur des serveurs sécurisés avec sauvegardes automatiques quotidiennes. L\'accès est protégé par authentification et les connexions sont chiffrées.',
    },
    {
        question: 'Puis-je essayer gratuitement ?',
        answer: 'Oui, le plan Découverte est entièrement gratuit et vous permet de créer jusqu\'à 5 factures par mois pour 3 clients. Idéal pour tester la solution avant de passer à un plan supérieur.',
    },
];

const openFaq = ref(null);

const toggleFaq = (index) => {
    openFaq.value = openFaq.value === index ? null : index;
};
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
                        <a href="#how-it-works" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            Comment ça marche
                        </a>
                        <a href="#pricing" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            Tarifs
                        </a>
                        <a href="#faq" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            FAQ
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
                        <a href="#features" @click="mobileMenuOpen = false" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">Fonctionnalités</a>
                        <a href="#how-it-works" @click="mobileMenuOpen = false" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">Comment ça marche</a>
                        <a href="#pricing" @click="mobileMenuOpen = false" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">Tarifs</a>
                        <a href="#faq" @click="mobileMenuOpen = false" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">FAQ</a>
                        <template v-if="canLogin && !$page.props.auth.user">
                            <Link :href="route('login')" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">Connexion</Link>
                            <Link v-if="canRegister" :href="route('register')" class="bg-[#9b5de5] text-white text-sm font-semibold px-5 py-3 rounded-xl text-center">Créer un compte</Link>
                        </template>
                    </div>
                </div>
            </nav>
        </header>

        <!-- Hero Section -->
        <section class="pt-28 pb-16 sm:pt-36 sm:pb-24 overflow-hidden">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-12 lg:gap-8 items-center">
                    <!-- Left: Text content -->
                    <div>
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

                        <p class="mt-6 text-lg text-slate-600 leading-relaxed">
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

                        <!-- Trust badges -->
                        <div class="mt-12 flex items-center gap-6 text-sm text-slate-500">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-[#00f5d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                Export FAIA
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-[#00f5d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                Données sécurisées
                            </div>
                        </div>
                    </div>

                    <!-- Right: Illustration -->
                    <div class="relative lg:pl-8">
                        <!-- Background decoration -->
                        <div class="absolute -top-8 -right-8 w-72 h-72 bg-[#9b5de5]/10 rounded-full blur-3xl"></div>
                        <div class="absolute -bottom-8 -left-8 w-56 h-56 bg-[#00bbf9]/10 rounded-full blur-3xl"></div>

                        <!-- Main card -->
                        <div class="relative bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-200 p-6">
                            <!-- Invoice preview -->
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="bg-[#9b5de5] p-2.5 rounded-xl">
                                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500">Facture</p>
                                        <p class="font-bold text-slate-900">F-2026-001</p>
                                    </div>
                                </div>
                                <span class="px-3 py-1 rounded-full bg-[#00f5d4]/10 text-[#00a896] text-xs font-medium">Payée</span>
                            </div>

                            <!-- Items -->
                            <div class="space-y-3 mb-6">
                                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-[#00bbf9] flex items-center justify-center text-white text-xs font-bold">10h</div>
                                        <span class="text-sm text-slate-700">Développement web</span>
                                    </div>
                                    <span class="text-sm font-semibold text-slate-900">850 €</span>
                                </div>
                                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-[#f15bb5] flex items-center justify-center text-white text-xs font-bold">5h</div>
                                        <span class="text-sm text-slate-700">Design UI/UX</span>
                                    </div>
                                    <span class="text-sm font-semibold text-slate-900">250 €</span>
                                </div>
                            </div>

                            <!-- Total -->
                            <div class="pt-4 border-t border-slate-100 flex justify-between items-end">
                                <div class="text-xs text-slate-500">TVA 17% : 187 €</div>
                                <div class="text-right">
                                    <p class="text-xs text-slate-500">Total TTC</p>
                                    <p class="text-2xl font-bold text-[#9b5de5]">1 287 €</p>
                                </div>
                            </div>
                        </div>

                        <!-- Floating elements -->
                        <div class="absolute -top-4 -left-4 bg-white rounded-2xl shadow-lg p-3 border border-slate-100">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-[#f15bb5] flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="text-xs">
                                    <p class="text-slate-500">Ce mois</p>
                                    <p class="font-bold text-slate-900">+12 450 €</p>
                                </div>
                            </div>
                        </div>

                        <div class="absolute -bottom-4 -right-4 bg-white rounded-2xl shadow-lg p-3 border border-slate-100">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-[#00f5d4] flex items-center justify-center">
                                    <svg class="w-4 h-4 text-slate-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div class="text-xs">
                                    <p class="text-slate-500">Factures payées</p>
                                    <p class="font-bold text-slate-900">24 / 24</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Logos / Trust Section -->
        <section class="py-12 border-y border-slate-200 bg-white">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">
                <p class="text-center text-sm text-slate-500 mb-8">Conforme aux exigences de</p>
                <div class="flex flex-wrap items-center justify-center gap-x-12 gap-y-6">
                    <div class="flex items-center gap-3 text-slate-400">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                        </svg>
                        <span class="font-medium">Luxembourg AED</span>
                    </div>
                    <div class="flex items-center gap-3 text-slate-400">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14H6v-2h6v2zm4-4H6v-2h10v2zm0-4H6V7h10v2z"/>
                        </svg>
                        <span class="font-medium">Format FAIA</span>
                    </div>
                    <div class="flex items-center gap-3 text-slate-400">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>
                        </svg>
                        <span class="font-medium">RGPD</span>
                    </div>
                    <div class="flex items-center gap-3 text-slate-400">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
                        </svg>
                        <span class="font-medium">TVA Intracommunautaire</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="py-20">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">
                <div class="text-center mb-16">
                    <p class="text-[#f15bb5] font-semibold mb-3">Fonctionnalités</p>
                    <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">
                        Tout ce qu'il vous faut
                    </h2>
                    <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                        Une solution complète pour gérer votre facturation au Luxembourg.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="bg-white rounded-2xl p-6 border border-slate-200 hover:border-slate-300 hover:shadow-lg transition-all"
                    >
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-5" :class="feature.color">
                            <svg v-if="feature.icon === 'document'" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <svg v-else-if="feature.icon === 'users'" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <svg v-else-if="feature.icon === 'clipboard'" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <svg v-else-if="feature.icon === 'refresh'" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <svg v-else-if="feature.icon === 'clock'" class="h-6 w-6 text-slate-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
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

        <!-- How it works Section -->
        <section id="how-it-works" class="py-20 bg-white">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">
                <div class="text-center mb-16">
                    <p class="text-[#00bbf9] font-semibold mb-3">Comment ça marche</p>
                    <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">
                        Facturez en 3 étapes
                    </h2>
                    <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                        Une prise en main rapide pour vous concentrer sur votre activité.
                    </p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <div v-for="step in steps" :key="step.number" class="relative">
                        <div class="text-6xl font-bold text-slate-100 mb-4">{{ step.number }}</div>
                        <h3 class="text-xl font-semibold text-slate-900 mb-2">{{ step.title }}</h3>
                        <p class="text-slate-600">{{ step.description }}</p>

                        <!-- Connector line -->
                        <div v-if="step.number !== '03'" class="hidden md:block absolute top-8 left-full w-full h-0.5 bg-slate-200 -translate-x-1/2"></div>
                    </div>
                </div>

                <div class="mt-12 text-center">
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="inline-flex items-center gap-2 bg-[#9b5de5] hover:bg-[#8b4ed5] text-white font-semibold px-6 py-3.5 rounded-xl transition-colors"
                    >
                        Créer mon compte gratuit
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="py-20">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">
                <div class="bg-[#9b5de5] rounded-3xl p-10 sm:p-12">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
                        <div class="text-center">
                            <p class="text-4xl sm:text-5xl font-bold text-white">100%</p>
                            <p class="mt-2 text-white/70">Conforme FAIA</p>
                        </div>
                        <div class="text-center">
                            <p class="text-4xl sm:text-5xl font-bold text-[#fee440]">17%</p>
                            <p class="mt-2 text-white/70">TVA Luxembourg</p>
                        </div>
                        <div class="text-center">
                            <p class="text-4xl sm:text-5xl font-bold text-white">∞</p>
                            <p class="mt-2 text-white/70">Factures possibles</p>
                        </div>
                        <div class="text-center">
                            <p class="text-4xl sm:text-5xl font-bold text-[#00f5d4]">24/7</p>
                            <p class="mt-2 text-white/70">Accès en ligne</p>
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
                            <span class="px-4 py-1.5 text-xs font-bold bg-[#fee440] text-slate-900 rounded-full">POPULAIRE</span>
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
                        <button disabled class="block w-full py-3.5 text-center font-semibold text-slate-400 border-2 border-slate-200 rounded-xl cursor-not-allowed">
                            Bientôt disponible
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section id="faq" class="py-20">
            <div class="mx-auto max-w-3xl px-6 lg:px-8">
                <div class="text-center mb-16">
                    <p class="text-[#f15bb5] font-semibold mb-3">FAQ</p>
                    <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">
                        Questions fréquentes
                    </h2>
                    <p class="text-lg text-slate-600">
                        Tout ce que vous devez savoir sur faktur.lu
                    </p>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="(faq, index) in faqs"
                        :key="index"
                        class="bg-white rounded-2xl border border-slate-200 overflow-hidden"
                    >
                        <button
                            @click="toggleFaq(index)"
                            class="w-full flex items-center justify-between p-6 text-left"
                        >
                            <span class="font-semibold text-slate-900">{{ faq.question }}</span>
                            <svg
                                class="w-5 h-5 text-slate-500 transition-transform"
                                :class="{ 'rotate-180': openFaq === index }"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div
                            v-show="openFaq === index"
                            class="px-6 pb-6 text-slate-600 leading-relaxed"
                        >
                            {{ faq.answer }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20 bg-white">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">
                <div class="bg-gradient-to-br from-[#9b5de5] to-[#7c3aed] rounded-3xl px-8 py-16 sm:px-16 sm:py-20 text-center relative overflow-hidden">
                    <!-- Decorative elements -->
                    <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                    <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/2"></div>

                    <div class="relative">
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
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-slate-200 py-12">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">
                <div class="grid md:grid-cols-4 gap-8 mb-8">
                    <div class="md:col-span-2">
                        <div class="flex items-center space-x-2.5 mb-4">
                            <div class="bg-[#9b5de5] p-2 rounded-xl">
                                <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <span class="font-bold text-slate-900">faktur.lu</span>
                        </div>
                        <p class="text-slate-600 text-sm max-w-xs">
                            La solution de facturation moderne et conforme pour les entrepreneurs au Luxembourg.
                        </p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-slate-900 mb-4">Produit</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#features" class="text-slate-600 hover:text-slate-900">Fonctionnalités</a></li>
                            <li><a href="#pricing" class="text-slate-600 hover:text-slate-900">Tarifs</a></li>
                            <li><a href="#faq" class="text-slate-600 hover:text-slate-900">FAQ</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-slate-900 mb-4">Conformité</h4>
                        <ul class="space-y-2 text-sm">
                            <li class="text-slate-600">Export FAIA</li>
                            <li class="text-slate-600">TVA Luxembourg</li>
                            <li class="text-slate-600">RGPD</li>
                        </ul>
                    </div>
                </div>
                <div class="pt-8 border-t border-slate-200 flex flex-col md:flex-row items-center justify-between gap-4">
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
                        © 2026 faktur.lu. Tous droits réservés.
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
