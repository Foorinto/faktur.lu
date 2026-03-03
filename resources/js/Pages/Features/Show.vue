<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import SeoHead from '@/Components/SeoHead.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';

const { t } = useTranslations();
const { localizedRoute } = useLocalizedRoute();

const mobileMenuOpen = ref(false);

const props = defineProps({
    feature: Object,
    otherFeatures: Array,
});

const page = usePage();
const appUrl = computed(() => page.props.appUrl || 'https://faktur.lu');

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
            "name": "faktur.lu",
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
    shield: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    globe: 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9',
    folder: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
    clock: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
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

    <div class="min-h-screen bg-slate-50">
        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-6xl mx-auto px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <Link :href="localizedRoute('home')" class="flex items-center">
                            <ApplicationLogo size="sm" />
                        </Link>
                    </div>
                    <div class="hidden md:flex items-center space-x-4">
                        <Link :href="localizedRoute('features.index')" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            {{ t('features.breadcrumb.features') }}
                        </Link>
                        <Link :href="localizedRoute('pricing')" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            {{ t('landing.nav.pricing') }}
                        </Link>
                        <Link :href="route('login')" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            {{ t('landing.nav.login') }}
                        </Link>
                        <Link :href="route('register')" class="bg-accent-rose hover:bg-pink-500 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors">
                            {{ t('landing.nav.create_account') }}
                        </Link>
                    </div>

                    <!-- Mobile menu button -->
                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="md:hidden p-2 text-slate-600 hover:text-slate-900 rounded-lg hover:bg-gray-50"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Mobile menu -->
                <div v-if="mobileMenuOpen" class="md:hidden py-4 border-t border-gray-200">
                    <div class="flex flex-col space-y-3">
                        <Link :href="localizedRoute('features.index')" @click="mobileMenuOpen = false" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">
                            {{ t('features.breadcrumb.features') }}
                        </Link>
                        <Link :href="localizedRoute('pricing')" @click="mobileMenuOpen = false" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">
                            {{ t('landing.nav.pricing') }}
                        </Link>
                        <Link :href="route('login')" @click="mobileMenuOpen = false" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">
                            {{ t('landing.nav.login') }}
                        </Link>
                        <Link :href="route('register')" @click="mobileMenuOpen = false" class="bg-accent-rose hover:bg-pink-500 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors text-center">
                            {{ t('landing.nav.create_account') }}
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

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

        <!-- Footer -->
        <footer class="bg-slate-900 py-12">
            <div class="max-w-6xl mx-auto px-6 lg:px-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="flex items-center">
                        <ApplicationLogo class="h-8 w-auto text-white" />
                    </div>
                    <div class="mt-4 md:mt-0 flex space-x-6">
                        <Link :href="localizedRoute('legal.mentions')" class="text-slate-400 hover:text-white text-sm">{{ t('landing.footer.legal_notice') }}</Link>
                        <Link :href="localizedRoute('legal.privacy')" class="text-slate-400 hover:text-white text-sm">{{ t('landing.footer.privacy') }}</Link>
                        <Link :href="localizedRoute('legal.terms')" class="text-slate-400 hover:text-white text-sm">{{ t('landing.footer.terms') }}</Link>
                    </div>
                </div>
                <div class="mt-8 text-center text-slate-500 text-sm">
                    &copy; {{ new Date().getFullYear() }} faktur.lu. {{ t('landing.footer.all_rights') }}
                </div>
            </div>
        </footer>
    </div>
</template>
