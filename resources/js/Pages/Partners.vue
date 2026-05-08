<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import HoneypotFields from '@/Components/HoneypotFields.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';

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
]);
</script>

<template>
    <SeoHead
        :title="t('partners.page_title')"
        :description="t('partners.meta_description')"
        canonical-path="/partenaires"
        route-name="partners"
    />

    <MarketingLayout>
        <div class="py-12 sm:py-16">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">

                <!-- Breadcrumb -->
                <nav class="mb-8 text-sm">
                    <Link :href="localizedRoute('home')" class="text-slate-500 hover:text-slate-700">faktur.lu</Link>
                    <span class="text-slate-400 mx-2">/</span>
                    <span class="text-slate-900">{{ t('partners.breadcrumb') }}</span>
                </nav>

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
