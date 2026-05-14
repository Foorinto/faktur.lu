<script setup>
import { Link } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';

const { t } = useTranslations();
const { localizedRoute } = useLocalizedRoute();
</script>

<template>
    <SeoHead
        :title="t('legal_cookies_seo_title')"
        :description="t('legal_cookies_seo_description')"
        canonical-path="/cookies"
    />

    <MarketingLayout>
        <!-- Content -->
        <div class="mx-auto max-w-4xl px-6 lg:px-8 py-12">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 lg:p-12">
                <h1 class="text-3xl font-bold text-slate-900 mb-2">{{ t('legal_cookies_h1') }}</h1>
                <p class="text-sm text-slate-500 mb-8">{{ t('legal_cookies_last_updated') }} {{ t('legal_cookies_last_updated_date') }}</p>

                <!-- Table des matières -->
                <nav class="mb-10 p-4 bg-slate-50 rounded-xl">
                    <h2 class="font-semibold text-slate-900 mb-3">{{ t('legal_cookies_toc_title') }}</h2>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#introduction" class="text-primary-500 hover:underline">{{ t('legal_cookies_toc_1') }}</a></li>
                        <li><a href="#cookies-utilises" class="text-primary-500 hover:underline">{{ t('legal_cookies_toc_2') }}</a></li>
                        <li><a href="#cookies-tiers" class="text-primary-500 hover:underline">{{ t('legal_cookies_toc_3') }}</a></li>
                        <li><a href="#duree" class="text-primary-500 hover:underline">{{ t('legal_cookies_toc_4') }}</a></li>
                        <li><a href="#gestion" class="text-primary-500 hover:underline">{{ t('legal_cookies_toc_5') }}</a></li>
                        <li><a href="#contact" class="text-primary-500 hover:underline">{{ t('legal_cookies_toc_6') }}</a></li>
                    </ul>
                </nav>

                <div class="prose prose-slate max-w-none">
                    <!-- 1. Introduction -->
                    <section id="introduction" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_cookies_s1_title') }}</h2>
                        <p class="text-slate-600 mb-4">
                            {{ t('legal_cookies_s1_p1') }}
                        </p>
                        <p class="text-slate-600">
                            {{ t('legal_cookies_s1_p2') }}
                        </p>
                    </section>

                    <!-- 2. Cookies utilisés -->
                    <section id="cookies-utilises" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_cookies_s2_title') }}</h2>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_cookies_s2_1_title') }}</h3>
                        <p class="text-slate-600 mb-4">
                            {{ t('legal_cookies_s2_1_p') }}
                        </p>
                        <div class="overflow-x-auto mb-6">
                            <table class="w-full text-sm border-collapse">
                                <thead>
                                    <tr class="bg-slate-100">
                                        <th class="border border-gray-200 px-4 py-2 text-left font-medium">{{ t('legal_cookies_table_cookie') }}</th>
                                        <th class="border border-gray-200 px-4 py-2 text-left font-medium">{{ t('legal_cookies_table_purpose') }}</th>
                                        <th class="border border-gray-200 px-4 py-2 text-left font-medium">{{ t('legal_cookies_table_duration') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-slate-600">
                                    <tr>
                                        <td class="border border-gray-200 px-4 py-2 font-mono text-xs">faktur_session</td>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_cookies_s2_1_row1_purpose') }}</td>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_cookies_s2_1_row1_duration') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-gray-200 px-4 py-2 font-mono text-xs">XSRF-TOKEN</td>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_cookies_s2_1_row2_purpose') }}</td>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_cookies_s2_1_row2_duration') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-gray-200 px-4 py-2 font-mono text-xs">remember_web_*</td>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_cookies_s2_1_row3_purpose') }}</td>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_cookies_s2_1_row3_duration') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_cookies_s2_2_title') }}</h3>
                        <p class="text-slate-600 mb-4">
                            {{ t('legal_cookies_s2_2_p') }}
                        </p>
                        <div class="overflow-x-auto mb-6">
                            <table class="w-full text-sm border-collapse">
                                <thead>
                                    <tr class="bg-slate-100">
                                        <th class="border border-gray-200 px-4 py-2 text-left font-medium">{{ t('legal_cookies_table_cookie') }}</th>
                                        <th class="border border-gray-200 px-4 py-2 text-left font-medium">{{ t('legal_cookies_table_purpose') }}</th>
                                        <th class="border border-gray-200 px-4 py-2 text-left font-medium">{{ t('legal_cookies_table_duration') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-slate-600">
                                    <tr>
                                        <td class="border border-gray-200 px-4 py-2 font-mono text-xs">locale</td>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_cookies_s2_2_row1_purpose') }}</td>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_cookies_s2_2_row1_duration') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-gray-200 px-4 py-2 font-mono text-xs">theme</td>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_cookies_s2_2_row2_purpose') }}</td>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_cookies_s2_2_row2_duration') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <h4 class="font-medium text-green-800 mb-2">{{ t('legal_cookies_s2_no_tracking_title') }}</h4>
                            <p class="text-sm text-green-700">
                                {{ t('legal_cookies_s2_no_tracking_p') }}
                            </p>
                        </div>
                    </section>

                    <!-- 3. Cookies tiers -->
                    <section id="cookies-tiers" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_cookies_s3_title') }}</h2>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_cookies_s3_stripe_title') }}</h3>
                        <p class="text-slate-600 mb-4">
                            {{ t('legal_cookies_s3_stripe_p1') }}
                        </p>
                        <p class="text-slate-600 mb-4">
                            {{ t('legal_cookies_s3_stripe_p2_prefix') }} <a href="https://stripe.com/fr/privacy" target="_blank" rel="noopener" class="text-primary-500">{{ t('legal_cookies_s3_stripe_link') }}</a>
                        </p>
                    </section>

                    <!-- 4. Durée -->
                    <section id="duree" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_cookies_s4_title') }}</h2>
                        <p class="text-slate-600 mb-4">
                            {{ t('legal_cookies_s4_p1') }}
                        </p>
                        <ul class="list-disc list-inside text-slate-600 space-y-2">
                            <li><strong>{{ t('legal_cookies_s4_li1_label') }}</strong> {{ t('legal_cookies_s4_li1_text') }}</li>
                            <li><strong>{{ t('legal_cookies_s4_li2_label') }}</strong> {{ t('legal_cookies_s4_li2_text') }}</li>
                        </ul>
                        <p class="text-slate-600 mt-4">
                            {{ t('legal_cookies_s4_p2') }}
                        </p>
                    </section>

                    <!-- 5. Gestion -->
                    <section id="gestion" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_cookies_s5_title') }}</h2>
                        <p class="text-slate-600 mb-4">
                            {{ t('legal_cookies_s5_p1') }}
                        </p>

                        <div class="space-y-3">
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800 mb-1">{{ t('legal_cookies_s5_chrome_title') }}</h4>
                                <p class="text-sm text-slate-600">{{ t('legal_cookies_s5_chrome_path') }}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800 mb-1">{{ t('legal_cookies_s5_firefox_title') }}</h4>
                                <p class="text-sm text-slate-600">{{ t('legal_cookies_s5_firefox_path') }}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800 mb-1">{{ t('legal_cookies_s5_safari_title') }}</h4>
                                <p class="text-sm text-slate-600">{{ t('legal_cookies_s5_safari_path') }}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800 mb-1">{{ t('legal_cookies_s5_edge_title') }}</h4>
                                <p class="text-sm text-slate-600">{{ t('legal_cookies_s5_edge_path') }}</p>
                            </div>
                        </div>

                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mt-6">
                            <h4 class="font-medium text-amber-800 mb-2">{{ t('legal_cookies_s5_warning_title') }}</h4>
                            <p class="text-sm text-amber-700">
                                {{ t('legal_cookies_s5_warning_p') }}
                            </p>
                        </div>
                    </section>

                    <!-- 6. Contact -->
                    <section id="contact">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_cookies_s6_title') }}</h2>
                        <p class="text-slate-600 mb-4">
                            {{ t('legal_cookies_s6_p1') }}
                        </p>
                        <div class="bg-slate-50 rounded-lg p-4 text-sm">
                            <ul class="space-y-2 text-slate-700">
                                <li><strong>{{ t('legal_cookies_s6_email_label') }}</strong> <a href="mailto:contact@faktur.lu" class="text-primary-500">contact@faktur.lu</a></li>
                            </ul>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Other legal links -->
            <div class="mt-8 flex flex-wrap gap-4 justify-center text-sm">
                <Link :href="localizedRoute('legal.mentions')" class="text-slate-600 hover:text-primary-500">{{ t('legal_cookies_link_mentions') }}</Link>
                <span class="text-slate-300">|</span>
                <Link :href="localizedRoute('legal.privacy')" class="text-slate-600 hover:text-primary-500">{{ t('legal_cookies_link_privacy') }}</Link>
                <span class="text-slate-300">|</span>
                <Link :href="localizedRoute('legal.terms')" class="text-slate-600 hover:text-primary-500">{{ t('legal_cookies_link_terms') }}</Link>
            </div>
        </div>
    </MarketingLayout>
</template>
