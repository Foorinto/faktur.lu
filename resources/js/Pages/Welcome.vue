<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    appUrl: {
        type: String,
        default: 'https://faktur.lu',
    },
});

// SEO Meta data
const pageTitle = computed(() => t('landing.page_title'));
const metaDescription = computed(() => t('landing.meta_description'));
const canonicalUrl = computed(() => props.appUrl);

// Schema.org structured data
const schemaOrganization = computed(() => JSON.stringify({
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "faktur.lu",
    "url": props.appUrl,
    "logo": `${props.appUrl}/images/logo.png`,
    "description": "Logiciel de facturation conforme pour le Luxembourg",
    "address": {
        "@type": "PostalAddress",
        "addressCountry": "LU"
    },
    "sameAs": []
}));

const schemaSoftware = computed(() => JSON.stringify({
    "@context": "https://schema.org",
    "@type": "SoftwareApplication",
    "name": "faktur.lu",
    "applicationCategory": "BusinessApplication",
    "operatingSystem": "Web",
    "offers": {
        "@type": "Offer",
        "price": "0",
        "priceCurrency": "EUR",
        "description": "Plan Starter gratuit"
    },
    "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.8",
        "ratingCount": "50"
    },
    "featureList": [
        "Factures conformes Luxembourg",
        "Export FAIA pour contrôles fiscaux",
        "TVA automatique 17%",
        "Devis professionnels",
        "Suivi du temps",
        "Gestion de projets"
    ]
}));

// Schema.org FAQPage for SEO
const schemaFAQ = computed(() => JSON.stringify({
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": faqs.value.map(faq => ({
        "@type": "Question",
        "name": faq.question,
        "acceptedAnswer": {
            "@type": "Answer",
            "text": faq.answer
        }
    }))
}));

// Inject JSON-LD scripts on mount
const scriptIds = ['schema-organization', 'schema-software', 'schema-faq'];

onMounted(() => {
    // Remove any existing scripts first
    scriptIds.forEach(id => {
        const existing = document.getElementById(id);
        if (existing) existing.remove();
    });

    // Inject Organization schema
    const orgScript = document.createElement('script');
    orgScript.id = 'schema-organization';
    orgScript.type = 'application/ld+json';
    orgScript.textContent = schemaOrganization.value;
    document.head.appendChild(orgScript);

    // Inject SoftwareApplication schema
    const softwareScript = document.createElement('script');
    softwareScript.id = 'schema-software';
    softwareScript.type = 'application/ld+json';
    softwareScript.textContent = schemaSoftware.value;
    document.head.appendChild(softwareScript);

    // Inject FAQPage schema
    const faqScript = document.createElement('script');
    faqScript.id = 'schema-faq';
    faqScript.type = 'application/ld+json';
    faqScript.textContent = schemaFAQ.value;
    document.head.appendChild(faqScript);
});

onUnmounted(() => {
    // Clean up scripts on unmount
    scriptIds.forEach(id => {
        const script = document.getElementById(id);
        if (script) script.remove();
    });
});

const mobileMenuOpen = ref(false);
const billingPeriod = ref('monthly');

// Quick highlight features (6 main ones for hero area)
const highlightFeatures = computed(() => [
    {
        title: t('landing.features.items.invoicing.title'),
        description: t('landing.features.items.invoicing.description'),
        icon: 'document',
        color: 'bg-[#9b5de5]',
    },
    {
        title: t('landing.features.items.clients.title'),
        description: t('landing.features.items.clients.description'),
        icon: 'users',
        color: 'bg-[#00bbf9]',
    },
    {
        title: t('landing.features.items.quotes.title'),
        description: t('landing.features.items.quotes.description'),
        icon: 'clipboard',
        color: 'bg-[#f15bb5]',
    },
    {
        title: t('landing.features.items.credit_notes.title'),
        description: t('landing.features.items.credit_notes.description'),
        icon: 'refresh',
        color: 'bg-[#00f5d4]',
    },
    {
        title: t('landing.features.items.time_tracking.title'),
        description: t('landing.features.items.time_tracking.description'),
        icon: 'clock',
        color: 'bg-[#fee440]',
    },
    {
        title: t('landing.features.items.faia.title'),
        description: t('landing.features.items.faia.description'),
        icon: 'download',
        color: 'bg-[#9b5de5]',
    },
]);

