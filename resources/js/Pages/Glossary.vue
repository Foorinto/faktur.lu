<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import SeoHead from '@/Components/SeoHead.vue';
import SchemaJsonLd from '@/Components/SchemaJsonLd.vue';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';

const { t } = useTranslations();
const { localizedRoute, currentLocale } = useLocalizedRoute();
const appUrl = computed(() => usePage().props.appUrl || 'https://faktur.lu');
const glossaryUrl = computed(() => `${appUrl.value}/${currentLocale()}/${t('glossary.slug')}`);

// 14 terms - ordered alphabetically. Each translation key returns name + description.
const termIds = [
    'aed',
    'autoliquidation',
    'ccss',
    'factur_x',
    'faia',
    'franchise_tva',
    'liva',
    'numerotation_sequentielle',
    'oss',
    'pdf_a',
    'peppol',
    'rcs',
    'vida',
    'vies',
];

// Optional sameAs (Wikidata) for terms with a public knowledge graph entry
const sameAsMap = {
    peppol: ['https://www.wikidata.org/wiki/Q7170933'],
    pdf_a: ['https://www.wikidata.org/wiki/Q1052200'],
    factur_x: ['https://www.wikidata.org/wiki/Q105606867'],
};

const terms = computed(() => termIds.map((id) => ({
    id,
    anchor: id.replaceAll('_', '-'),
    name: t(`glossary.terms.${id}.name`),
    alternateName: t(`glossary.terms.${id}.alternate`),
    description: t(`glossary.terms.${id}.description`),
    sameAs: sameAsMap[id] || null,
})));

const breadcrumbSchema = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    'itemListElement': [
        { '@type': 'ListItem', 'position': 1, 'name': 'faktur.lu', 'item': appUrl.value },
        { '@type': 'ListItem', 'position': 2, 'name': t('glossary.breadcrumb'), 'item': glossaryUrl.value },
    ],
}));

const definedTermSetSchema = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'DefinedTermSet',
    '@id': `${glossaryUrl.value}#set`,
    'name': t('glossary.set_name'),
    'description': t('glossary.set_description'),
    'inLanguage': currentLocale(),
    'url': glossaryUrl.value,
    'publisher': {
        '@type': 'Organization',
        '@id': `${appUrl.value}/#organization`,
        'name': 'faktur.lu',
    },
    'hasDefinedTerm': terms.value.map((term) => {
        const node = {
            '@type': 'DefinedTerm',
            '@id': `${glossaryUrl.value}#${term.anchor}`,
            'name': term.name,
            'alternateName': term.alternateName,
            'description': term.description,
            'url': `${glossaryUrl.value}#${term.anchor}`,
            'inDefinedTermSet': `${glossaryUrl.value}#set`,
        };
        if (term.sameAs) node.sameAs = term.sameAs;
        return node;
    }),
}));

const webPageSchema = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'WebPage',
    '@id': glossaryUrl.value,
    'name': t('glossary.page_title'),
    'description': t('glossary.meta_description'),
    'url': glossaryUrl.value,
    'inLanguage': currentLocale(),
    'isPartOf': {
        '@type': 'WebSite',
        '@id': `${appUrl.value}/#website`,
        'name': 'faktur.lu',
        'url': appUrl.value,
    },
    'mainEntity': { '@id': `${glossaryUrl.value}#set` },
    'dateModified': new Date().toISOString().slice(0, 10),
    'speakable': {
        '@type': 'SpeakableSpecification',
        'cssSelector': ['h1', '#glossary-intro'],
    },
}));
</script>

<template>
    <SeoHead
        :title="t('glossary.page_title')"
        :description="t('glossary.meta_description')"
        route-name="glossary"
    />
    <SchemaJsonLd :schemas="[breadcrumbSchema, definedTermSetSchema, webPageSchema]" />

    <MarketingLayout>
        <!-- Breadcrumb -->
        <div class="max-w-5xl mx-auto px-6 lg:px-8 py-4">
            <nav class="flex text-sm text-slate-500">
                <Link :href="localizedRoute('home')" class="hover:text-slate-900 transition-colors">{{ t('features.breadcrumb.home') }}</Link>
                <span class="mx-2">/</span>
                <span class="text-slate-900 font-medium">{{ t('glossary.breadcrumb') }}</span>
            </nav>
        </div>

        <!-- Hero -->
        <section class="max-w-5xl mx-auto px-6 lg:px-8 pt-8 pb-12">
            <h1 class="text-4xl md:text-5xl font-bold text-slate-900 leading-tight">
                {{ t('glossary.hero_title') }}
            </h1>
            <p id="glossary-intro" class="mt-4 text-lg text-slate-600 max-w-3xl">{{ t('glossary.hero_subtitle') }}</p>
        </section>

        <!-- Quick nav: jump to term -->
        <section class="max-w-5xl mx-auto px-6 lg:px-8 pb-8">
            <div class="flex flex-wrap gap-2">
                <a
                    v-for="term in terms"
                    :key="`nav-${term.id}`"
                    :href="`#${term.anchor}`"
                    class="px-3 py-1.5 text-sm font-medium bg-slate-100 hover:bg-primary-100 hover:text-primary-700 text-slate-700 rounded-full transition-colors"
                >
                    {{ term.name }}
                </a>
            </div>
        </section>

        <!-- Terms -->
        <section class="max-w-5xl mx-auto px-6 lg:px-8 pb-16">
            <dl class="space-y-8">
                <div
                    v-for="term in terms"
                    :key="term.id"
                    :id="term.anchor"
                    class="border-l-4 border-primary-500 pl-6 py-2 scroll-mt-24"
                >
                    <dt class="flex items-baseline gap-3 flex-wrap">
                        <span class="text-2xl font-bold text-slate-900">{{ term.name }}</span>
                        <span class="text-sm text-slate-500 italic">{{ term.alternateName }}</span>
                    </dt>
                    <dd class="mt-2 text-base text-slate-700 leading-relaxed">{{ term.description }}</dd>
                </div>
            </dl>
        </section>

        <!-- CTA -->
        <section class="max-w-5xl mx-auto px-6 lg:px-8 pb-20">
            <div class="rounded-2xl border border-primary-200 bg-primary-50 p-8">
                <h2 class="text-2xl font-bold text-primary-900">{{ t('glossary.cta_title') }}</h2>
                <p class="mt-2 text-primary-800">{{ t('glossary.cta_subtitle') }}</p>
                <Link
                    :href="localizedRoute('register') || '/register'"
                    class="mt-6 inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl transition-colors"
                >
                    {{ t('glossary.cta_button') }}
                </Link>
            </div>
        </section>
    </MarketingLayout>
</template>
