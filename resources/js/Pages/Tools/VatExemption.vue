<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import SchemaJsonLd from '@/Components/SchemaJsonLd.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';
import { useToolSchemas } from '@/Composables/useToolSchemas';

const { t } = useTranslations();
const { localizedRoute } = useLocalizedRoute();
const { breadcrumb, faqPage, webApplication } = useToolSchemas();

// Seuil franchise TVA Luxembourg = 35 000 EUR HT/an depuis 2020
const FRANCHISE_THRESHOLD = 35000;

const annualRevenue = ref(20000);
const activityType = ref('services');
const currentRegime = ref('none');

const result = computed(() => {
    const revenue = parseFloat(annualRevenue.value) || 0;
    const isUnder = revenue <= FRANCHISE_THRESHOLD;
    const remaining = FRANCHISE_THRESHOLD - revenue;
    const overshoot = revenue - FRANCHISE_THRESHOLD;

    if (currentRegime.value === 'normal') {
        // Déjà au régime normal : peut demander à passer en franchise si éligible
        return {
            status: isUnder ? 'eligible_to_switch' : 'must_stay_normal',
            revenue,
            remaining,
            overshoot,
        };
    }

    // En franchise ou non assujetti
    return {
        status: isUnder ? 'eligible' : 'must_register',
        revenue,
        remaining,
        overshoot,
    };
});

const fmt = (n) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Math.abs(n)) + ' €';

const faqs = computed(() => [
    { q: t('tools.vat_exemption.faq.q1'), a: t('tools.vat_exemption.faq.a1') },
    { q: t('tools.vat_exemption.faq.q2'), a: t('tools.vat_exemption.faq.a2') },
    { q: t('tools.vat_exemption.faq.q3'), a: t('tools.vat_exemption.faq.a3') },
    { q: t('tools.vat_exemption.faq.q4'), a: t('tools.vat_exemption.faq.a4') },
]);

const schemas = computed(() => [
    breadcrumb(t('tools.vat_exemption.breadcrumb')),
    faqPage(faqs.value),
    webApplication({
        name: t('tools.vat_exemption.title'),
        description: t('tools.vat_exemption.meta_description'),
        url: localizedRoute('tools.vat_exemption'),
        category: 'FinanceApplication',
    }),
]);

const relatedTools = computed(() => [
    { key: 'vat_calculator', route: localizedRoute('tools.vat_calculator') },
    { key: 'invoice_generator', route: localizedRoute('tools.invoice_generator') },
    { key: 'templates', route: localizedRoute('tools.templates') },
]);
</script>

