<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';

const { t } = useTranslations();
const { localizedRoute } = useLocalizedRoute();

const tools = computed(() => [
    {
        icon: 'calculator',
        color: 'bg-primary-500/10 text-primary-600',
        title: t('tools.index.vat_calculator.title'),
        description: t('tools.index.vat_calculator.description'),
        href: localizedRoute('tools.vat_calculator'),
        badge: t('tools.index.badge_free'),
    },
    {
        icon: 'percent',
        color: 'bg-[#00f5d4]/10 text-[#00a896]',
        title: t('tools.index.vat_exemption.title'),
        description: t('tools.index.vat_exemption.description'),
        href: localizedRoute('tools.vat_exemption'),
        badge: t('tools.index.badge_free'),
    },
    {
        icon: 'bank',
        color: 'bg-[#00bbf9]/10 text-[#00bbf9]',
        title: t('tools.index.iban_validator.title'),
        description: t('tools.index.iban_validator.description'),
        href: localizedRoute('tools.iban_validator'),
        badge: t('tools.index.badge_free'),
    },
    {
        icon: 'file-check',
        color: 'bg-[#f15bb5]/10 text-[#f15bb5]',
        title: t('tools.index.faia_validator.title'),
        description: t('tools.index.faia_validator.description'),
        href: localizedRoute('faia-validator'),
        badge: t('tools.index.badge_free'),
    },
]);
</script>

<template>
    <SeoHead
        :title="t('tools.index.page_title')"
        :description="t('tools.index.meta_description')"
        canonical-path="/outils"
        route-name="tools"
    />

    <MarketingLayout>
        <div class="py-12 sm:py-16">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">
                <!-- Breadcrumb -->
                <nav class="mb-8 text-sm">
                    <Link :href="localizedRoute('home')" class="text-slate-500 hover:text-slate-700">faktur.lu</Link>
                    <span class="text-slate-400 mx-2">/</span>
                    <span class="text-slate-900">{{ t('tools.index.breadcrumb') }}</span>
                </nav>

                <!-- Hero -->
                <div class="text-center mb-16">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary-500/10 text-primary-600 text-sm font-medium mb-6">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z" /></svg>
                        {{ t('tools.index.badge') }}
                    </div>
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 leading-tight mb-6">
                        {{ t('tools.index.hero_title') }}
                    </h1>
                    <p class="text-lg text-slate-600 max-w-3xl mx-auto leading-relaxed">
                        {{ t('tools.index.hero_subtitle') }}
                    </p>
                </div>

                <!-- Tools grid -->
                <div class="grid sm:grid-cols-2 gap-6 mb-16">
                    <Link
                        v-for="(tool, i) in tools"
                        :key="i"
                        :href="tool.href"
                        class="group bg-white rounded-2xl border border-gray-200 p-6 hover:shadow-lg hover:border-primary-300 transition-all"
                    >
                        <div class="flex items-start gap-4">
                            <div :class="['w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0', tool.color]">
                                <svg v-if="tool.icon === 'calculator'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <svg v-else-if="tool.icon === 'percent'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                                </svg>
                                <svg v-else-if="tool.icon === 'bank'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <svg v-else-if="tool.icon === 'file-check'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <h3 class="font-semibold text-slate-900 group-hover:text-primary-600 transition-colors">{{ tool.title }}</h3>
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-medium">{{ tool.badge }}</span>
                                </div>
                                <p class="text-sm text-slate-600 leading-relaxed">{{ tool.description }}</p>
                            </div>
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-primary-500 transition-colors flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                        </div>
                    </Link>
                </div>

                <!-- CTA -->
                <div class="bg-gradient-to-br from-primary-50 to-[#00f5d4]/10 rounded-3xl p-8 sm:p-12 text-center">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4">{{ t('tools.index.cta_title') }}</h2>
                    <p class="text-slate-600 mb-8 max-w-xl mx-auto">{{ t('tools.index.cta_subtitle') }}</p>
                    <Link :href="route('register')" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-xl transition-colors">
                        {{ t('tools.index.cta_button') }}
                    </Link>
                </div>
            </div>
        </div>
    </MarketingLayout>
</template>
