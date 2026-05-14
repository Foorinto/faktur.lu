<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted } from 'vue';
import SeoHead from '@/Components/SeoHead.vue';
import FlagIcon from '@/Components/FlagIcon.vue';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';

const { t } = useTranslations();
const { localizedRoute } = useLocalizedRoute();
const appUrl = computed(() => usePage().props.appUrl || 'https://faktur.lu');

const schemaBreadcrumb = computed(() => JSON.stringify({
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        { "@type": "ListItem", "position": 1, "name": "faktur.lu", "item": appUrl.value },
        { "@type": "ListItem", "position": 2, "name": t('about.breadcrumb') },
    ],
}));

const schemaAboutOrganization = computed(() => JSON.stringify({
    "@context": "https://schema.org",
    "@type": ["Organization", "LocalBusiness"],
    "@id": `${appUrl.value}/#organization`,
    "name": "faktur.lu",
    "identifier": {
        "@type": "PropertyValue",
        "propertyID": "Wikidata",
        "value": "Q139674760"
    },
    "url": appUrl.value,
    "logo": `${appUrl.value}/images/logo.png`,
    "description": t('landing.schema_software_description'),
    "foundingDate": "2026",
    "slogan": t('landing.schema_slogan'),
    "address": {
        "@type": "PostalAddress",
        "addressLocality": "Luxembourg",
        "addressRegion": "Luxembourg",
        "postalCode": "L-1855",
        "addressCountry": "LU"
    },
    "areaServed": [
        { "@type": "Country", "name": "Luxembourg" },
        { "@type": "Country", "name": "Belgium" },
        { "@type": "Country", "name": "France" },
        { "@type": "Country", "name": "Germany" }
    ],
    "knowsLanguage": ["fr", "de", "en", "lb", "pt"],
    "knowsAbout": [
        "Luxembourg VAT",
        "FAIA reporting",
        "Peppol e-invoicing",
        "Factur-X",
        "AED fiscal compliance"
    ],
    "sameAs": [
        "https://www.linkedin.com/company/faktur-lu/",
        "https://www.trustpilot.com/review/faktur.lu",
        "https://www.wikidata.org/wiki/Q139674760"
    ]
}));

onMounted(() => {
    document.getElementById('schema-breadcrumb')?.remove();
    const breadcrumbScript = document.createElement('script');
    breadcrumbScript.id = 'schema-breadcrumb';
    breadcrumbScript.type = 'application/ld+json';
    breadcrumbScript.textContent = schemaBreadcrumb.value;
    document.head.appendChild(breadcrumbScript);

    document.getElementById('schema-about-org')?.remove();
    const orgScript = document.createElement('script');
    orgScript.id = 'schema-about-org';
    orgScript.type = 'application/ld+json';
    orgScript.textContent = schemaAboutOrganization.value;
    document.head.appendChild(orgScript);
});

onUnmounted(() => {
    document.getElementById('schema-breadcrumb')?.remove();
    document.getElementById('schema-about-org')?.remove();
});

