<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import SchemaJsonLd from '@/Components/SchemaJsonLd.vue';
import HoneypotFields from '@/Components/HoneypotFields.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';
import { useMarque } from "@/Composables/useMarque";

const { nom: marqueNom, url: marqueUrl } = useMarque();

const { t } = useTranslations();
const { localizedRoute, currentLocale } = useLocalizedRoute();
const page = usePage();

const form = useForm({
    company: '',
    name: '',
    email: '',
    clients_count: '',
    message: '',
    homepage_url: '',
    form_loaded_at: '',
});

const submit = () => {
    form.post(localizedRoute('partners.contact'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

// ───── Programme « Partenaire fondateur » (pilote) ─────
// Lien Calendly « Découverte ${marqueNom.value} - portail comptable » (20 min, Google Meet)
const CALENDLY_URL = 'https://calendly.com/foorintodev/decouverte-faktur-lu-portail-comptable';

// Note : le portail comptable est gratuit pour TOUTES les fiduciaires (clarifié
// dans le sous-titre). La liste ci-dessous = les avantages exclusifs du programme
// - aucun abonnement faktur offert (accompagnement + visibilité uniquement).
const painKeys = ['formats', 'faia', 'reentry', 'access'];

const founderGet = computed(() => [
    t('partners.founder.get_3'),
    t('partners.founder.get_4'),
    t('partners.founder.get_5'),
    t('partners.founder.get_6'),
]);

const founderGive = computed(() => [
    t('partners.founder.give_1'),
    t('partners.founder.give_2'),
]);

const steps = computed(() => [
    {
        number: '01',
        title: t('partners.steps.step1_title'),
        description: t('partners.steps.step1_desc'),
        icon: 'user-plus',
    },
    {
        number: '02',
        title: t('partners.steps.step2_title'),
        description: t('partners.steps.step2_desc'),
        icon: 'mail',
    },
    {
        number: '03',
        title: t('partners.steps.step3_title'),
        description: t('partners.steps.step3_desc'),
        icon: 'eye',
    },
]);

const advantages = computed(() => [
    {
        title: t('partners.advantages.portal_title'),
        description: t('partners.advantages.portal_desc'),
        icon: 'monitor',
        color: 'bg-primary-500/10 text-primary-500',
    },
    {
        title: t('partners.advantages.exports_title'),
        description: t('partners.advantages.exports_desc'),
        icon: 'download',
        color: 'bg-[#00f5d4]/10 text-[#00a896]',
    },
    {
        title: t('partners.advantages.faia_title'),
        description: t('partners.advantages.faia_desc'),
        icon: 'shield',
        color: 'bg-[#00bbf9]/10 text-[#00bbf9]',
    },
    {
        title: t('partners.advantages.time_title'),
        description: t('partners.advantages.time_desc'),
        icon: 'clock',
        color: 'bg-[#f15bb5]/10 text-[#f15bb5]',
    },
    {
        title: t('partners.advantages.multi_title'),
        description: t('partners.advantages.multi_desc'),
        icon: 'users',
        color: 'bg-[#fee440]/10 text-[#d4a500]',
    },
    {
        title: t('partners.advantages.free_title'),
        description: t('partners.advantages.free_desc'),
        icon: 'gift',
        color: 'bg-[#9b5de5]/10 text-[#9b5de5]',
    },
]);

const faqs = computed(() => [
    { q: t('partners.faq.q1'), a: t('partners.faq.a1') },
    { q: t('partners.faq.q2'), a: t('partners.faq.a2') },
    { q: t('partners.faq.q3'), a: t('partners.faq.a3') },
    { q: t('partners.faq.q4'), a: t('partners.faq.a4') },
    { q: t('partners.faq.q5'), a: t('partners.faq.a5') },
    { q: t('partners.faq.q6'), a: t('partners.faq.a6') },
]);

// Schema.org for SEO + LLM authority signals
const appUrl = computed(() => page.props.appUrl || marqueUrl.value);
const schemas = computed(() => [
    {
        '@context': 'https://schema.org',
        '@type': 'Service',
        name: t('partners.title'),
        description: t('partners.meta_description'),
        serviceType: 'Accounting partner program',
        areaServed: { '@type': 'Country', name: 'Luxembourg', sameAs: 'https://www.wikidata.org/wiki/Q32' },
        provider: {
            '@type': 'Organization',
            '@id': appUrl.value + '/#organization',
            name: marqueNom.value,
            url: appUrl.value + '/',
            sameAs: ['https://www.wikidata.org/wiki/Q139674760'],
        },
        audience: {
            '@type': 'BusinessAudience',
            audienceType: 'Fiduciaries, accounting firms, chartered accountants',
            geographicArea: { '@type': 'Country', name: 'Luxembourg' },
        },
        offers: {
            '@type': 'Offer',
            price: '0',
            priceCurrency: 'EUR',
            description: 'Free for accounting firms; clients pay their own ${marqueNom.value} plan (Free, Essentiel or Pro)',
        },
        dateModified: new Date().toISOString().slice(0, 10),
    },
    {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: [
            { '@type': 'ListItem', position: 1, name: marqueNom.value, item: appUrl.value + '/' + currentLocale() + '/' },
            { '@type': 'ListItem', position: 2, name: t('partners.breadcrumb') },
        ],
    },
    {
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        mainEntity: faqs.value.map((f) => ({
            '@type': 'Question',
            name: f.q,
            acceptedAnswer: { '@type': 'Answer', text: f.a },
        })),
    },
]);
</script>

<template>
    <SeoHead
        :title="t('partners.page_title')"
        :description="t('partners.meta_description')"
        canonical-path="/partenaires"
        route-name="partners"
    />
    <SchemaJsonLd :schemas="schemas" />

    <MarketingLayout>
        <div class="py-12 sm:py-16">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">

                <!-- Breadcrumb -->
                <nav class="mb-8 text-sm">
                    <Link :href="localizedRoute('home')" class="text-slate-500 hover:text-slate-700">{{ marqueNom }}</Link>
                    <span class="text-slate-400 mx-2">/</span>
                    <span class="text-slate-900">{{ t('partners.breadcrumb') }}</span>
                </nav>

                <!-- Programme Partenaire Fondateur (pilote) -->
                <section class="mb-16">
                    <div class="rounded-3xl bg-gradient-to-br from-[#9b5de5] to-[#7c3aed] text-white p-8 sm:p-12">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/15 text-white text-sm font-medium mb-6">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                            {{ t('partners.founder.badge') }}
                        </div>

                        <h2 class="text-3xl sm:text-4xl font-bold leading-tight mb-4 max-w-3xl">
                            {{ t('partners.founder.title') }}
                        </h2>
                        <p class="text-lg text-white/90 max-w-3xl leading-relaxed mb-8">
                            {{ t('partners.founder.subtitle') }}
                        </p>

                        <div class="grid md:grid-cols-2 gap-6 mb-6">
                            <!-- Ce que vous recevez -->
                            <div class="bg-white/10 rounded-2xl p-6">
                                <h3 class="font-semibold text-lg mb-4">{{ t('partners.founder.get_title') }}</h3>
                                <ul class="space-y-3">
                                    <li v-for="(item, i) in founderGet" :key="i" class="flex items-start gap-3">
                                        <svg class="w-5 h-5 text-[#00f5d4] flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span class="text-sm text-white/95 leading-relaxed">{{ item }}</span>
                                    </li>
                                </ul>
                            </div>
                            <!-- Ce qu'on attend en retour -->
                            <div class="bg-white/10 rounded-2xl p-6">
                                <h3 class="font-semibold text-lg mb-4">{{ t('partners.founder.give_title') }}</h3>
                                <ul class="space-y-3 mb-4">
                                    <li v-for="(item, i) in founderGive" :key="i" class="flex items-start gap-3">
                                        <svg class="w-5 h-5 text-[#fee440] flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                        </svg>
                                        <span class="text-sm text-white/95 leading-relaxed">{{ item }}</span>
                                    </li>
                                </ul>
                                <p class="text-xs text-white/70 leading-relaxed">{{ t('partners.founder.give_note') }}</p>
                            </div>
                        </div>

                        <!-- Annuaire (teaser) -->
                        <div class="bg-white/10 rounded-2xl p-6 mb-8 flex items-start gap-4">
                            <svg class="w-6 h-6 text-[#00bbf9] flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <div>
                                <h3 class="font-semibold mb-1">{{ t('partners.founder.directory_title') }}</h3>
                                <p class="text-sm text-white/90 leading-relaxed">{{ t('partners.founder.directory_desc') }}</p>
                            </div>
                        </div>

                        <!-- CTA -->
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                            <a :href="CALENDLY_URL" target="_blank" rel="noopener"
                               class="inline-flex items-center justify-center gap-2 bg-white text-[#7c3aed] font-semibold px-7 py-3.5 rounded-xl hover:bg-white/90 transition-colors">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ t('partners.founder.cta') }}
                            </a>
                            <span class="text-sm text-white/70">{{ t('partners.founder.cta_note') }}</span>
                        </div>

                        <p class="mt-6 text-xs text-white/70">{{ t('partners.founder.reassurance') }}</p>
                    </div>
                </section>

                <!-- Hero -->
                <div class="text-center mb-16">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#9b5de5]/10 text-[#9b5de5] text-sm font-medium mb-6">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        {{ t('partners.badge') }}
                    </div>
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 leading-tight mb-6">
                        {{ t('partners.title') }}
                    </h1>
                    <p class="text-lg text-slate-600 max-w-3xl mx-auto leading-relaxed">
                        {{ t('partners.subtitle') }}
                    </p>

                    <!-- Le validateur FAIA est gratuit et sans inscription : c'est
                         ce qu'on peut offrir à un cabinet sans rien lui demander,
                         et donc le meilleur point d'entrée dans cette page. -->
                    <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                        <Link
                            :href="localizedRoute('faia-validator')"
                            class="inline-flex items-center gap-2 bg-accent-rose hover:bg-pink-500 text-white font-semibold px-6 py-3.5 rounded-xl transition-colors"
                        >
                            {{ t('partners.cta_faia') }}
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                        </Link>
                    </div>
                    <p class="mt-3 text-sm text-slate-500">{{ t('partners.cta_faia_note') }}</p>
                </div>

                <!-- Ce qui fait perdre du temps à un cabinet.
                     Repris de l'ancienne page /pour-fiduciaires, fusionnée ici. -->
                <div class="mb-20">
                    <h2 class="text-2xl font-bold text-slate-900 text-center mb-10">
                        {{ t('partners.pains.title') }}
                    </h2>
                    <div class="grid sm:grid-cols-2 gap-6 max-w-4xl mx-auto">
                        <div v-for="key in painKeys" :key="key" class="p-6 rounded-2xl border border-gray-200 bg-white">
                            <h3 class="font-semibold text-slate-900 mb-2">{{ t('partners.pains.' + key + '.title') }}</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">{{ t('partners.pains.' + key + '.desc') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Avantages -->
                <div class="mb-20">
                    <h2 class="text-2xl font-bold text-slate-900 text-center mb-4">
                        {{ t('partners.advantages.title') }}
                    </h2>
                    <p class="text-slate-600 text-center max-w-2xl mx-auto mb-10">
                        {{ t('partners.advantages.subtitle') }}
                    </p>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="(adv, i) in advantages" :key="i" class="bg-white rounded-2xl border border-gray-200 p-6">
                            <div :class="['w-12 h-12 rounded-xl flex items-center justify-center mb-4', adv.color]">
                                <!-- Portal -->
                                <svg v-if="adv.icon === 'monitor'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <!-- Exports -->
                                <svg v-else-if="adv.icon === 'download'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <!-- FAIA -->
                                <svg v-else-if="adv.icon === 'shield'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <!-- Time -->
                                <svg v-else-if="adv.icon === 'clock'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <!-- Multi-clients -->
                                <svg v-else-if="adv.icon === 'users'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <!-- Free -->
                                <svg v-else-if="adv.icon === 'gift'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-slate-900 mb-2">{{ adv.title }}</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">{{ adv.description }}</p>
                        </div>
                    </div>
                </div>

                <!-- Comment ca marche -->
                <div class="mb-20 bg-slate-50 rounded-3xl p-8 sm:p-12">
                    <h2 class="text-2xl font-bold text-slate-900 text-center mb-4">
                        {{ t('partners.steps.title') }}
                    </h2>
                    <p class="text-slate-600 text-center max-w-2xl mx-auto mb-12">
                        {{ t('partners.steps.subtitle') }}
                    </p>
                    <div class="grid md:grid-cols-3 gap-8">
                        <div v-for="(step, i) in steps" :key="i" class="text-center">
                            <div class="mx-auto w-16 h-16 rounded-2xl bg-white shadow-sm border border-gray-200 flex items-center justify-center mb-4">
                                <!-- User Plus -->
                                <svg v-if="step.icon === 'user-plus'" class="w-7 h-7 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                                <!-- Mail -->
                                <svg v-else-if="step.icon === 'mail'" class="w-7 h-7 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <!-- Eye -->
                                <svg v-else-if="step.icon === 'eye'" class="w-7 h-7 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </div>
                            <div class="text-xs font-bold text-primary-500 mb-2">{{ step.number }}</div>
                            <h3 class="font-semibold text-slate-900 mb-2">{{ step.title }}</h3>
                            <p class="text-sm text-slate-600">{{ step.description }}</p>
                        </div>
                    </div>
                </div>

                <!-- FAQ -->
                <div class="mb-20">
                    <h2 class="text-2xl font-bold text-slate-900 text-center mb-10">
                        {{ t('partners.faq.title') }}
                    </h2>
                    <div class="max-w-3xl mx-auto space-y-4">
                        <details v-for="(faq, i) in faqs" :key="i" class="group bg-white rounded-xl border border-gray-200">
                            <summary class="flex items-center justify-between cursor-pointer px-6 py-4 font-medium text-slate-900">
                                {{ faq.q }}
                                <svg class="w-5 h-5 text-slate-400 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </summary>
                            <div class="px-6 pb-4 text-sm text-slate-600 leading-relaxed">
                                {{ faq.a }}
                            </div>
                        </details>
                    </div>
                </div>

                <!-- Formulaire de contact -->
                <div class="max-w-2xl mx-auto">
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8 sm:p-10">
                        <h2 class="text-2xl font-bold text-slate-900 text-center mb-2">
                            {{ t('partners.form.title') }}
                        </h2>
                        <p class="text-slate-600 text-center mb-8">
                            {{ t('partners.form.subtitle') }}
                        </p>

                        <!-- Success -->
                        <div v-if="page.props.flash?.success" class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-emerald-700 font-medium">{{ t('partners.form.success') }}</p>
                            </div>
                        </div>

                        <form v-else @submit.prevent="submit" class="space-y-5">
                            <HoneypotFields
                                v-model:honeypot="form.homepage_url"
                                v-model:loadedAt="form.form_loaded_at"
                            />
                            <div class="grid sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('partners.form.company') }} *</label>
                                    <input v-model="form.company" type="text" :placeholder="t('partners.form.company_placeholder')" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500" />
                                    <p v-if="form.errors.company" class="mt-1 text-sm text-red-600">{{ form.errors.company }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('partners.form.name') }} *</label>
                                    <input v-model="form.name" type="text" :placeholder="t('partners.form.name_placeholder')" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500" />
                                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('partners.form.email') }} *</label>
                                    <input v-model="form.email" type="email" placeholder="contact@cabinet.lu" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500" />
                                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('partners.form.clients_count') }} *</label>
                                    <select v-model="form.clients_count" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                        <option value="" disabled>{{ t('partners.form.clients_select') }}</option>
                                        <option value="1-10">1 - 10</option>
                                        <option value="11-50">11 - 50</option>
                                        <option value="51-100">51 - 100</option>
                                        <option value="100+">100+</option>
                                    </select>
                                    <p v-if="form.errors.clients_count" class="mt-1 text-sm text-red-600">{{ form.errors.clients_count }}</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('partners.form.message') }}</label>
                                <textarea v-model="form.message" rows="4" :placeholder="t('partners.form.message_placeholder')" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"></textarea>
                            </div>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full bg-accent-rose hover:bg-pink-500 disabled:bg-slate-400 text-white font-semibold py-3 rounded-xl transition-colors"
                            >
                                {{ form.processing ? t('partners.form.sending') : t('partners.form.submit') }}
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </MarketingLayout>
</template>