<template>
    <SchemaJsonLd :schemas="schemas" />
    <SeoHead
        :title="t('tools.vat_exemption.page_title')"
        :description="t('tools.vat_exemption.meta_description')"
        canonical-path="/outils/franchise-tva"
        route-name="tools.vat_exemption"
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
                    <span class="text-slate-900">{{ t('tools.vat_exemption.breadcrumb') }}</span>
                </nav>

                <!-- Hero -->
                <div class="text-center mb-12">
                    <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 leading-tight mb-4">
                        {{ t('tools.vat_exemption.title') }}
                    </h1>
                    <p class="text-lg text-slate-600">
                        {{ t('tools.vat_exemption.subtitle') }}
                    </p>
                </div>

                <!-- Form -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 sm:p-8 mb-8">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">{{ t('tools.vat_exemption.revenue_label') }}</label>
                            <div class="relative">
                                <input
                                    v-model.number="annualRevenue"
                                    type="number"
                                    min="0"
                                    step="100"
                                    class="w-full pl-4 pr-12 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 text-lg"
                                />
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500">€/{{ t('tools.vat_exemption.year') }}</span>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">{{ t('tools.vat_exemption.revenue_help') }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">{{ t('tools.vat_exemption.activity_label') }}</label>
                            <select v-model="activityType" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 text-lg">
                                <option value="services">{{ t('tools.vat_exemption.activity_services') }}</option>
                                <option value="commerce">{{ t('tools.vat_exemption.activity_commerce') }}</option>
                                <option value="liberal">{{ t('tools.vat_exemption.activity_liberal') }}</option>
                                <option value="craft">{{ t('tools.vat_exemption.activity_craft') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">{{ t('tools.vat_exemption.regime_label') }}</label>
                            <select v-model="currentRegime" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 text-lg">
                                <option value="none">{{ t('tools.vat_exemption.regime_none') }}</option>
                                <option value="franchise">{{ t('tools.vat_exemption.regime_franchise') }}</option>
                                <option value="normal">{{ t('tools.vat_exemption.regime_normal') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Result -->
                <div
                    :class="[
                        'rounded-2xl p-6 sm:p-8 mb-8 border-2',
                        result.status === 'eligible' || result.status === 'eligible_to_switch'
                            ? 'bg-emerald-50 border-emerald-200'
                            : 'bg-amber-50 border-amber-200'
                    ]"
                >
                    <div class="flex items-start gap-4">
                        <div :class="[
                            'flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center',
                            result.status === 'eligible' || result.status === 'eligible_to_switch'
                                ? 'bg-emerald-500 text-white'
                                : 'bg-amber-500 text-white'
                        ]">
                            <svg v-if="result.status === 'eligible' || result.status === 'eligible_to_switch'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            <svg v-else class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-slate-900 mb-3">
                                {{ t(`tools.vat_exemption.result.${result.status}.title`) }}
                            </h3>
                            <p class="text-slate-700 mb-4 leading-relaxed">
                                {{ t(`tools.vat_exemption.result.${result.status}.description`) }}
                            </p>
                            <div class="grid sm:grid-cols-2 gap-4 mb-4">
                                <div class="bg-white/60 rounded-lg p-3">
                                    <p class="text-xs text-slate-500 uppercase mb-1">{{ t('tools.vat_exemption.result.threshold') }}</p>
                                    <p class="text-lg font-bold text-slate-900">{{ fmt(35000) }} HT</p>
                                </div>
                                <div class="bg-white/60 rounded-lg p-3">
                                    <p class="text-xs text-slate-500 uppercase mb-1">
                                        {{ result.status === 'eligible' || result.status === 'eligible_to_switch'
                                            ? t('tools.vat_exemption.result.remaining')
                                            : t('tools.vat_exemption.result.overshoot') }}
                                    </p>
                                    <p :class="[
                                        'text-lg font-bold',
                                        result.status === 'eligible' || result.status === 'eligible_to_switch' ? 'text-emerald-700' : 'text-amber-700'
                                    ]">
                                        {{ fmt(result.status === 'eligible' || result.status === 'eligible_to_switch' ? result.remaining : result.overshoot) }}
                                    </p>
                                </div>
                            </div>
                            <div class="bg-white/60 rounded-lg p-4">
                                <h4 class="font-semibold text-slate-900 mb-2">{{ t('tools.vat_exemption.result.next_steps') }}</h4>
                                <p class="text-sm text-slate-700 leading-relaxed">
                                    {{ t(`tools.vat_exemption.result.${result.status}.next_steps`) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CTA -->
                <div class="bg-gradient-to-br from-primary-50 to-[#00f5d4]/10 rounded-2xl p-6 sm:p-8 text-center mb-12">
                    <h2 class="text-xl font-bold text-slate-900 mb-2">{{ t('tools.vat_exemption.cta_title') }}</h2>
                    <p class="text-slate-600 mb-6">{{ t('tools.vat_exemption.cta_subtitle') }}</p>
                    <Link :href="route('register')" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-xl transition-colors">
                        {{ t('tools.vat_exemption.cta_button') }}
                    </Link>
                </div>

                <!-- FAQ -->
                <div class="mb-12">
                    <h2 class="text-2xl font-bold text-slate-900 text-center mb-8">{{ t('tools.vat_exemption.faq.title') }}</h2>
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

                <!-- Related tools (internal linking) -->
                <div class="mb-12">
                    <h2 class="text-xl font-bold text-slate-900 mb-6">{{ t('tools.related_title') }}</h2>
                    <div class="grid sm:grid-cols-3 gap-4">
                        <Link
                            v-for="tool in relatedTools"
                            :key="tool.key"
                            :href="tool.route"
                            class="block p-4 rounded-xl border border-gray-200 hover:border-primary-500 hover:shadow-sm transition-all bg-white"
                        >
                            <p class="font-semibold text-slate-900 mb-1">{{ t('tools.index.' + tool.key + '.title') }}</p>
                            <p class="text-sm text-slate-600">{{ t('tools.index.' + tool.key + '.description') }}</p>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </MarketingLayout>
</template>