const values = [
    { icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', key: 'compliance' },
    { icon: 'M13 10V3L4 14h7v7l9-11h-7z', key: 'simplicity' },
    { icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', key: 'security' },
    { icon: 'M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9', key: 'local' },
];

const roadmap = computed(() => [
    { status: 'shipped', title: t('about.roadmap.shipped.title'), description: t('about.roadmap.shipped.description') },
    { status: 'in_progress', title: t('about.roadmap.in_progress.title'), description: t('about.roadmap.in_progress.description') },
    { status: 'planned', title: t('about.roadmap.planned.title'), description: t('about.roadmap.planned.description') },
]);
</script>

<template>
    <SeoHead
        :title="t('about.page_title')"
        :description="t('about.meta_description')"
        canonical-path="/a-propos"
        route-name="about"
    />

    <MarketingLayout>
        <!-- Breadcrumb -->
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-4">
            <nav class="flex text-sm text-slate-500">
                <Link :href="localizedRoute('home')" class="hover:text-slate-900 transition-colors">{{ t('features.breadcrumb.home') }}</Link>
                <span class="mx-2">/</span>
                <span class="text-slate-900 font-medium">{{ t('about.breadcrumb') }}</span>
            </nav>
        </div>

        <!-- Hero -->
        <section class="py-16 sm:py-24">
            <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary-500/10 text-primary-500 text-sm font-medium mb-6">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    {{ t('about.badge') }}
                </div>
                <h1 class="text-4xl sm:text-5xl font-bold text-slate-900">
                    {{ t('about.title') }}
                </h1>
                <p class="mt-6 text-xl text-slate-600 leading-relaxed">
                    {{ t('about.subtitle') }}
                </p>
            </div>
        </section>

        <!-- Mission -->
        <section class="pb-16">
            <div class="max-w-4xl mx-auto px-6 lg:px-8">
                <div class="bg-white rounded-2xl p-8 sm:p-12 shadow-sm border border-gray-200">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4">{{ t('about.mission_title') }}</h2>
                    <p class="text-lg text-slate-600 leading-relaxed">
                        {{ t('about.mission_text') }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Founder / Team section (humanise vs concurrent anonyme) -->
        <section class="pb-20 bg-white py-20">
            <div class="max-w-4xl mx-auto px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-slate-900 text-center mb-4">{{ t('about.team.title') }}</h2>
                <p class="text-slate-600 text-center max-w-2xl mx-auto mb-12">{{ t('about.team.subtitle') }}</p>

                <div class="bg-gradient-to-br from-primary-50 to-[#00f5d4]/10 rounded-3xl p-8 sm:p-12">
                    <div class="flex flex-col sm:flex-row items-center sm:items-start gap-8">
                        <!-- Avatar placeholder (initiales) -->
                        <div class="flex-shrink-0 w-32 h-32 rounded-full bg-primary-500 flex items-center justify-center text-white text-4xl font-bold shadow-lg">
                            FB
                        </div>
                        <div class="flex-1 text-center sm:text-left">
                            <h3 class="text-2xl font-bold text-slate-900 mb-1">{{ t('about.team.founder_name') }}</h3>
                            <p class="text-primary-600 font-medium mb-4">{{ t('about.team.founder_role') }}</p>
                            <p class="text-slate-700 leading-relaxed mb-4">{{ t('about.team.founder_bio') }}</p>
                            <div class="flex flex-wrap gap-2 justify-center sm:justify-start">
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-white text-slate-700 text-sm">
                                    <svg class="w-4 h-4 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    {{ t('about.team.tag_luxembourg') }}
                                </span>
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-white text-slate-700 text-sm">
                                    <svg class="w-4 h-4 text-[#00a896]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                    {{ t('about.team.tag_compliance') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Values -->
        <section class="pt-12 pb-20">
            <div class="max-w-6xl mx-auto px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-slate-900 text-center mb-12">{{ t('about.values_title') }}</h2>
                <div class="grid sm:grid-cols-2 gap-8">
                    <div
                        v-for="value in values"
                        :key="value.key"
                        class="bg-white rounded-2xl p-8 shadow-sm border border-gray-200"
                    >
                        <div class="w-12 h-12 bg-primary-500/10 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="value.icon" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-slate-900 mb-2">{{ t(`about.values.${value.key}.title`) }}</h3>
                        <p class="text-slate-600">{{ t(`about.values.${value.key}.description`) }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Localisation Luxembourg -->
        <section class="pb-20 py-20">
            <div class="max-w-6xl mx-auto px-6 lg:px-8">
                <div class="grid md:grid-cols-2 gap-10 items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-slate-900 mb-4">{{ t('about.location.title') }}</h2>
                        <p class="text-slate-600 leading-relaxed mb-6">{{ t('about.location.text') }}</p>
                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-primary-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                <span class="text-slate-700">{{ t('about.location.address') }}</span>
                            </div>
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-primary-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                <a href="mailto:contact@faktur.lu" class="text-slate-700 hover:text-primary-600">{{ t('about.location.email') }}</a>
                            </div>
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-primary-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span class="text-slate-700">{{ t('about.location.languages') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-primary-100 to-primary-50 rounded-3xl p-12 text-center">
                        <FlagIcon code="lb" class="w-32 h-20 mb-4 mx-auto shadow-md" />
                        <h3 class="text-2xl font-bold text-slate-900 mb-2">{{ t('about.location.country_title') }}</h3>
                        <p class="text-slate-600">{{ t('about.location.country_text') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Roadmap publique -->
        <section class="pb-20 bg-white py-20">
            <div class="max-w-4xl mx-auto px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-slate-900 text-center mb-4">{{ t('about.roadmap.title') }}</h2>
                <p class="text-slate-600 text-center max-w-2xl mx-auto mb-12">{{ t('about.roadmap.subtitle') }}</p>
                <div class="space-y-4">
                    <div v-for="(item, i) in roadmap" :key="i" class="bg-slate-50 rounded-2xl p-6 border border-gray-200">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <span v-if="item.status === 'shipped'" class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    {{ t('about.roadmap.status_shipped') }}
                                </span>
                                <span v-else-if="item.status === 'in_progress'" class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold">
                                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                    {{ t('about.roadmap.status_in_progress') }}
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">
                                    {{ t('about.roadmap.status_planned') }}
                                </span>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-slate-900 mb-1">{{ item.title }}</h3>
                                <p class="text-slate-600 text-sm">{{ item.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Why Luxembourg -->
        <section class="pb-20 bg-slate-50 py-20">
            <div class="max-w-4xl mx-auto px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-slate-900 text-center mb-6">{{ t('about.why_luxembourg_title') }}</h2>
                <p class="text-lg text-slate-600 text-center leading-relaxed mb-10">
                    {{ t('about.why_luxembourg_text') }}
                </p>
                <div class="grid sm:grid-cols-4 gap-6">
                    <div class="text-center p-6 bg-white rounded-2xl border border-gray-200">
                        <div class="text-3xl font-bold text-primary-500">2026</div>
                        <div class="mt-2 text-sm text-slate-600">{{ t('about.stats.since') }}</div>
                    </div>
                    <div class="text-center p-6 bg-white rounded-2xl border border-gray-200">
                        <div class="text-3xl font-bold text-primary-500">100%</div>
                        <div class="mt-2 text-sm text-slate-600">{{ t('about.stats.compliant') }}</div>
                    </div>
                    <div class="text-center p-6 bg-white rounded-2xl border border-gray-200">
                        <div class="text-3xl font-bold text-primary-500">5</div>
                        <div class="mt-2 text-sm text-slate-600">{{ t('about.stats.languages') }}</div>
                    </div>
                    <div class="text-center p-6 bg-white rounded-2xl border border-gray-200">
                        <div class="text-3xl font-bold text-primary-500">FAIA</div>
                        <div class="mt-2 text-sm text-slate-600">{{ t('about.stats.faia') }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="py-20 bg-primary-500">
            <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-bold text-white">{{ t('about.cta_title') }}</h2>
                <p class="mt-4 text-lg text-white/80">{{ t('about.cta_subtitle') }}</p>
                <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                    <Link :href="route('register')" class="inline-flex items-center justify-center px-8 py-4 bg-white text-primary-500 font-semibold rounded-xl text-lg hover:bg-gray-50 transition-colors">
                        {{ t('landing.hero.cta_start') }}
                    </Link>
                    <Link :href="localizedRoute('contact')" class="inline-flex items-center justify-center px-8 py-4 border-2 border-white/30 text-white font-semibold rounded-xl text-lg hover:bg-white/10 transition-colors">
                        {{ t('about.cta_contact') }}
                    </Link>
                </div>
            </div>
        </section>
    </MarketingLayout>
</template>