// Feature categories with all functionalities
const featureCategories = computed(() => [
    {
        id: 'invoicing',
        title: t('landing.features.categories.invoicing.title'),
        color: 'bg-[#9b5de5]',
        icon: 'document',
        items: [
            { title: t('landing.features.categories.invoicing.items.invoices.title'), description: t('landing.features.categories.invoicing.items.invoices.description'), icon: 'document' },
            { title: t('landing.features.categories.invoicing.items.quotes.title'), description: t('landing.features.categories.invoicing.items.quotes.description'), icon: 'clipboard' },
            { title: t('landing.features.categories.invoicing.items.credit_notes.title'), description: t('landing.features.categories.invoicing.items.credit_notes.description'), icon: 'refresh' },
            { title: t('landing.features.categories.invoicing.items.recurring.title'), description: t('landing.features.categories.invoicing.items.recurring.description'), icon: 'calendar' },
            { title: t('landing.features.categories.invoicing.items.multi_currency.title'), description: t('landing.features.categories.invoicing.items.multi_currency.description'), icon: 'currency' },
            { title: t('landing.features.categories.invoicing.items.email.title'), description: t('landing.features.categories.invoicing.items.email.description'), icon: 'mail' },
        ],
    },
    {
        id: 'compliance',
        title: t('landing.features.categories.compliance.title'),
        color: 'bg-[#00f5d4]',
        icon: 'shield',
        items: [
            { title: t('landing.features.categories.compliance.items.faia.title'), description: t('landing.features.categories.compliance.items.faia.description'), icon: 'download', badge: 'FAIA' },
            { title: t('landing.features.categories.compliance.items.peppol.title'), description: t('landing.features.categories.compliance.items.peppol.description'), icon: 'globe', badge: 'Bientôt' },
            { title: t('landing.features.categories.compliance.items.vat.title'), description: t('landing.features.categories.compliance.items.vat.description'), icon: 'calculator' },
            { title: t('landing.features.categories.compliance.items.archive.title'), description: t('landing.features.categories.compliance.items.archive.description'), icon: 'archive' },
            { title: t('landing.features.categories.compliance.items.audit.title'), description: t('landing.features.categories.compliance.items.audit.description'), icon: 'eye' },
            { title: t('landing.features.categories.compliance.items.vies.title'), description: t('landing.features.categories.compliance.items.vies.description'), icon: 'check-circle' },
        ],
    },
    {
        id: 'management',
        title: t('landing.features.categories.management.title'),
        color: 'bg-[#00bbf9]',
        icon: 'briefcase',
        items: [
            { title: t('landing.features.categories.management.items.clients.title'), description: t('landing.features.categories.management.items.clients.description'), icon: 'users' },
            { title: t('landing.features.categories.management.items.projects.title'), description: t('landing.features.categories.management.items.projects.description'), icon: 'folder' },
            { title: t('landing.features.categories.management.items.time_tracking.title'), description: t('landing.features.categories.management.items.time_tracking.description'), icon: 'clock' },
            { title: t('landing.features.categories.management.items.expenses.title'), description: t('landing.features.categories.management.items.expenses.description'), icon: 'receipt' },
            { title: t('landing.features.categories.management.items.dashboard.title'), description: t('landing.features.categories.management.items.dashboard.description'), icon: 'chart' },
            { title: t('landing.features.categories.management.items.revenue_book.title'), description: t('landing.features.categories.management.items.revenue_book.description'), icon: 'book' },
        ],
    },
    {
        id: 'security',
        title: t('landing.features.categories.security.title'),
        color: 'bg-[#f15bb5]',
        icon: 'lock',
        items: [
            { title: t('landing.features.categories.security.items.two_factor.title'), description: t('landing.features.categories.security.items.two_factor.description'), icon: 'key' },
            { title: t('landing.features.categories.security.items.accountant.title'), description: t('landing.features.categories.security.items.accountant.description'), icon: 'user-group' },
            { title: t('landing.features.categories.security.items.encryption.title'), description: t('landing.features.categories.security.items.encryption.description'), icon: 'shield' },
            { title: t('landing.features.categories.security.items.backup.title'), description: t('landing.features.categories.security.items.backup.description'), icon: 'cloud' },
        ],
    },
]);

