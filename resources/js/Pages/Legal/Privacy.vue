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
        :title="t('legal_privacy_seo_title')"
        :description="t('legal_privacy_seo_description')"
        canonical-path="/confidentialite"
    />

    <MarketingLayout>
        <!-- Content -->
        <div class="mx-auto max-w-4xl px-6 lg:px-8 py-12">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 lg:p-12">
                <h1 class="text-3xl font-bold text-slate-900 mb-2">{{ t('legal_privacy_h1_title') }}</h1>
                <p class="text-sm text-slate-500 mb-8">{{ t('legal_privacy_last_updated_label') }} {{ t('legal_privacy_last_updated_date') }}</p>

                <!-- Table des matières -->
                <nav class="mb-10 p-4 bg-slate-50 rounded-xl">
                    <h2 class="font-semibold text-slate-900 mb-3">{{ t('legal_privacy_toc_title') }}</h2>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#introduction" class="text-primary-500 hover:underline">{{ t('legal_privacy_toc_1') }}</a></li>
                        <li><a href="#responsable" class="text-primary-500 hover:underline">{{ t('legal_privacy_toc_2') }}</a></li>
                        <li><a href="#donnees-collectees" class="text-primary-500 hover:underline">{{ t('legal_privacy_toc_3') }}</a></li>
                        <li><a href="#finalites" class="text-primary-500 hover:underline">{{ t('legal_privacy_toc_4') }}</a></li>
                        <li><a href="#conservation" class="text-primary-500 hover:underline">{{ t('legal_privacy_toc_5') }}</a></li>
                        <li><a href="#destinataires" class="text-primary-500 hover:underline">{{ t('legal_privacy_toc_6') }}</a></li>
                        <li><a href="#transferts" class="text-primary-500 hover:underline">{{ t('legal_privacy_toc_7') }}</a></li>
                        <li><a href="#droits" class="text-primary-500 hover:underline">{{ t('legal_privacy_toc_8') }}</a></li>
                        <li><a href="#securite" class="text-primary-500 hover:underline">{{ t('legal_privacy_toc_9') }}</a></li>
                        <li><a href="#modifications" class="text-primary-500 hover:underline">{{ t('legal_privacy_toc_10') }}</a></li>
                        <li><a href="#contact" class="text-primary-500 hover:underline">{{ t('legal_privacy_toc_11') }}</a></li>
                    </ul>
                </nav>

                <div class="prose prose-slate max-w-none">
                    <!-- 1. Introduction -->
                    <section id="introduction" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_privacy_s1_title') }}</h2>
                        <p class="text-slate-600 mb-4">
                            {{ t('legal_privacy_s1_p1') }}
                        </p>
                        <p class="text-slate-600">
                            {{ t('legal_privacy_s1_p2') }}
                        </p>
                    </section>

                    <!-- 2. Responsable du traitement -->
                    <section id="responsable" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_privacy_s2_title') }}</h2>
                        <div class="bg-slate-50 rounded-lg p-4 text-sm">
                            <ul class="space-y-2 text-slate-700">
                                <li><strong>{{ t('legal_privacy_s2_label_responsable') }}</strong> {{ t('legal_privacy_s2_value_responsable') }}</li>
                                <li><strong>{{ t('legal_privacy_s2_label_address') }}</strong> {{ t('legal_privacy_s2_value_address') }}</li>
                                <li><strong>{{ t('legal_privacy_s2_label_email') }}</strong> <a href="mailto:contact@faktur.lu" class="text-primary-500">contact@faktur.lu</a></li>
                                <li><strong>{{ t('legal_privacy_s2_label_rcs') }}</strong> {{ t('legal_privacy_s2_value_rcs') }}</li>
                            </ul>
                        </div>
                    </section>

                    <!-- 3. Données collectées -->
                    <section id="donnees-collectees" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_privacy_s3_title') }}</h2>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_privacy_s3_1_title') }}</h3>
                        <ul class="list-disc list-inside text-slate-600 space-y-1 mb-4">
                            <li>{{ t('legal_privacy_s3_1_li1') }}</li>
                            <li>{{ t('legal_privacy_s3_1_li2') }}</li>
                            <li>{{ t('legal_privacy_s3_1_li3') }}</li>
                            <li>{{ t('legal_privacy_s3_1_li4') }}</li>
                        </ul>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_privacy_s3_2_title') }}</h3>
                        <ul class="list-disc list-inside text-slate-600 space-y-1 mb-4">
                            <li>{{ t('legal_privacy_s3_2_li1') }}</li>
                            <li>{{ t('legal_privacy_s3_2_li2') }}</li>
                            <li>{{ t('legal_privacy_s3_2_li3') }}</li>
                        </ul>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_privacy_s3_3_title') }}</h3>
                        <p class="text-slate-600 mb-4">
                            {{ t('legal_privacy_s3_3_p1') }}
                        </p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_privacy_s3_4_title') }}</h3>
                        <ul class="list-disc list-inside text-slate-600 space-y-1">
                            <li>{{ t('legal_privacy_s3_4_li1') }}</li>
                            <li>{{ t('legal_privacy_s3_4_li2') }}</li>
                            <li>{{ t('legal_privacy_s3_4_li3') }}</li>
                            <li>{{ t('legal_privacy_s3_4_li4') }}</li>
                        </ul>
                    </section>

                    <!-- 4. Finalités et bases légales -->
                    <section id="finalites" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_privacy_s4_title') }}</h2>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm border-collapse">
                                <thead>
                                    <tr class="bg-slate-100">
                                        <th class="border border-gray-200 px-4 py-2 text-left font-medium">{{ t('legal_privacy_s4_th_purpose') }}</th>
                                        <th class="border border-gray-200 px-4 py-2 text-left font-medium">{{ t('legal_privacy_s4_th_legal_basis') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-slate-600">
                                    <tr>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s4_row1_purpose') }}</td>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s4_row1_basis') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s4_row2_purpose') }}</td>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s4_row2_basis') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s4_row3_purpose') }}</td>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s4_row3_basis') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s4_row4_purpose') }}</td>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s4_row4_basis') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s4_row5_purpose') }}</td>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s4_row5_basis') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s4_row6_purpose') }}</td>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s4_row6_basis') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s4_row7_purpose') }}</td>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s4_row7_basis') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s4_row8_purpose') }}</td>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s4_row8_basis') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- 5. Durée de conservation -->
                    <section id="conservation" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_privacy_s5_title') }}</h2>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm border-collapse">
                                <thead>
                                    <tr class="bg-slate-100">
                                        <th class="border border-gray-200 px-4 py-2 text-left font-medium">{{ t('legal_privacy_s5_th_type') }}</th>
                                        <th class="border border-gray-200 px-4 py-2 text-left font-medium">{{ t('legal_privacy_s5_th_duration') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-slate-600">
                                    <tr>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s5_row1_type') }}</td>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s5_row1_duration') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s5_row2_type') }}</td>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s5_row2_duration') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s5_row3_type') }}</td>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s5_row3_duration') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s5_row4_type') }}</td>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s5_row4_duration') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s5_row5_type') }}</td>
                                        <td class="border border-gray-200 px-4 py-2">{{ t('legal_privacy_s5_row5_duration') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- 6. Destinataires -->
                    <section id="destinataires" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_privacy_s6_title') }}</h2>
                        <p class="text-slate-600 mb-4">
                            {{ t('legal_privacy_s6_p1') }}
                        </p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_privacy_s6_h3_subprocessors') }}</h3>
                        <ul class="list-disc list-inside text-slate-600 space-y-2">
                            <li><strong>Stripe</strong> {{ t('legal_privacy_s6_sub_stripe') }}</li>
                            <li><strong>Brevo</strong> {{ t('legal_privacy_s6_sub_brevo') }}</li>
                            <li><strong>o2switch</strong> {{ t('legal_privacy_s6_sub_o2switch') }}</li>
                        </ul>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_privacy_s6_h3_other') }}</h3>
                        <ul class="list-disc list-inside text-slate-600 space-y-2">
                            <li>{{ t('legal_privacy_s6_other_li1') }}</li>
                            <li>{{ t('legal_privacy_s6_other_li2') }}</li>
                        </ul>
                    </section>

                    <!-- 7. Transferts hors UE -->
                    <section id="transferts" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_privacy_s7_title') }}</h2>
                        <p class="text-slate-600 mb-4">
                            {{ t('legal_privacy_s7_p1') }}
                        </p>
                        <ul class="list-disc list-inside text-slate-600 space-y-1">
                            <li>{{ t('legal_privacy_s7_li1') }}</li>
                            <li>{{ t('legal_privacy_s7_li2') }}</li>
                            <li>{{ t('legal_privacy_s7_li3') }}</li>
                        </ul>
                    </section>

                    <!-- 8. Vos droits -->
                    <section id="droits" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_privacy_s8_title') }}</h2>
                        <p class="text-slate-600 mb-4">
                            {{ t('legal_privacy_s8_p1') }}
                        </p>

                        <div class="space-y-4">
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800 mb-2">{{ t('legal_privacy_s8_right1_title') }}</h4>
                                <p class="text-sm text-slate-600">{{ t('legal_privacy_s8_right1_desc') }}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800 mb-2">{{ t('legal_privacy_s8_right2_title') }}</h4>
                                <p class="text-sm text-slate-600">{{ t('legal_privacy_s8_right2_desc') }}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800 mb-2">{{ t('legal_privacy_s8_right3_title') }}</h4>
                                <p class="text-sm text-slate-600">{{ t('legal_privacy_s8_right3_desc') }}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800 mb-2">{{ t('legal_privacy_s8_right4_title') }}</h4>
                                <p class="text-sm text-slate-600">{{ t('legal_privacy_s8_right4_desc') }}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800 mb-2">{{ t('legal_privacy_s8_right5_title') }}</h4>
                                <p class="text-sm text-slate-600">{{ t('legal_privacy_s8_right5_desc') }}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800 mb-2">{{ t('legal_privacy_s8_right6_title') }}</h4>
                                <p class="text-sm text-slate-600">{{ t('legal_privacy_s8_right6_desc') }}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800 mb-2">{{ t('legal_privacy_s8_right7_title') }}</h4>
                                <p class="text-sm text-slate-600">{{ t('legal_privacy_s8_right7_desc') }}</p>
                            </div>
                        </div>

                        <p class="text-slate-600 mt-6">
                            {{ t('legal_privacy_s8_p_contact_before') }} <a href="mailto:contact@faktur.lu" class="text-primary-500">contact@faktur.lu</a>{{ t('legal_privacy_s8_p_contact_after') }}
                        </p>

                        <p class="text-slate-600 mt-4">
                            <strong>{{ t('legal_privacy_s8_complaint_label') }}</strong> {{ t('legal_privacy_s8_complaint_text') }}
                            <a href="https://cnpd.public.lu" target="_blank" rel="noopener" class="text-primary-500">https://cnpd.public.lu</a>
                        </p>
                    </section>

                    <!-- 9. Sécurité -->
                    <section id="securite" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_privacy_s9_title') }}</h2>
                        <p class="text-slate-600 mb-4">
                            {{ t('legal_privacy_s9_p1') }}
                        </p>
                        <ul class="list-disc list-inside text-slate-600 space-y-1">
                            <li>{{ t('legal_privacy_s9_li1') }}</li>
                            <li>{{ t('legal_privacy_s9_li2') }}</li>
                            <li>{{ t('legal_privacy_s9_li3') }}</li>
                            <li>{{ t('legal_privacy_s9_li4') }}</li>
                            <li>{{ t('legal_privacy_s9_li5') }}</li>
                            <li>{{ t('legal_privacy_s9_li6') }}</li>
                            <li>{{ t('legal_privacy_s9_li7') }}</li>
                        </ul>
                    </section>

                    <!-- 10. Modifications -->
                    <section id="modifications" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_privacy_s10_title') }}</h2>
                        <p class="text-slate-600">
                            {{ t('legal_privacy_s10_p1') }}
                        </p>
                    </section>

                    <!-- 11. Contact -->
                    <section id="contact">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_privacy_s11_title') }}</h2>
                        <p class="text-slate-600 mb-4">
                            {{ t('legal_privacy_s11_p1') }}
                        </p>
                        <div class="bg-slate-50 rounded-lg p-4 text-sm">
                            <ul class="space-y-2 text-slate-700">
                                <li><strong>{{ t('legal_privacy_s11_label_email') }}</strong> <a href="mailto:contact@faktur.lu" class="text-primary-500">contact@faktur.lu</a></li>
                                <li><strong>{{ t('legal_privacy_s11_label_address') }}</strong> {{ t('legal_privacy_s11_value_address') }}</li>
                            </ul>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Other legal links -->
            <div class="mt-8 flex flex-wrap gap-4 justify-center text-sm">
                <Link :href="localizedRoute('legal.mentions')" class="text-slate-600 hover:text-primary-500">{{ t('legal_privacy_link_mentions') }}</Link>
                <span class="text-slate-300">|</span>
                <Link :href="localizedRoute('legal.terms')" class="text-slate-600 hover:text-primary-500">{{ t('legal_privacy_link_terms') }}</Link>
                <span class="text-slate-300">|</span>
                <Link :href="localizedRoute('legal.cookies')" class="text-slate-600 hover:text-primary-500">{{ t('legal_privacy_link_cookies') }}</Link>
            </div>
        </div>
    </MarketingLayout>
</template>
