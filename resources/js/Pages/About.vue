<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import SeoHead from '@/Components/SeoHead.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';

const { t } = useTranslations();
const { localizedRoute } = useLocalizedRoute();

const mobileMenuOpen = ref(false);

const values = [
    { icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', key: 'compliance' },
    { icon: 'M13 10V3L4 14h7v7l9-11h-7z', key: 'simplicity' },
    { icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', key: 'security' },
    { icon: 'M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9', key: 'local' },
];
</script>

<template>
    <SeoHead
        :title="t('about.page_title')"
        :description="t('about.meta_description')"
        canonical-path="/a-propos"
        route-name="about"
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
                            {{ t('landing.nav.features') }}
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
                            {{ t('landing.nav.features') }}
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

        <!-- Values -->
        <section class="pb-20">
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

        <!-- Why Luxembourg -->
        <section class="pb-20 bg-white py-20">
            <div class="max-w-4xl mx-auto px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-slate-900 text-center mb-6">{{ t('about.why_luxembourg_title') }}</h2>
                <p class="text-lg text-slate-600 text-center leading-relaxed mb-10">
                    {{ t('about.why_luxembourg_text') }}
                </p>
                <div class="grid sm:grid-cols-3 gap-6">
                    <div class="text-center p-6">
                        <div class="text-3xl font-bold text-primary-500">100%</div>
                        <div class="mt-2 text-sm text-slate-600">{{ t('about.stats.compliant') }}</div>
                    </div>
                    <div class="text-center p-6">
                        <div class="text-3xl font-bold text-primary-500">4</div>
                        <div class="mt-2 text-sm text-slate-600">{{ t('about.stats.languages') }}</div>
                    </div>
                    <div class="text-center p-6">
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
