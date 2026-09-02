<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import SeoHead from '@/Components/SeoHead.vue';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';
import { useMarque } from "@/Composables/useMarque";

const { nom: marqueNom, url: marqueUrl } = useMarque();

const { t } = useTranslations();
const { localizedRoute } = useLocalizedRoute();

const props = defineProps({
    feature: Object,
    otherFeatures: Array,
});

const page = usePage();
const appUrl = computed(() => page.props.appUrl || marqueUrl.value);

// FAQ toggle
const openFaq = ref(null);
const toggleFaq = (index) => {
    openFaq.value = openFaq.value === index ? null : index;
};

// Get translated feature data
const featureTitle = computed(() => t(`features.${props.feature.id}.title`));
const featurePageTitle = computed(() => t(`features.${props.feature.id}.page_title`));
const featureMetaDescription = computed(() => t(`features.${props.feature.id}.meta_description`));
const featureHeroDescription = computed(() => t(`features.${props.feature.id}.hero_description`));
const featureItems = computed(() => {
    const items = t(`features.${props.feature.id}.items`);
    if (typeof items === 'object' && items !== null) {
        return Object.values(items);
    }
    return [];
});
const featureFaqs = computed(() => {
    const faqs = t(`features.${props.feature.id}.faqs`);
    if (typeof faqs === 'object' && faqs !== null) {
        return Object.values(faqs);
    }
    return [];
});

// Schema.org FAQPage structured data
const schemaFAQ = computed(() => JSON.stringify({
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": featureFaqs.value.map(faq => ({
        "@type": "Question",
        "name": faq.question,
        "acceptedAnswer": {
            "@type": "Answer",
            "text": faq.answer,
        },
    })),
}));

// Schema.org BreadcrumbList
const schemaBreadcrumb = computed(() => JSON.stringify({
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@type": "ListItem",
            "position": 1,
            "name": marqueNom.value,
            "item": appUrl.value,
        },
        {
            "@type": "ListItem",
            "position": 2,
            "name": t('features.breadcrumb.features'),
            "item": `${appUrl.value}${localizedRoute('features.index')}`,
        },
        {
            "@type": "ListItem",
            "position": 3,
            "name": featureTitle.value,
        },
    ],
}));

const scriptIds = ['schema-faq-feature', 'schema-breadcrumb'];

onMounted(() => {
    scriptIds.forEach(id => {
        const existing = document.getElementById(id);
        if (existing) existing.remove();
    });

    if (featureFaqs.value.length > 0) {
        const faqScript = document.createElement('script');
        faqScript.id = 'schema-faq-feature';
        faqScript.type = 'application/ld+json';
        faqScript.textContent = schemaFAQ.value;
        document.head.appendChild(faqScript);
    }

    const breadcrumbScript = document.createElement('script');
    breadcrumbScript.id = 'schema-breadcrumb';
    breadcrumbScript.type = 'application/ld+json';
    breadcrumbScript.textContent = schemaBreadcrumb.value;
    document.head.appendChild(breadcrumbScript);
});

onUnmounted(() => {
    scriptIds.forEach(id => {
        const script = document.getElementById(id);
        if (script) script.remove();
    });
});

const featureIcons = {
    document: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    clipboard: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
    users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    'credit-card': 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
    shield: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    globe: 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9',
    folder: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
    clock: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    identification: 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0',
    chat: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    calculator: 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
    'document-duplicate': 'M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2',
    'pencil-square': 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10',
    check: 'M5 13l4 4L19 7',
};
</script>

