<script setup>
import { Link } from '@inertiajs/vue3';
import SeoHead from '@/Components/SeoHead.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';

const { t } = useTranslations();
const { localizedRoute } = useLocalizedRoute();

defineProps({
    features: Array,
});

const featureIcons = {
    document: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    shield: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    globe: 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9',
    folder: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
    clock: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
};
</script>

<template>
    <SeoHead
        :title="t('features.index.page_title')"
        :description="t('features.index.meta_description')"
        canonical-path="/fonctionnalites"
        route-name="features.index"
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
                    <div class="flex items-center space-x-4">
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
                </div>
            </div>
        </nav>

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
