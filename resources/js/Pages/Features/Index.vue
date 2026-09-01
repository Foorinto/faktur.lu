<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted } from 'vue';
import SeoHead from '@/Components/SeoHead.vue';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';
import { useMarque } from "@/Composables/useMarque";

const { url: marqueUrl } = useMarque();

const { t } = useTranslations();
const { localizedRoute } = useLocalizedRoute();
const appUrl = computed(() => usePage().props.appUrl || marqueUrl.value);

const schemaBreadcrumb = computed(() => JSON.stringify({
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        { "@type": "ListItem", "position": 1, "name": "faktur.lu", "item": appUrl.value },
        { "@type": "ListItem", "position": 2, "name": t('features.breadcrumb.features') },
    ],
}));

onMounted(() => {
    const script = document.createElement('script');
    script.id = 'schema-breadcrumb';
    script.type = 'application/ld+json';
    script.textContent = schemaBreadcrumb.value;
    document.head.appendChild(script);
});
onUnmounted(() => { document.getElementById('schema-breadcrumb')?.remove(); });

defineProps({
    features: Array,
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
};
</script>

<template>
    <SeoHead
        :title="t('features.index.page_title')"
        :description="t('features.index.meta_description')"
        canonical-path="/fonctionnalites"
        route-name="features.index"
    />

    <MarketingLayout>
        <!-- Breadcrumb -->
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-4">
            <nav class="flex text-sm text-slate-500">
                <Link :href="localizedRoute('home')" class="hover:text-slate-900 transition-colors">{{ t('features.breadcrumb.home') }}</Link>
                <span class="mx-2">/</span>
                <span class="text-slate-900 font-medium">{{ t('features.breadcrumb.features') }}</span>
            </nav>
        </div>

        <!-- Hero -->
        <section class="py-16 sm:py-24">
            <div class="max-w-6xl mx-auto px-6 lg:px-8 text-center">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary-500/10 text-primary-500 text-sm font-medium mb-6">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    {{ t('features.index.badge') }}
                </div>
                <h1 class="text-4xl sm:text-5xl font-bold text-slate-900">
                    {{ t('features.index.title') }}
                </h1>
                <p class="mt-6 text-xl text-slate-600 max-w-3xl mx-auto">
                    {{ t('features.index.subtitle') }}
                </p>
            </div>
        </section>

        <!-- Feature Cards Grid -->
        <section class="pb-24">
            <div class="max-w-6xl mx-auto px-6 lg:px-8">
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <Link
                        v-for="feature in features"
                        :key="feature.slug"
                        :href="localizedRoute('features.show', { slug: feature.slug })"
                        class="group bg-white rounded-2xl border border-gray-200 p-8 hover:shadow-lg hover:border-gray-300 transition-all"
                    >
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center mb-6"
                            :style="{ backgroundColor: feature.color + '15' }"
                        >
                            <svg class="w-6 h-6" :style="{ color: feature.color }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="featureIcons[feature.icon]" />
                            </svg>
                        </div>

                        <h2 class="text-xl font-bold text-slate-900 group-hover:text-primary-500 transition-colors mb-3">
                            {{ t(`features.${feature.id}.title`) }}
                        </h2>

                        <p class="text-slate-600 mb-6">
                            {{ t(`features.${feature.id}.short_description`) }}
                        </p>

                        <span class="inline-flex items-center text-sm font-medium text-primary-500 group-hover:gap-2 transition-all">
                            {{ t('features.index.learn_more') }}
                            <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                    </Link>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="py-20 bg-primary-500">
            <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-bold text-white">{{ t('features.index.cta_title') }}</h2>
                <p class="mt-4 text-lg text-white/80">{{ t('features.index.cta_subtitle') }}</p>
                <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                    <Link :href="route('register')" class="inline-flex items-center justify-center px-8 py-4 bg-white text-primary-500 font-semibold rounded-xl text-lg hover:bg-gray-50 transition-colors">
                        {{ t('landing.hero.cta_start') }}
                    </Link>
                    <Link :href="localizedRoute('pricing')" class="inline-flex items-center justify-center px-8 py-4 border-2 border-white/30 text-white font-semibold rounded-xl text-lg hover:bg-white/10 transition-colors">
                        {{ t('landing.nav.pricing') }}
                    </Link>
                </div>
            </div>
        </section>
    </MarketingLayout>
</template>
