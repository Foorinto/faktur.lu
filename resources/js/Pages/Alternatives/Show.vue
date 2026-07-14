<script setup>
import { Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import SeoHead from '@/Components/SeoHead.vue';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';

const { t } = useTranslations();
const { localizedRoute } = useLocalizedRoute();

const props = defineProps({
    competitor: Object,
    others: Array,
});

const c = computed(() => props.competitor);

const pageTitle = computed(() => t(`alternatives.${c.value.id}.page_title`));
const metaDescription = computed(() => t(`alternatives.${c.value.id}.meta_description`));
const heroTitle = computed(() => t(`alternatives.${c.value.id}.hero_title`));
const heroDescription = computed(() => t(`alternatives.${c.value.id}.hero_description`));
const intro = computed(() => t(`alternatives.${c.value.id}.intro`));

const asArray = (value) => (typeof value === 'object' && value !== null ? Object.values(value) : []);

const criteria = computed(() => asArray(t('alternatives.common.criteria')));
const whyPoints = computed(() => asArray(t('alternatives.common.why_points')));
const faqs = computed(() => asArray(t('alternatives.common.faqs')));

const openFaq = ref(null);
const toggleFaq = (i) => { openFaq.value = openFaq.value === i ? null : i; };

const altPath = (slug) => `/fr/alternative-${slug}-luxembourg`;

// FAQPage JSON-LD (mirrors the Features page approach)
let faqScript = null;
onMounted(() => {
    if (!faqs.value.length) return;
    const data = {
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        mainEntity: faqs.value.map((f) => ({
            '@type': 'Question',
            name: f.question,
            acceptedAnswer: { '@type': 'Answer', text: f.answer },
        })),
    };
    faqScript = document.createElement('script');
    faqScript.type = 'application/ld+json';
    faqScript.text = JSON.stringify(data);
    document.head.appendChild(faqScript);
});
onUnmounted(() => { if (faqScript) document.head.removeChild(faqScript); });
</script>

<template>
    <SeoHead
        :title="pageTitle"
        :description="metaDescription"
        :canonical-path="`/alternative-${competitor.slug}-luxembourg`"
    />

    <MarketingLayout>
        <!-- Breadcrumb -->
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-4">
            <nav class="flex text-sm text-slate-500">
                <Link :href="localizedRoute('home')" class="hover:text-slate-900 transition-colors">{{ t('features.breadcrumb.home') }}</Link>
                <span class="mx-2">/</span>
                <span class="text-slate-900 font-medium">{{ t('alternatives.common.breadcrumb') }}</span>
            </nav>
        </div>

        <!-- Hero -->
        <section class="py-16 sm:py-20">
            <div class="max-w-6xl mx-auto px-6 lg:px-8">
                <div class="max-w-3xl">
                    <p class="text-accent-rose font-semibold mb-3">{{ t('alternatives.common.eyebrow') }}</p>
                    <h1 class="text-4xl sm:text-5xl font-bold text-slate-900">{{ heroTitle }}</h1>
                    <p class="mt-6 text-xl text-slate-600 leading-relaxed">{{ heroDescription }}</p>
                    <p class="mt-4 text-base text-slate-500 leading-relaxed">{{ intro }}</p>
                    <div class="mt-10">
                        <Link :href="route('register')" class="inline-flex items-center px-8 py-4 bg-accent-rose hover:bg-pink-500 text-white font-semibold rounded-xl text-lg transition-colors">
                            {{ t('landing.hero.cta_start') }}
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Comparison table -->
        <section class="py-16 bg-white">
            <div class="max-w-6xl mx-auto px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-slate-900 mb-3">{{ t('alternatives.common.comparison_title') }}</h2>
                <p class="text-slate-500 mb-10">{{ t('alternatives.common.comparison_subtitle') }}</p>

                <div class="overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="text-left font-semibold text-slate-700 px-4 py-4 w-1/3">{{ t('alternatives.common.criterion') }}</th>
                                <th class="text-left font-semibold text-primary-700 px-4 py-4">faktur.lu</th>
                                <th class="text-left font-semibold text-slate-700 px-4 py-4">
                                    {{ competitor.name }}
                                    <span class="block text-xs font-normal text-slate-400">{{ t('alternatives.common.competitor_note') }}</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(row, index) in criteria" :key="index">
                                <td class="px-4 py-4 font-medium text-slate-800 align-top">{{ row.label }}</td>
                                <td class="px-4 py-4 text-slate-700 align-top bg-primary-50/40">
                                    <span class="text-primary-600 mr-1">✓</span>{{ row.faktur }}
                                </td>
                                <td class="px-4 py-4 text-slate-500 align-top">{{ row.competitor }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-slate-400">{{ t('alternatives.common.table_disclaimer') }}</p>
            </div>
        </section>

        <!-- Why faktur.lu -->
        <section class="py-16">
            <div class="max-w-6xl mx-auto px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-slate-900 mb-12">{{ t('alternatives.common.why_title') }}</h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div v-for="(point, index) in whyPoints" :key="index" class="p-6 rounded-2xl border border-slate-100">
                        <h3 class="font-semibold text-slate-900 mb-2">{{ point.title }}</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">{{ point.text }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section v-if="faqs.length" class="py-16 bg-white">
            <div class="max-w-3xl mx-auto px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-slate-900 mb-10">{{ t('alternatives.common.faq_title') }}</h2>
                <div class="space-y-3">
                    <div v-for="(faq, index) in faqs" :key="index" class="border border-slate-200 rounded-xl">
                        <button type="button" class="w-full flex justify-between items-center text-left px-5 py-4" @click="toggleFaq(index)">
                            <span class="font-semibold text-slate-900">{{ faq.question }}</span>
                            <svg class="h-5 w-5 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === index }" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                        </button>
                        <div v-show="openFaq === index" class="px-5 pb-5 text-slate-600 text-sm leading-relaxed">{{ faq.answer }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="py-20">
            <div class="max-w-3xl mx-auto px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-bold text-slate-900">{{ t('alternatives.common.cta_title') }}</h2>
                <p class="mt-4 text-lg text-slate-600">{{ t('alternatives.common.cta_text') }}</p>
                <div class="mt-8">
                    <Link :href="route('register')" class="inline-flex items-center px-8 py-4 bg-accent-rose hover:bg-pink-500 text-white font-semibold rounded-xl text-lg transition-colors">
                        {{ t('alternatives.common.cta_button') }}
                    </Link>
                </div>
            </div>
        </section>

        <!-- Other alternatives -->
        <section v-if="others.length" class="py-12 bg-white border-t border-slate-100">
            <div class="max-w-6xl mx-auto px-6 lg:px-8">
                <h2 class="text-lg font-semibold text-slate-900 mb-6">{{ t('alternatives.common.others_title') }}</h2>
                <div class="flex flex-wrap gap-3">
                    <a v-for="other in others" :key="other.slug" :href="altPath(other.slug)"
                       class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-200 text-sm text-slate-700 hover:border-primary-300 hover:text-primary-700 transition-colors">
                        faktur.lu vs {{ other.name }}
                    </a>
                </div>
            </div>
        </section>
    </MarketingLayout>
</template>