const activeCategory = ref('invoicing');

// Legacy features array for backwards compatibility
const features = highlightFeatures;

const steps = computed(() => [
    {
        number: '01',
        title: t('landing.how_it_works.steps.step1.title'),
        description: t('landing.how_it_works.steps.step1.description'),
    },
    {
        number: '02',
        title: t('landing.how_it_works.steps.step2.title'),
        description: t('landing.how_it_works.steps.step2.description'),
    },
    {
        number: '03',
        title: t('landing.how_it_works.steps.step3.title'),
        description: t('landing.how_it_works.steps.step3.description'),
    },
]);

const faqs = computed(() => [
    {
        question: t('landing.faq.items.faia.question'),
        answer: t('landing.faq.items.faia.answer'),
    },
    {
        question: t('landing.faq.items.compliant.question'),
        answer: t('landing.faq.items.compliant.answer'),
    },
    {
        question: t('landing.faq.items.credit_notes.question'),
        answer: t('landing.faq.items.credit_notes.answer'),
    },
    {
        question: t('landing.faq.items.time_tracking.question'),
        answer: t('landing.faq.items.time_tracking.answer'),
    },
    {
        question: t('landing.faq.items.security.question'),
        answer: t('landing.faq.items.security.answer'),
    },
    {
        question: t('landing.faq.items.free_trial.question'),
        answer: t('landing.faq.items.free_trial.answer'),
    },
]);

const openFaq = ref(null);

const toggleFaq = (index) => {
    openFaq.value = openFaq.value === index ? null : index;
};
</script>

