<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';

const { t } = useTranslations();
const { localizedRoute } = useLocalizedRoute();

const amount = ref(1000);
const mode = ref('ht'); // 'ht' (montant HT vers TTC) ou 'ttc' (montant TTC vers HT)
const vatRate = ref(17);

const VAT_RATES = [
    { value: 17, labelKey: 'standard' },
    { value: 14, labelKey: 'intermediate' },
    { value: 8, labelKey: 'reduced' },
    { value: 3, labelKey: 'super_reduced' },
    { value: 0, labelKey: 'exempt' },
];

const result = computed(() => {
    const num = parseFloat(amount.value) || 0;
    const rate = parseFloat(vatRate.value) || 0;
    if (mode.value === 'ht') {
        const ht = num;
        const tva = ht * (rate / 100);
        const ttc = ht + tva;
        return { ht, tva, ttc };
    } else {
        const ttc = num;
        const ht = ttc / (1 + rate / 100);
        const tva = ttc - ht;
        return { ht, tva, ttc };
    }
});

const fmt = (n) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n) + ' €';

const faqs = computed(() => [
    { q: t('tools.vat_calculator.faq.q1'), a: t('tools.vat_calculator.faq.a1') },
    { q: t('tools.vat_calculator.faq.q2'), a: t('tools.vat_calculator.faq.a2') },
    { q: t('tools.vat_calculator.faq.q3'), a: t('tools.vat_calculator.faq.a3') },
    { q: t('tools.vat_calculator.faq.q4'), a: t('tools.vat_calculator.faq.a4') },
]);
</script>

<template>
    <SeoHead
        :title="t('tools.vat_calculator.page_title')"
        :description="t('tools.vat_calculator.meta_description')"
        canonical-path="/outils/calculateur-tva"
        route-name="tools.vat_calculator"
    />

    <MarketingLayout>
        <div class="py-12 sm:py-16">
            <div class="mx-auto max-w-4xl px-6 lg:px-8">
                <!-- Breadcrumb -->
                <nav class="mb-8 text-sm">
                    <Link :href="localizedRoute('home')" class="text-slate-500 hover:text-slate-700">faktur.lu</Link>
                    <span class="text-slate-400 mx-2">/</span>
                    <Link :href="localizedRoute('tools')" class="text-slate-500 hover:text-slate-700">{{ t('tools.index.breadcrumb') }}</Link>
                    <span class="text-slate-400 mx-2">/</span>
                    <span class="text-slate-900">{{ t('tools.vat_calculator.breadcrumb') }}</span>
                </nav>

                <!-- Hero -->
                <div class="text-center mb-12">
                    <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 leading-tight mb-4">
                        {{ t('tools.vat_calculator.title') }}
                    </h1>
                    <p class="text-lg text-slate-600">
                        {{ t('tools.vat_calculator.subtitle') }}
                    </p>
                </div>

                <!-- Calculator -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 sm:p-8 mb-8">
                    <div class="grid sm:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">{{ t('tools.vat_calculator.amount_label') }}</label>
                            <div class="relative">
                                <input
                                    v-model.number="amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full pl-4 pr-12 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-lg"
                                />
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500">€</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">{{ t('tools.vat_calculator.mode_label') }}</label>
                            <select v-model="mode" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 text-lg">
                                <option value="ht">{{ t('tools.vat_calculator.mode_ht_to_ttc') }}</option>
                                <option value="ttc">{{ t('tools.vat_calculator.mode_ttc_to_ht') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 mb-2">{{ t('tools.vat_calculator.rate_label') }}</label>
                        <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                            <button
                                v-for="rate in VAT_RATES"
                                :key="rate.value"
                                type="button"
                                @click="vatRate = rate.value"
                                :class="[
                                    'px-4 py-3 rounded-xl font-semibold text-sm transition-all',
                                    vatRate === rate.value
                                        ? 'bg-primary-500 text-white'
                                        : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                                ]"
                            >
                                {{ rate.value }}%
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">{{ t(`tools.vat_calculator.rate_${VAT_RATES.find(r => r.value === vatRate)?.labelKey}`) }}</p>
                    </div>

                    <!-- Results -->
                    <div class="border-t border-gray-200 pt-6">
                        <div class="grid sm:grid-cols-3 gap-4">
                            <div class="text-center p-4 bg-slate-50 rounded-xl">
                                <p class="text-xs text-slate-500 uppercase mb-1">{{ t('tools.vat_calculator.result_ht') }}</p>
                                <p class="text-2xl font-bold text-slate-900">{{ fmt(result.ht) }}</p>
                            </div>
                            <div class="text-center p-4 bg-primary-50 rounded-xl">
                                <p class="text-xs text-primary-600 uppercase mb-1">{{ t('tools.vat_calculator.result_vat') }}</p>
                                <p class="text-2xl font-bold text-primary-600">{{ fmt(result.tva) }}</p>
                            </div>
                            <div class="text-center p-4 bg-emerald-50 rounded-xl">
                                <p class="text-xs text-emerald-600 uppercase mb-1">{{ t('tools.vat_calculator.result_ttc') }}</p>
                                <p class="text-2xl font-bold text-emerald-700">{{ fmt(result.ttc) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CTA -->
                <div class="bg-gradient-to-br from-primary-50 to-[#00f5d4]/10 rounded-2xl p-6 sm:p-8 text-center mb-12">
                    <h2 class="text-xl font-bold text-slate-900 mb-2">{{ t('tools.vat_calculator.cta_title') }}</h2>
                    <p class="text-slate-600 mb-6">{{ t('tools.vat_calculator.cta_subtitle') }}</p>
                    <Link :href="route('register')" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-xl transition-colors">
                        {{ t('tools.vat_calculator.cta_button') }}
                    </Link>
                </div>

                <!-- FAQ -->
                <div class="mb-12">
                    <h2 class="text-2xl font-bold text-slate-900 text-center mb-8">{{ t('tools.vat_calculator.faq.title') }}</h2>
                    <div class="space-y-4">
                        <details v-for="(faq, i) in faqs" :key="i" class="group bg-white rounded-xl border border-gray-200">
                            <summary class="flex items-center justify-between cursor-pointer px-6 py-4 font-medium text-slate-900">
                                {{ faq.q }}
                                <svg class="w-5 h-5 text-slate-400 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                            </summary>
                            <div class="px-6 pb-4 text-sm text-slate-600 leading-relaxed">
                                {{ faq.a }}
                            </div>
                        </details>
                    </div>
                </div>
            </div>
        </div>
    </MarketingLayout>
</template>
