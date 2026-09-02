<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import SchemaJsonLd from '@/Components/SchemaJsonLd.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';
import { useMarque } from "@/Composables/useMarque";

const { nom: marqueNom, url: marqueUrl } = useMarque();

const { t } = useTranslations();
const { localizedRoute, currentLocale } = useLocalizedRoute();
const page = usePage();
const appUrl = computed(() => page.props.appUrl || marqueUrl.value);

const pains = computed(() => [
    { key: 'silos' },
    { key: 'team' },
    { key: 'compliance' },
    { key: 'cashflow' },
]);

const features = computed(() => [
    { key: 'unlimited' },
    { key: 'crm' },
    { key: 'hr' },
    { key: 'accountant' },
    { key: 'peppol' },
    { key: 'archive' },
    { key: 'reminders' },
    { key: 'branding' },
]);

const faqs = computed(() => ['q1', 'q2', 'q3', 'q4', 'q5', 'q6'].map((key) => ({
    q: t('for_smes.faq.' + key + '.q'),
    a: t('for_smes.faq.' + key + '.a'),
})));

const schemas = computed(() => [
    {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: [
            { '@type': 'ListItem', position: 1, name: marqueNom.value, item: appUrl.value + '/' + currentLocale() + '/' },
            { '@type': 'ListItem', position: 2, name: t('for_smes.breadcrumb') },
        ],
    },
    {
        '@context': 'https://schema.org',
        '@type': 'WebPage',
        name: t('for_smes.hero_title'),
        description: t('for_smes.meta_description'),
        url: appUrl.value + localizedRoute('for_smes'),
        dateModified: new Date().toISOString().slice(0, 10),
        inLanguage: currentLocale(),
        audience: {
            '@type': 'BusinessAudience',
            audienceType: 'Small and medium enterprises (SMEs)',
            geographicArea: { '@type': 'Country', name: 'Luxembourg', sameAs: 'https://www.wikidata.org/wiki/Q32' },
        },
        about: { '@type': 'Thing', name: 'Invoice', sameAs: 'https://www.wikidata.org/wiki/Q330362' },
        provider: {
            '@type': 'Organization',
            '@id': appUrl.value + '/#organization',
            name: marqueNom.value,
            url: appUrl.value + '/',
            sameAs: ['https://www.wikidata.org/wiki/Q139674760'],
        },
    },
    {
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        mainEntity: faqs.value.map((f) => ({
            '@type': 'Question',
            name: f.q,
            acceptedAnswer: { '@type': 'Answer', text: f.a },
        })),
    },
]);
</script>