<template>
    <Head :title="pageTitle">
        <!-- Primary Meta Tags -->
        <meta name="description" :content="metaDescription" />
        <meta name="keywords" :content="t('landing.meta_keywords')" />
        <link rel="canonical" :href="canonicalUrl" />

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website" />
        <meta property="og:url" :content="canonicalUrl" />
        <meta property="og:title" :content="pageTitle" />
        <meta property="og:description" :content="metaDescription" />
        <meta property="og:image" :content="`${appUrl}/images/og-image.png`" />
        <meta property="og:locale" content="fr_LU" />
        <meta property="og:site_name" content="faktur.lu" />

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" :content="canonicalUrl" />
        <meta name="twitter:title" :content="pageTitle" />
        <meta name="twitter:description" :content="metaDescription" />
        <meta name="twitter:image" :content="`${appUrl}/images/og-image.png`" />
    </Head>

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
                            {{ t('landing.nav.features') }}
                        </a>
                        <a href="#how-it-works" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            {{ t('landing.nav.how_it_works') }}
                        </a>
                        <a href="#pricing" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            {{ t('landing.nav.pricing') }}
                        </a>
                        <a href="#faq" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            {{ t('landing.nav.faq') }}
                        </a>
                        <Link :href="route('faia-validator')" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            {{ t('landing.nav.faia_validator') }}
                        </Link>
                    </div>

                    <!-- Auth links -->
                    <div v-if="canLogin" class="hidden md:flex items-center space-x-4">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors"
                        >
                            {{ t('landing.nav.dashboard') }}
                        </Link>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors"
                            >
                                {{ t('landing.nav.login') }}
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="bg-[#9b5de5] hover:bg-[#8b4ed5] text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors"
                            >
                                {{ t('landing.nav.create_account') }}
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
                        <a href="#features" @click="mobileMenuOpen = false" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">{{ t('landing.nav.features') }}</a>
                        <a href="#how-it-works" @click="mobileMenuOpen = false" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">{{ t('landing.nav.how_it_works') }}</a>
                        <a href="#pricing" @click="mobileMenuOpen = false" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">{{ t('landing.nav.pricing') }}</a>
                        <a href="#faq" @click="mobileMenuOpen = false" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">{{ t('landing.nav.faq') }}</a>
                        <Link :href="route('faia-validator')" @click="mobileMenuOpen = false" class="text-sm font-medium text-[#9b5de5] hover:text-[#8b4ed5] py-2">{{ t('landing.nav.faia_validator') }}</Link>
                        <template v-if="canLogin && !$page.props.auth.user">
                            <Link :href="route('login')" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">{{ t('landing.nav.login') }}</Link>
                            <Link v-if="canRegister" :href="route('register')" class="bg-[#9b5de5] text-white text-sm font-semibold px-5 py-3 rounded-xl text-center">{{ t('landing.nav.create_account') }}</Link>
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
                            {{ t('landing.hero.badge') }}
                        </div>

                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-slate-900 leading-tight">
                            {{ t('landing.hero.title_1') }}
                            <span class="text-[#9b5de5]">{{ t('landing.hero.title_2') }}</span>
                            {{ t('landing.hero.title_3') }}
                        </h1>

                        <p class="mt-6 text-lg text-slate-600 leading-relaxed">
                            {{ t('landing.hero.subtitle') }}
                        </p>

                        <div class="mt-10 flex flex-col sm:flex-row items-start gap-4">
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="inline-flex items-center gap-2 bg-[#9b5de5] hover:bg-[#8b4ed5] text-white font-semibold px-6 py-3.5 rounded-xl transition-colors"
                            >
                                {{ t('landing.hero.cta_start') }}
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </Link>
                            <Link
                                v-if="canLogin && !$page.props.auth.user"
                                :href="route('login')"
                                class="inline-flex items-center gap-2 text-slate-700 hover:text-slate-900 font-medium px-6 py-3.5 transition-colors"
                            >
                                {{ t('landing.hero.cta_login') }}
                                <span class="text-[#00bbf9]">→</span>
                            </Link>
                        </div>

                        <!-- Trust badges -->
                        <div class="mt-12 flex items-center gap-6 text-sm text-slate-500">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-[#00f5d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                {{ t('landing.hero.badge_faia') }}
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-[#00f5d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                {{ t('landing.hero.badge_secure') }}
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
                                        <p class="text-xs text-slate-500">{{ t('landing.preview.invoice') }}</p>
                                        <p class="font-bold text-slate-900">F-2026-001</p>
                                    </div>
                                </div>
                                <span class="px-3 py-1 rounded-full bg-[#00f5d4]/10 text-[#00a896] text-xs font-medium">{{ t('landing.preview.paid') }}</span>
                            </div>

                            <!-- Items -->
                            <div class="space-y-3 mb-6">
                                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-[#00bbf9] flex items-center justify-center text-white text-xs font-bold">10h</div>
                                        <span class="text-sm text-slate-700">{{ t('landing.preview.web_dev') }}</span>
                                    </div>
                                    <span class="text-sm font-semibold text-slate-900">850 €</span>
                                </div>
                                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-[#f15bb5] flex items-center justify-center text-white text-xs font-bold">5h</div>
                                        <span class="text-sm text-slate-700">{{ t('landing.preview.ui_design') }}</span>
                                    </div>
                                    <span class="text-sm font-semibold text-slate-900">250 €</span>
                                </div>
                            </div>

                            <!-- Total -->
                            <div class="pt-4 border-t border-slate-100 flex justify-between items-end">
                                <div class="text-xs text-slate-500">{{ t('landing.preview.vat') }} : 187 €</div>
                                <div class="text-right">
                                    <p class="text-xs text-slate-500">{{ t('landing.preview.total_ttc') }}</p>
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
                                    <p class="text-slate-500">{{ t('landing.preview.this_month') }}</p>
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
                                    <p class="text-slate-500">{{ t('landing.preview.invoices_paid') }}</p>
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
                <p class="text-center text-sm text-slate-500 mb-8">{{ t('landing.trust.compliant_with') }}</p>
                <div class="flex flex-wrap items-center justify-center gap-x-12 gap-y-6">
                    <div class="flex items-center gap-3 text-slate-400">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                        </svg>
                        <span class="font-medium">{{ t('landing.trust.luxembourg_aed') }}</span>
                    </div>
                    <div class="flex items-center gap-3 text-slate-400">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14H6v-2h6v2zm4-4H6v-2h10v2zm0-4H6V7h10v2z"/>
                        </svg>
                        <span class="font-medium">{{ t('landing.trust.faia_format') }}</span>
                    </div>
                    <div class="flex items-center gap-3 text-slate-400">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>
                        </svg>
                        <span class="font-medium">{{ t('landing.trust.gdpr') }}</span>
                    </div>
                    <div class="flex items-center gap-3 text-slate-400">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
                        </svg>
                        <span class="font-medium">{{ t('landing.trust.intra_vat') }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="py-20">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">
                <div class="text-center mb-12">
                    <p class="text-[#f15bb5] font-semibold mb-3">{{ t('landing.features.title') }}</p>
                    <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">
                        {{ t('landing.features.heading') }}
                    </h2>
                    <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                        {{ t('landing.features.subtitle') }}
                    </p>
                </div>

                <!-- Category tabs -->
                <div class="flex flex-wrap justify-center gap-2 mb-12">
                    <button
                        v-for="category in featureCategories"
                        :key="category.id"
                        @click="activeCategory = category.id"
                        :class="[
                            'px-5 py-2.5 rounded-xl text-sm font-medium transition-all',
                            activeCategory === category.id
                                ? `${category.color} text-white shadow-lg`
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                        ]"
                    >
                        {{ category.title }}
                    </button>
                </div>

                <!-- Features grid for active category -->
                <div v-for="category in featureCategories" :key="category.id">
                    <div v-show="activeCategory === category.id" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <article
                            v-for="(item, index) in category.items"
                            :key="index"
                            class="bg-white rounded-2xl p-6 border border-slate-200 hover:border-slate-300 hover:shadow-lg transition-all"
                        >
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-5" :class="category.color">
                                    <!-- Icons -->
                                <svg v-if="item.icon === 'document'" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <svg v-else-if="item.icon === 'clipboard'" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <svg v-else-if="item.icon === 'refresh'" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <svg v-else-if="item.icon === 'calendar'" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <svg v-else-if="item.icon === 'currency'" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <svg v-else-if="item.icon === 'mail'" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <svg v-else-if="item.icon === 'download'" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    <svg v-else-if="item.icon === 'globe'" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                    </svg>
                                    <svg v-else-if="item.icon === 'calculator'" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    <svg v-else-if="item.icon === 'archive'" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                    </svg>
                                    <svg v-else-if="item.icon === 'eye'" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg v-else-if="item.icon === 'check-circle'" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <svg v-else-if="item.icon === 'users'" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <svg v-else-if="item.icon === 'folder'" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                    </svg>
                                    <svg v-else-if="item.icon === 'clock'" class="h-5 w-5 text-slate-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <svg v-else-if="item.icon === 'receipt'" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" />
                                    </svg>
                                    <svg v-else-if="item.icon === 'chart'" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    <svg v-else-if="item.icon === 'book'" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    <svg v-else-if="item.icon === 'key'" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                    </svg>
                                    <svg v-else-if="item.icon === 'user-group'" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <svg v-else-if="item.icon === 'shield'" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <svg v-else-if="item.icon === 'cloud'" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                                    </svg>
                                    <svg v-else class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-slate-900 mb-2 flex items-center gap-2">
                                {{ item.title }}
                                <span v-if="item.badge" class="px-2 py-0.5 text-xs font-medium rounded-full" :class="item.badge === 'FAIA' ? 'bg-[#00f5d4]/20 text-[#00a896]' : 'bg-slate-200 text-slate-600'">
                                    {{ item.badge }}
                                </span>
                            </h3>
                            <p class="text-slate-600 leading-relaxed">{{ item.description }}</p>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <!-- How it works Section -->
        <section id="how-it-works" class="py-20 bg-slate-50">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">
                <div class="text-center mb-16">
                    <p class="text-[#00bbf9] font-semibold mb-3">{{ t('landing.how_it_works.title') }}</p>
                    <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">
                        {{ t('landing.how_it_works.heading') }}
                    </h2>
                    <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                        {{ t('landing.how_it_works.subtitle') }}
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
                        {{ t('landing.how_it_works.cta') }}
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
                            <p class="mt-2 text-white/70">{{ t('landing.stats.faia_compliant') }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-4xl sm:text-5xl font-bold text-[#fee440]">17%</p>
                            <p class="mt-2 text-white/70">{{ t('landing.stats.vat_luxembourg') }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-4xl sm:text-5xl font-bold text-white">∞</p>
                            <p class="mt-2 text-white/70">{{ t('landing.stats.unlimited_invoices') }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-4xl sm:text-5xl font-bold text-[#00f5d4]">24/7</p>
                            <p class="mt-2 text-white/70">{{ t('landing.stats.online_access') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section id="pricing" class="py-20 bg-white">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">
                <div class="text-center mb-12">
                    <p class="text-[#00bbf9] font-semibold mb-3">{{ t('landing.pricing.title') }}</p>
                    <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">
                        {{ t('landing.pricing.heading') }}
                    </h2>
                    <p class="text-lg text-slate-600">
                        {{ t('landing.pricing.subtitle') }}
                    </p>

                    <!-- Billing toggle -->
                    <div class="mt-8 flex justify-center items-center gap-4">
                        <span
                            :class="[
                                'text-sm font-medium transition-colors',
                                billingPeriod === 'monthly' ? 'text-slate-900' : 'text-slate-500'
                            ]"
                        >
                            Mensuel
                        </span>
                        <button
                            @click="billingPeriod = billingPeriod === 'monthly' ? 'yearly' : 'monthly'"
                            class="relative inline-flex h-7 w-12 items-center rounded-full transition-colors"
                            :class="billingPeriod === 'yearly' ? 'bg-[#9b5de5]' : 'bg-slate-300'"
                        >
                            <span
                                class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"
                                :class="billingPeriod === 'yearly' ? 'translate-x-6' : 'translate-x-1'"
                            />
                        </button>
                        <span
                            :class="[
                                'text-sm font-medium transition-colors',
                                billingPeriod === 'yearly' ? 'text-slate-900' : 'text-slate-500'
                            ]"
                        >
                            Annuel
                        </span>
                        <span
                            v-if="billingPeriod === 'yearly'"
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-[#00f5d4]/20 text-[#00a896]"
                        >
                            -17% (2 mois offerts)
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-4xl mx-auto">
                    <!-- Plan Gratuit -->
                    <div class="bg-slate-50 rounded-3xl p-8">
                        <div class="mb-6">
                            <h3 class="text-xl font-semibold text-slate-900">{{ t('landing.pricing.plans.discovery.name') }}</h3>
                            <p class="text-slate-500 mt-1">{{ t('landing.pricing.plans.discovery.description') }}</p>
                        </div>
                        <div class="mb-6">
                            <span class="text-4xl font-bold text-slate-900">{{ t('landing.pricing.plans.discovery.price') }}</span>
                            <span class="text-slate-500 ml-1">pour toujours</span>
                        </div>
                        <ul class="space-y-4 mb-8">
                            <li class="flex items-center gap-3 text-slate-700">
                                <svg class="w-5 h-5 text-[#00f5d4] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ t('landing.pricing.plans.discovery.features.0') }}
                            </li>
                            <li class="flex items-center gap-3 text-slate-700">
                                <svg class="w-5 h-5 text-[#00f5d4] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ t('landing.pricing.plans.discovery.features.1') }}
                            </li>
                            <li class="flex items-center gap-3 text-slate-700">
                                <svg class="w-5 h-5 text-[#00f5d4] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ t('landing.pricing.plans.discovery.features.2') }}
                            </li>
                        </ul>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="block w-full py-3.5 text-center font-semibold text-slate-700 border-2 border-slate-200 rounded-xl hover:bg-slate-100 transition-colors"
                        >
                            {{ t('landing.pricing.start') }}
                        </Link>
                    </div>

                    <!-- Plan Pro -->
                    <div class="bg-[#9b5de5] rounded-3xl p-8 relative">
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                            <span class="px-4 py-1.5 text-xs font-bold bg-[#fee440] text-slate-900 rounded-full">{{ t('landing.pricing.popular') }}</span>
                        </div>
                        <div class="mb-6">
                            <h3 class="text-xl font-semibold text-white">{{ t('landing.pricing.plans.professional.name') }}</h3>
                            <p class="text-white/70 mt-1">{{ t('landing.pricing.plans.professional.description') }}</p>
                        </div>
                        <div class="mb-6">
                            <span class="text-4xl font-bold text-white">
                                {{ billingPeriod === 'yearly' ? '5,83€' : '7€' }}
                            </span>
                            <span class="text-white/70 ml-1">/mois HT</span>
                            <p v-if="billingPeriod === 'yearly'" class="text-sm text-white/60 mt-1">
                                70€ facturé annuellement
                            </p>
                        </div>
                        <ul class="space-y-4 mb-8">
                            <li class="flex items-center gap-3 text-white/90">
                                <svg class="w-5 h-5 text-[#fee440] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ t('landing.pricing.plans.professional.features.0') }}
                            </li>
                            <li class="flex items-center gap-3 text-white/90">
                                <svg class="w-5 h-5 text-[#fee440] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ t('landing.pricing.plans.professional.features.1') }}
                            </li>
                            <li class="flex items-center gap-3 text-white/90">
                                <svg class="w-5 h-5 text-[#fee440] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ t('landing.pricing.plans.professional.features.2') }}
                            </li>
                            <li class="flex items-center gap-3 text-white/90">
                                <svg class="w-5 h-5 text-[#fee440] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ t('landing.pricing.plans.professional.features.3') }}
                            </li>
                        </ul>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="block w-full py-3.5 text-center font-semibold text-[#9b5de5] bg-white rounded-xl hover:bg-slate-50 transition-colors"
                        >
                            {{ t('landing.pricing.sign_up') }}
                        </Link>
                    </div>
                </div>

                <!-- Feature Comparison Table -->
                <div class="mt-16 max-w-4xl mx-auto">
                    <h3 class="text-xl font-semibold text-slate-900 text-center mb-8">
                        Comparatif détaillé des fonctionnalités
                    </h3>
                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200">
                                    <th class="text-left py-4 px-6 text-sm font-semibold text-slate-900">Fonctionnalité</th>
                                    <th class="text-center py-4 px-6 text-sm font-semibold text-slate-900 w-28">Starter</th>
                                    <th class="text-center py-4 px-6 text-sm font-semibold text-[#9b5de5] w-28">Pro</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <!-- Limites -->
                                <tr class="bg-slate-50/50">
                                    <td colspan="3" class="py-3 px-6 text-xs font-semibold text-slate-500 uppercase tracking-wider">Limites</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-6 text-sm text-slate-700">Clients</td>
                                    <td class="py-3 px-6 text-center text-sm text-slate-600">2</td>
                                    <td class="py-3 px-6 text-center text-sm font-medium text-[#9b5de5]">Illimité</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-6 text-sm text-slate-700">Factures / mois</td>
                                    <td class="py-3 px-6 text-center text-sm text-slate-600">2</td>
                                    <td class="py-3 px-6 text-center text-sm font-medium text-[#9b5de5]">Illimité</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-6 text-sm text-slate-700">Devis / mois</td>
                                    <td class="py-3 px-6 text-center text-sm text-slate-600">2</td>
                                    <td class="py-3 px-6 text-center text-sm font-medium text-[#9b5de5]">Illimité</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-6 text-sm text-slate-700">Emails / mois</td>
                                    <td class="py-3 px-6 text-center text-sm text-slate-600">2</td>
                                    <td class="py-3 px-6 text-center text-sm font-medium text-[#9b5de5]">Illimité</td>
                                </tr>

                                <!-- Fonctionnalités de base -->
                                <tr class="bg-slate-50/50">
                                    <td colspan="3" class="py-3 px-6 text-xs font-semibold text-slate-500 uppercase tracking-wider">Fonctionnalités de base</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-6 text-sm text-slate-700">Factures conformes Luxembourg</td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-[#00f5d4] mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-[#00f5d4] mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-6 text-sm text-slate-700">Devis professionnels</td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-[#00f5d4] mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-[#00f5d4] mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-6 text-sm text-slate-700">Gestion des clients</td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-[#00f5d4] mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-[#00f5d4] mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-6 text-sm text-slate-700">Avoirs / notes de crédit</td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-[#00f5d4] mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-[#00f5d4] mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-6 text-sm text-slate-700">Suivi du temps</td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-[#00f5d4] mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-[#00f5d4] mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-6 text-sm text-slate-700">Suivi des dépenses</td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-[#00f5d4] mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-[#00f5d4] mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-6 text-sm text-slate-700">Authentification 2FA</td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-[#00f5d4] mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-[#00f5d4] mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </td>
                                </tr>

                                <!-- Fonctionnalités Pro -->
                                <tr class="bg-slate-50/50">
                                    <td colspan="3" class="py-3 px-6 text-xs font-semibold text-slate-500 uppercase tracking-wider">Fonctionnalités Pro</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-6 text-sm text-slate-700">Export FAIA (contrôle fiscal)</td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-[#00f5d4] mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-6 text-sm text-slate-700">Archivage PDF/A 10 ans</td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-[#00f5d4] mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-6 text-sm text-slate-700">Relances automatiques impayés</td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-[#00f5d4] mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-6 text-sm text-slate-700">Accès comptable dédié</td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-[#00f5d4] mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-6 text-sm text-slate-700">Sans mention "faktur.lu"</td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-[#00f5d4] mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-6 text-sm text-slate-700">Support email prioritaire</td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <svg class="w-5 h-5 text-[#00f5d4] mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section id="faq" class="py-20">
            <div class="mx-auto max-w-3xl px-6 lg:px-8">
                <div class="text-center mb-16">
                    <p class="text-[#f15bb5] font-semibold mb-3">{{ t('landing.faq.title') }}</p>
                    <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">
                        {{ t('landing.faq.heading') }}
                    </h2>
                    <p class="text-lg text-slate-600">
                        {{ t('landing.faq.subtitle') }}
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
                            {{ t('landing.cta.heading') }}
                        </h2>
                        <p class="text-lg text-white/80 mb-10 max-w-xl mx-auto">
                            {{ t('landing.cta.subtitle') }}
                        </p>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="inline-flex items-center gap-2 px-8 py-4 bg-white text-[#9b5de5] font-semibold rounded-xl hover:bg-slate-50 transition-colors"
                        >
                            {{ t('landing.cta.button') }}
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
                <div class="grid md:grid-cols-5 gap-8 mb-8">
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
                            {{ t('landing.footer.description') }}
                        </p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-slate-900 mb-4">{{ t('landing.footer.product') }}</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#features" class="text-slate-600 hover:text-slate-900">{{ t('landing.nav.features') }}</a></li>
                            <li><a href="#pricing" class="text-slate-600 hover:text-slate-900">{{ t('landing.nav.pricing') }}</a></li>
                            <li><a href="#faq" class="text-slate-600 hover:text-slate-900">{{ t('landing.nav.faq') }}</a></li>
                            <li><Link :href="route('faia-validator')" class="text-slate-600 hover:text-slate-900">{{ t('landing.nav.faia_validator') }}</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-slate-900 mb-4">{{ t('landing.footer.compliance') }}</h4>
                        <ul class="space-y-2 text-sm">
                            <li class="text-slate-600">{{ t('landing.footer.faia_export') }}</li>
                            <li class="text-slate-600">{{ t('landing.footer.vat_luxembourg') }}</li>
                            <li class="text-slate-600">{{ t('landing.footer.gdpr') }}</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-slate-900 mb-4">Légal</h4>
                        <ul class="space-y-2 text-sm">
                            <li><Link :href="route('legal.mentions')" class="text-slate-600 hover:text-slate-900">Mentions légales</Link></li>
                            <li><Link :href="route('legal.privacy')" class="text-slate-600 hover:text-slate-900">Confidentialité</Link></li>
                            <li><Link :href="route('legal.terms')" class="text-slate-600 hover:text-slate-900">CGU / CGV</Link></li>
                            <li><Link :href="route('legal.cookies')" class="text-slate-600 hover:text-slate-900">Cookies</Link></li>
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
                        {{ t('landing.footer.faia_compliant') }}
                    </div>
                    <p class="text-sm text-slate-500">
                        {{ t('landing.footer.copyright') }}
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