<template>
    <SeoHead
        :title="featurePageTitle"
        :description="featureMetaDescription"
        :canonical-path="`/fonctionnalites/${feature.slug}`"
        route-name="features.show"
        :route-params="{ slug: feature.slug }"
    />

    <MarketingLayout>
        <!-- Breadcrumb -->
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-4">
            <nav class="flex text-sm text-slate-500">
                <Link :href="localizedRoute('home')" class="hover:text-slate-900 transition-colors">{{ t('features.breadcrumb.home') }}</Link>
                <span class="mx-2">/</span>
                <Link :href="localizedRoute('features.index')" class="hover:text-slate-900 transition-colors">{{ t('features.breadcrumb.features') }}</Link>
                <span class="mx-2">/</span>
                <span class="text-slate-900 font-medium">{{ featureTitle }}</span>
            </nav>
        </div>

        <!-- Hero Section -->
        <section class="py-16 sm:py-24">
            <div class="max-w-6xl mx-auto px-6 lg:px-8">
                <div class="max-w-3xl">
                    <div
                        class="w-14 h-14 rounded-2xl flex items-center justify-center mb-8"
                        :style="{ backgroundColor: feature.color + '15' }"
                    >
                        <svg class="w-7 h-7" :style="{ color: feature.color }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="featureIcons[feature.icon]" />
                        </svg>
                    </div>

                    <h1 class="text-4xl sm:text-5xl font-bold text-slate-900">
                        {{ featureTitle }}
                    </h1>

                    <p class="mt-6 text-xl text-slate-600 leading-relaxed">
                        {{ featureHeroDescription }}
                    </p>

                    <div class="mt-10 flex flex-wrap gap-4">
                        <Link :href="route('register')" class="inline-flex items-center px-8 py-4 bg-accent-rose hover:bg-pink-500 text-white font-semibold rounded-xl text-lg transition-colors">
                            {{ t('landing.hero.cta_start') }}
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Feature Details -->
        <section class="py-16 bg-white">
            <div class="max-w-6xl mx-auto px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-slate-900 mb-12">
                    {{ t(`features.${feature.id}.details_title`) }}
                </h2>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div
                        v-for="(item, index) in featureItems"
                        :key="index"
                        class="p-6 rounded-2xl border border-slate-100 hover:border-gray-200 hover:shadow-sm transition-all"
                    >
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" :style="{ backgroundColor: feature.color + '15' }">
                            <svg class="w-5 h-5" :style="{ color: feature.color }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="featureIcons.check" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900 mb-2">{{ item.title }}</h3>
                        <p class="text-slate-600">{{ item.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section v-if="featureFaqs.length > 0" class="py-16">
            <div class="max-w-3xl mx-auto px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-slate-900 text-center mb-12">
                    {{ t('features.faq_title') }}
                </h2>

                <div class="space-y-4">
                    <div
                        v-for="(faq, index) in featureFaqs"
                        :key="index"
                        class="bg-white rounded-2xl border border-gray-200 overflow-hidden"
                    >
                        <button
                            @click="toggleFaq(index)"
                            class="w-full px-6 py-5 flex items-center justify-between text-left"
                        >
                            <span class="text-lg font-semibold text-slate-900">{{ faq.question }}</span>
                            <svg
                                class="w-5 h-5 text-slate-500 transition-transform flex-shrink-0 ml-4"
                                :class="{ 'rotate-180': openFaq === index }"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div v-if="openFaq === index" class="px-6 pb-5">
                            <p class="text-slate-600 leading-relaxed">{{ faq.answer }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Other Features -->
        <section class="py-16 bg-white">
            <div class="max-w-6xl mx-auto px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-slate-900 mb-8">{{ t('features.other_features') }}</h2>

                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <Link
                        v-for="other in otherFeatures"
                        :key="other.slug"
                        :href="localizedRoute('features.show', { slug: other.slug })"
                        class="group p-6 rounded-2xl border border-gray-200 hover:shadow-md hover:border-gray-300 transition-all"
                    >
                        <div
                            class="w-10 h-10 rounded-xl flex items-center justify-center mb-4"
                            :style="{ backgroundColor: other.color + '15' }"
                        >
                            <svg class="w-5 h-5" :style="{ color: other.color }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="featureIcons[other.icon]" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-slate-900 group-hover:text-primary-500 transition-colors">
                            {{ t(`features.${other.id}.title`) }}
                        </h3>
                    </Link>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="py-20 bg-primary-500">
            <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-bold text-white">{{ t('landing.cta.heading') }}</h2>
                <p class="mt-4 text-lg text-white/80">{{ t('landing.cta.subtitle') }}</p>
                <div class="mt-8">
                    <Link :href="route('register')" class="inline-flex items-center px-8 py-4 bg-white text-primary-500 font-semibold rounded-xl text-lg hover:bg-gray-50 transition-colors">
                        {{ t('landing.hero.cta_start') }}
                    </Link>
                </div>
            </div>
        </section>

    </MarketingLayout>
</template>
