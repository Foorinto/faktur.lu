<script setup>
import { computed, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';
import { useMarque } from "@/Composables/useMarque";

const { nom: marqueNom } = useMarque();


const { t } = useTranslations();
const { localizedRoute } = useLocalizedRoute();

const commitments = computed(() => [
    {
        icon: 'shield-check',
        title: t('why_faktur.commitments.compliance.title'),
        description: t('why_faktur.commitments.compliance.description'),
        color: 'bg-primary-500/10 text-primary-500',
    },
    {
        icon: 'globe',
        title: t('why_faktur.commitments.languages.title'),
        description: t('why_faktur.commitments.languages.description'),
        color: 'bg-[#00f5d4]/10 text-[#00a896]',
    },
    {
        icon: 'pin',
        title: t('why_faktur.commitments.local.title'),
        description: t('why_faktur.commitments.local.description'),
        color: 'bg-[#00bbf9]/10 text-[#00bbf9]',
    },
    {
        icon: 'tag',
        title: t('why_faktur.commitments.transparency.title'),
        description: t('why_faktur.commitments.transparency.description'),
        color: 'bg-[#f15bb5]/10 text-[#f15bb5]',
    },
    {
        icon: 'star',
        title: t('why_faktur.commitments.experience.title'),
        description: t('why_faktur.commitments.experience.description'),
        color: 'bg-[#9b5de5]/10 text-[#9b5de5]',
    },
]);

const differentiators = computed(() => [
    { feature: t('why_faktur.compare.faia'), faktur: true, generic: false },
    { feature: t('why_faktur.compare.peppol'), faktur: true, generic: false },
    { feature: t('why_faktur.compare.languages'), faktur: true, generic: false },
    { feature: t('why_faktur.compare.local_support'), faktur: true, generic: false },
    { feature: t('why_faktur.compare.fiduciary_portal'), faktur: true, generic: false },
    { feature: t('why_faktur.compare.eu_hosting'), faktur: true, generic: 'partial' },
    { feature: t('why_faktur.compare.transparent_pricing'), faktur: true, generic: 'partial' },
    { feature: t('why_faktur.compare.factur_x'), faktur: true, generic: false },
]);

const schemaWhyFaktur = computed(() => JSON.stringify({
    "@context": "https://schema.org",
    "@type": "WebPage",
    "name": t('why_faktur.page_title'),
    "description": t('why_faktur.meta_description'),
    "url": typeof window !== 'undefined' ? window.location.href : '',
    "mainEntity": {
        "@type": "Article",
        "headline": t('why_faktur.hero.title'),
        "description": t('why_faktur.meta_description'),
        "author": {
            "@type": "Organization",
            "name": marqueNom.value
        },
        "publisher": {
            "@type": "Organization",
            "name": marqueNom.value
        }
    }
}));

onMounted(() => {
    const existing = document.getElementById('schema-why-faktur');
    if (existing) existing.remove();
    const script = document.createElement('script');
    script.id = 'schema-why-faktur';
    script.type = 'application/ld+json';
    script.textContent = schemaWhyFaktur.value;
    document.head.appendChild(script);
});

onUnmounted(() => {
    const existing = document.getElementById('schema-why-faktur');
    if (existing) existing.remove();
});
</script>

<template>
    <SeoHead
        :title="t('why_faktur.page_title')"
        :description="t('why_faktur.meta_description')"
        canonical-path="/pourquoi-faktur-lu"
        route-name="why_faktur"
    />

    <MarketingLayout>
        <div class="py-12 sm:py-16">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">

                <!-- Breadcrumb -->
                <nav class="mb-8 text-sm">
                    <Link :href="localizedRoute('home')" class="text-slate-500 hover:text-slate-700">faktur.lu</Link>
                    <span class="text-slate-400 mx-2">/</span>
                    <span class="text-slate-900">{{ t('why_faktur.breadcrumb') }}</span>
                </nav>

                <!-- Hero -->
                <div class="text-center mb-16">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary-500/10 text-primary-600 text-sm font-medium mb-6">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                        {{ t('why_faktur.hero.badge') }}
                    </div>
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 leading-tight mb-6">
                        {{ t('why_faktur.hero.title') }}
                    </h1>
                    <p class="text-lg text-slate-600 max-w-3xl mx-auto leading-relaxed">
                        {{ t('why_faktur.hero.subtitle') }}
                    </p>
                </div>

                <!-- 5 engagements -->
                <div class="mb-20">
                    <h2 class="text-2xl font-bold text-slate-900 text-center mb-4">
                        {{ t('why_faktur.commitments.title') }}
                    </h2>
                    <p class="text-slate-600 text-center max-w-2xl mx-auto mb-10">
                        {{ t('why_faktur.commitments.subtitle') }}
                    </p>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="(item, i) in commitments" :key="i" class="bg-white rounded-2xl border border-gray-200 p-6">
                            <div :class="['w-12 h-12 rounded-xl flex items-center justify-center mb-4', item.color]">
                                <svg v-if="item.icon === 'shield-check'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <svg v-else-if="item.icon === 'globe'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg v-else-if="item.icon === 'pin'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <svg v-else-if="item.icon === 'tag'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                <svg v-else-if="item.icon === 'star'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-slate-900 mb-2">{{ item.title }}</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">{{ item.description }}</p>
                        </div>
                    </div>
                </div>

                <!-- Comparatif éthique -->
                <div class="mb-20 bg-slate-50 rounded-3xl p-8 sm:p-12">
                    <h2 class="text-2xl font-bold text-slate-900 text-center mb-4">
                        {{ t('why_faktur.compare.title') }}
                    </h2>
                    <p class="text-slate-600 text-center max-w-2xl mx-auto mb-10">
                        {{ t('why_faktur.compare.subtitle') }}
                    </p>
                    <div class="overflow-x-auto">
                        <table class="w-full bg-white rounded-2xl shadow-sm">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-4 px-6 font-semibold text-slate-900">{{ t('why_faktur.compare.col_feature') }}</th>
                                    <th class="text-center py-4 px-6 font-semibold text-primary-600">{{ t('why_faktur.compare.col_faktur') }}</th>
                                    <th class="text-center py-4 px-6 font-semibold text-slate-500">{{ t('why_faktur.compare.col_generic') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, i) in differentiators" :key="i" class="border-b border-gray-100 last:border-0">
                                    <td class="py-3 px-6 text-slate-700">{{ row.feature }}</td>
                                    <td class="py-3 px-6 text-center">
                                        <svg v-if="row.faktur === true" class="w-5 h-5 text-emerald-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <svg v-if="row.generic === true" class="w-5 h-5 text-emerald-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                        <span v-else-if="row.generic === 'partial'" class="text-slate-400 text-sm">{{ t('why_faktur.compare.partial') }}</span>
                                        <svg v-else class="w-5 h-5 text-red-400 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-xs text-slate-500 text-center mt-6 italic">
                        {{ t('why_faktur.compare.disclaimer') }}
                    </p>
                </div>

                <!-- Founder story / antériorité -->
                <div class="mb-20 bg-gradient-to-br from-primary-50 to-[#00f5d4]/10 rounded-3xl p-8 sm:p-12">
                    <div class="max-w-3xl mx-auto">
                        <h2 class="text-2xl font-bold text-slate-900 mb-6 text-center">
                            {{ t('why_faktur.story.title') }}
                        </h2>
                        <div class="prose prose-slate max-w-none">
                            <p class="text-slate-700 leading-relaxed mb-4">{{ t('why_faktur.story.paragraph_1') }}</p>
                            <p class="text-slate-700 leading-relaxed mb-4">{{ t('why_faktur.story.paragraph_2') }}</p>
                            <p class="text-slate-700 leading-relaxed">{{ t('why_faktur.story.paragraph_3') }}</p>
                        </div>
                    </div>
                </div>

                <!-- CTA -->
                <div class="text-center bg-white border border-gray-200 rounded-3xl p-8 sm:p-12">
                    <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-4">
                        {{ t('why_faktur.cta.title') }}
                    </h2>
                    <p class="text-slate-600 mb-8 max-w-xl mx-auto">
                        {{ t('why_faktur.cta.subtitle') }}
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <Link :href="route('register')" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-xl transition-colors">
                            {{ t('why_faktur.cta.primary_button') }}
                        </Link>
                        <Link :href="localizedRoute('pricing')" class="inline-flex items-center justify-center gap-2 px-8 py-4 border border-gray-300 text-slate-700 hover:bg-gray-50 font-semibold rounded-xl transition-colors">
                            {{ t('why_faktur.cta.secondary_button') }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </MarketingLayout>
</template>
