<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { Link, usePage } from '@inertiajs/vue3';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';

const { t } = useTranslations();
const { localizedRoute } = useLocalizedRoute();
const page = usePage();

const email = ref('');
const consent = ref(false);
const downloading = ref(null);
const downloaded = ref([]);
const error = ref(null);

const templates = [
    { key: 'invoice_blank', icon: 'file', color: 'bg-primary-500/10 text-primary-600' },
    { key: 'aed_checklist', icon: 'check', color: 'bg-emerald-500/10 text-emerald-600' },
    { key: 'reminder_letter', icon: 'mail', color: 'bg-amber-500/10 text-amber-600' },
    { key: 'vat_calendar', icon: 'calendar', color: 'bg-pink-500/10 text-pink-600' },
];

async function downloadTemplate(template) {
    error.value = null;

    if (!email.value) {
        error.value = t('tools.templates.error_email');
        return;
    }
    if (!consent.value) {
        error.value = t('tools.templates.error_consent');
        return;
    }

    downloading.value = template;

    try {
        const response = await axios.post(
            `/${page.props.locale}/tools/download-template`,
            {
                email: email.value,
                template,
                language: page.props.locale,
            },
            { responseType: 'blob' }
        );

        const blob = new Blob([response.data], { type: 'application/pdf' });
        const url = window.URL.createObjectURL(blob);
        const filenames = {
            invoice_blank: 'modele-facture-luxembourg.pdf',
            aed_checklist: 'checklist-controle-aed.pdf',
            reminder_letter: 'modele-lettre-relance-impaye.pdf',
            vat_calendar: 'calendrier-tva-luxembourg-2026.pdf',
        };
        const link = document.createElement('a');
        link.href = url;
        link.download = filenames[template];
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

        if (!downloaded.value.includes(template)) {
            downloaded.value.push(template);
        }
    } catch (e) {
        console.error('[downloadTemplate]', e);
        if (e.response?.status === 429) {
            error.value = t('tools.templates.error_rate_limit') || t('tools.templates.error_generic');
        } else {
            error.value = t('tools.templates.error_generic');
        }
    } finally {
        downloading.value = null;
    }
}
</script>

<template>
    <SeoHead
        :title="t('tools.templates.page_title')"
        :description="t('tools.templates.meta_description')"
        canonical-path="/outils/modeles-facture"
        route-name="tools.templates"
    />

    <MarketingLayout>
        <div class="py-12 sm:py-16">
            <div class="mx-auto max-w-5xl px-6 lg:px-8">
                <!-- Breadcrumb -->
                <nav class="mb-8 text-sm">
                    <Link :href="localizedRoute('home')" class="text-slate-500 hover:text-slate-700">faktur.lu</Link>
                    <span class="text-slate-400 mx-2">/</span>
                    <Link :href="localizedRoute('tools')" class="text-slate-500 hover:text-slate-700">{{ t('tools.index.breadcrumb') }}</Link>
                    <span class="text-slate-400 mx-2">/</span>
                    <span class="text-slate-900">{{ t('tools.templates.breadcrumb') }}</span>
                </nav>

                <!-- Hero -->
                <div class="text-center mb-12">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-orange-500/10 text-orange-600 text-sm font-medium mb-6">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        {{ t('tools.templates.badge') }}
                    </div>
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 leading-tight mb-6">
                        {{ t('tools.templates.title') }}
                    </h1>
                    <p class="text-lg text-slate-600 max-w-3xl mx-auto leading-relaxed">
                        {{ t('tools.templates.subtitle') }}
                    </p>
                </div>

                <!-- Email capture form -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 sm:p-8 mb-10">
                    <h2 class="text-xl font-bold text-slate-900 mb-2">{{ t('tools.templates.form_title') }}</h2>
                    <p class="text-sm text-slate-600 mb-5">{{ t('tools.templates.form_subtitle') }}</p>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('tools.templates.email_label') }}</label>
                            <input
                                v-model="email"
                                type="email"
                                :placeholder="t('tools.templates.email_placeholder')"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500"
                                autocomplete="email"
                            />
                        </div>
                        <label class="flex items-start gap-2 cursor-pointer">
                            <input v-model="consent" type="checkbox" class="mt-1 rounded border-gray-300 text-primary-500 focus:ring-primary-500" />
                            <span class="text-xs text-slate-600">{{ t('tools.templates.consent') }}</span>
                        </label>
                        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
                    </div>
                </div>

                <!-- Templates grid -->
                <div class="grid sm:grid-cols-2 gap-5 mb-12">
                    <div
                        v-for="tpl in templates"
                        :key="tpl.key"
                        class="bg-white rounded-2xl border border-gray-200 p-5 sm:p-6 hover:shadow-md transition-shadow"
                    >
                        <div class="flex items-start gap-4 mb-4">
                            <div :class="['w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0', tpl.color]">
                                <svg v-if="tpl.icon === 'file'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                <svg v-else-if="tpl.icon === 'check'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                                <svg v-else-if="tpl.icon === 'mail'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                <svg v-else-if="tpl.icon === 'calendar'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-slate-900 mb-1">{{ t(`tools.templates.items.${tpl.key}.title`) }}</h3>
                                <p class="text-sm text-slate-600 leading-relaxed">{{ t(`tools.templates.items.${tpl.key}.description`) }}</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            :disabled="downloading === tpl.key"
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-medium rounded-lg transition-colors"
                            @click="downloadTemplate(tpl.key)"
                        >
                            <svg v-if="downloading === tpl.key" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <svg v-else-if="downloaded.includes(tpl.key)" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                            {{
                                downloading === tpl.key
                                    ? t('tools.templates.downloading')
                                    : downloaded.includes(tpl.key)
                                        ? t('tools.templates.downloaded')
                                        : t('tools.templates.download_button')
                            }}
                        </button>
                    </div>
                </div>

                <!-- CTA -->
                <div class="bg-gradient-to-br from-primary-50 to-[#00f5d4]/10 rounded-3xl p-8 sm:p-12 text-center">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4">{{ t('tools.templates.cta_title') }}</h2>
                    <p class="text-slate-600 mb-8 max-w-xl mx-auto">{{ t('tools.templates.cta_subtitle') }}</p>
                    <Link :href="route('register')" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-xl transition-colors">
                        {{ t('tools.templates.cta_button') }}
                    </Link>
                </div>
            </div>
        </div>
    </MarketingLayout>
</template>