<template>
    <SeoHead
        :title="t('for_smes.page_title')"
        :description="t('for_smes.meta_description')"
        canonical-path="/pour-pme"
        route-name="for_smes"
    />
    <SchemaJsonLd :schemas="schemas" />

    <MarketingLayout>
        <div class="py-12 sm:py-16">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">

                <!-- Breadcrumb -->
                <nav class="mb-8 text-sm">
                    <Link :href="localizedRoute('home')" class="text-slate-500 hover:text-slate-700">{{ marqueNom }}</Link>
                    <span class="text-slate-400 mx-2">/</span>
                    <span class="text-slate-900">{{ t('for_smes.breadcrumb') }}</span>
                </nav>

                <!-- Hero -->
                <div class="text-center mb-12">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#00bbf9]/10 text-[#00bbf9] text-sm font-medium mb-6">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" /></svg>
                        {{ t('for_smes.badge') }}
                    </div>
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 leading-tight max-w-4xl mx-auto mb-6">
                        {{ t('for_smes.hero_title') }}
                    </h1>
                    <p class="text-lg text-slate-600 max-w-3xl mx-auto leading-relaxed mb-8">
                        {{ t('for_smes.hero_subtitle') }}
                    </p>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <Link :href="route('register')" class="inline-flex items-center gap-2 bg-accent-rose hover:bg-pink-500 text-white font-semibold px-6 py-3.5 rounded-xl transition-colors">
                            {{ t('for_smes.cta_primary') }}
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                        </Link>
                        <Link :href="localizedRoute('pricing')" class="text-slate-700 hover:text-slate-900 font-medium px-6 py-3.5">
                            {{ t('for_smes.cta_secondary') }} →
                        </Link>
                    </div>
                    <p class="mt-3 text-sm text-slate-500">{{ t('for_smes.cta_reassurance') }}</p>
                </div>

                <!-- Pains -->
                <section class="mb-20">
                    <h2 class="text-2xl font-bold text-slate-900 text-center mb-10">{{ t('for_smes.pains_title') }}</h2>
                    <div class="grid sm:grid-cols-2 gap-6">
                        <div v-for="pain in pains" :key="pain.key" class="p-6 rounded-2xl border border-gray-200 bg-white">
                            <h3 class="font-semibold text-slate-900 mb-2">{{ t('for_smes.pains.' + pain.key + '.title') }}</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">{{ t('for_smes.pains.' + pain.key + '.desc') }}</p>
                        </div>
                    </div>
                </section>

                <!-- Features -->
                <section class="mb-20">
                    <h2 class="text-2xl font-bold text-slate-900 text-center mb-10">{{ t('for_smes.features_title') }}</h2>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div v-for="feat in features" :key="feat.key" class="p-6 rounded-2xl border border-gray-200 bg-white">
                            <div class="w-10 h-10 rounded-xl bg-[#00bbf9]/10 flex items-center justify-center mb-3">
                                <svg class="w-5 h-5 text-[#00bbf9]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            </div>
                            <h3 class="font-semibold text-slate-900 mb-2">{{ t('for_smes.features.' + feat.key + '.title') }}</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">{{ t('for_smes.features.' + feat.key + '.desc') }}</p>
                        </div>
                    </div>
                </section>

                <!-- Plan recommend -->
                <section class="mb-20">
                    <div class="bg-gradient-to-br from-[#00bbf9]/10 to-primary-50 rounded-3xl p-8 sm:p-12 text-center">
                        <p class="text-sm uppercase tracking-wider text-[#00bbf9] font-semibold mb-3">{{ t('for_smes.plan_recommend_title') }}</p>
                        <h2 class="text-2xl font-bold text-slate-900 mb-3">{{ t('for_smes.plan_recommend_name') }}</h2>
                        <p class="text-slate-600 max-w-2xl mx-auto mb-6">{{ t('for_smes.plan_recommend_desc') }}</p>
                        <Link :href="route('register')" class="inline-flex items-center gap-2 bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl transition-colors">
                            {{ t('for_smes.plan_recommend_cta') }}
                        </Link>
                        <p class="mt-4 text-sm text-slate-500 max-w-xl mx-auto">{{ t('for_smes.plan_note') }}</p>
                    </div>
                </section>

                <!-- FAQ -->
                <section class="mb-16">
                    <h2 class="text-2xl font-bold text-slate-900 text-center mb-10">{{ t('for_smes.faq_title') }}</h2>
                    <div class="max-w-3xl mx-auto space-y-4">
                        <details v-for="(faq, i) in faqs" :key="i" class="group bg-white rounded-xl border border-gray-200">
                            <summary class="flex items-center justify-between cursor-pointer px-6 py-4 font-medium text-slate-900">
                                {{ faq.q }}
                                <svg class="w-5 h-5 text-slate-400 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                            </summary>
                            <div class="px-6 pb-4 text-sm text-slate-600 leading-relaxed">{{ faq.a }}</div>
                        </details>
                    </div>
                </section>

                <!-- Final CTA -->
                <section class="text-center">
                    <h2 class="text-2xl font-bold text-slate-900 mb-3">{{ t('for_smes.cta_final_title') }}</h2>
                    <p class="text-slate-600 max-w-2xl mx-auto mb-6">{{ t('for_smes.cta_final_subtitle') }}</p>
                    <Link :href="route('register')" class="inline-flex items-center gap-2 bg-accent-rose hover:bg-pink-500 text-white font-semibold px-6 py-3.5 rounded-xl transition-colors">
                        {{ t('for_smes.cta_primary') }}
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                    </Link>
                </section>

            </div>
        </div>
    </MarketingLayout>
</template>
