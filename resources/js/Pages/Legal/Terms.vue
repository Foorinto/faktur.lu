<script setup>
import { Link } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { useMarque } from "@/Composables/useMarque";

const { emailContact: marqueEmailContact, nom: marqueNom } = useMarque();


const { t } = useTranslations();
const { localizedRoute } = useLocalizedRoute();
</script>

<template>
    <SeoHead
        :title="t('legal_terms_seo_title')"
        :description="t('legal_terms_seo_description')"
        canonical-path="/cgu"
    />

    <MarketingLayout>
        <!-- Content -->
        <div class="mx-auto max-w-4xl px-6 lg:px-8 py-12">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 lg:p-12">
                <h1 class="text-3xl font-bold text-slate-900 mb-2">{{ t('legal_terms_h1') }}</h1>
                <p class="text-sm text-slate-500 mb-8">{{ t('legal_terms_last_updated_label') }} {{ t('legal_terms_last_updated_date') }}</p>

                <!-- Table des matières -->
                <nav class="mb-10 p-4 bg-slate-50 rounded-xl">
                    <h2 class="font-semibold text-slate-900 mb-3">{{ t('legal_terms_toc_title') }}</h2>
                    <ul class="space-y-2 text-sm grid md:grid-cols-2 gap-x-8">
                        <li><a href="#objet" class="text-primary-500 hover:underline">{{ t('legal_terms_toc_1') }}</a></li>
                        <li><a href="#definitions" class="text-primary-500 hover:underline">{{ t('legal_terms_toc_2') }}</a></li>
                        <li><a href="#acces" class="text-primary-500 hover:underline">{{ t('legal_terms_toc_3') }}</a></li>
                        <li><a href="#description" class="text-primary-500 hover:underline">{{ t('legal_terms_toc_4') }}</a></li>
                        <li><a href="#abonnements" class="text-primary-500 hover:underline">{{ t('legal_terms_toc_5') }}</a></li>
                        <li><a href="#paiement" class="text-primary-500 hover:underline">{{ t('legal_terms_toc_6') }}</a></li>
                        <li><a href="#retractation" class="text-primary-500 hover:underline">{{ t('legal_terms_toc_7') }}</a></li>
                        <li><a href="#obligations-utilisateur" class="text-primary-500 hover:underline">{{ t('legal_terms_toc_8') }}</a></li>
                        <li><a href="#obligations-faktur" class="text-primary-500 hover:underline">{{ t('legal_terms_toc_9') }}</a></li>
                        <li><a href="#donnees" class="text-primary-500 hover:underline">{{ t('legal_terms_toc_10') }}</a></li>
                        <li><a href="#propriete-intellectuelle" class="text-primary-500 hover:underline">{{ t('legal_terms_toc_11') }}</a></li>
                        <li><a href="#responsabilite" class="text-primary-500 hover:underline">{{ t('legal_terms_toc_12') }}</a></li>
                        <li><a href="#resiliation" class="text-primary-500 hover:underline">{{ t('legal_terms_toc_13') }}</a></li>
                        <li><a href="#modifications" class="text-primary-500 hover:underline">{{ t('legal_terms_toc_14') }}</a></li>
                        <li><a href="#droit-applicable" class="text-primary-500 hover:underline">{{ t('legal_terms_toc_15') }}</a></li>
                        <li><a href="#contact" class="text-primary-500 hover:underline">{{ t('legal_terms_toc_16') }}</a></li>
                    </ul>
                </nav>

                <div class="prose prose-slate max-w-none">
                    <!-- 1. Objet -->
                    <section id="objet" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_terms_s1_title') }}</h2>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s1_p1') }}</p>
                        <p class="text-slate-600">{{ t('legal_terms_s1_p2') }}</p>
                    </section>

                    <!-- 2. Définitions -->
                    <section id="definitions" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_terms_s2_title') }}</h2>
                        <ul class="list-disc list-inside text-slate-600 space-y-2">
                            <li><strong>{{ t('legal_terms_s2_li1_label') }}</strong> {{ t('legal_terms_s2_li1_text') }}</li>
                            <li><strong>{{ t('legal_terms_s2_li2_label') }}</strong> {{ t('legal_terms_s2_li2_text') }}</li>
                            <li><strong>{{ t('legal_terms_s2_li3_label') }}</strong> {{ t('legal_terms_s2_li3_text') }}</li>
                            <li><strong>{{ t('legal_terms_s2_li4_label') }}</strong> {{ t('legal_terms_s2_li4_text') }}</li>
                            <li><strong>{{ t('legal_terms_s2_li5_label') }}</strong> {{ t('legal_terms_s2_li5_text') }}</li>
                        </ul>
                    </section>

                    <!-- 3. Accès au service -->
                    <section id="acces" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_terms_s3_title') }}</h2>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s3_1_title') }}</h3>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s3_1_p') }}</p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s3_2_title') }}</h3>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s3_2_p') }}</p>
                        <ul class="list-disc list-inside text-slate-600 space-y-1 mb-4">
                            <li>{{ t('legal_terms_s3_2_li1') }}</li>
                            <li>{{ t('legal_terms_s3_2_li2') }}</li>
                            <li>{{ t('legal_terms_s3_2_li3') }}</li>
                        </ul>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s3_3_title') }}</h3>
                        <p class="text-slate-600">{{ t('legal_terms_s3_3_p') }}</p>
                    </section>

                    <!-- 4. Description du service -->
                    <section id="description" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_terms_s4_title') }}</h2>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s4_p') }}</p>
                        <ul class="list-disc list-inside text-slate-600 space-y-1">
                            <li>{{ t('legal_terms_s4_li1') }}</li>
                            <li>{{ t('legal_terms_s4_li2') }}</li>
                            <li>{{ t('legal_terms_s4_li3') }}</li>
                            <li>{{ t('legal_terms_s4_li4') }}</li>
                            <li>{{ t('legal_terms_s4_li5') }}</li>
                            <li>{{ t('legal_terms_s4_li6') }}</li>
                            <li>{{ t('legal_terms_s4_li7') }}</li>
                        </ul>
                    </section>

                    <!-- 5. Abonnements et tarifs -->
                    <section id="abonnements" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_terms_s5_title') }}</h2>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s5_1_title') }}</h3>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s5_1_p') }}</p>
                        <div class="space-y-4 mb-6">
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800">{{ t('legal_terms_s5_plan1_title') }}</h4>
                                <p class="text-sm text-slate-600">{{ t('legal_terms_s5_plan1_desc') }}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800">{{ t('legal_terms_s5_plan2_title') }}</h4>
                                <p class="text-sm text-slate-600">{{ t('legal_terms_s5_plan2_desc') }}</p>
                            </div>
                        </div>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s5_2_title') }}</h3>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s5_2_p1') }}</p>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s5_2_p2') }}</p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s5_3_title') }}</h3>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s5_3_p') }}</p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s5_4_title') }}</h3>
                        <p class="text-slate-600">{{ t('legal_terms_s5_4_p') }}</p>
                    </section>

                    <!-- 6. Paiement -->
                    <section id="paiement" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_terms_s6_title') }}</h2>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s6_1_title') }}</h3>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s6_1_p') }}</p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s6_2_title') }}</h3>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s6_2_p') }}</p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s6_3_title') }}</h3>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s6_3_p') }}</p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s6_4_title') }}</h3>
                        <p class="text-slate-600">{{ t('legal_terms_s6_4_p') }}</p>
                    </section>

                    <!-- 7. Droit de rétractation -->
                    <section id="retractation" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_terms_s7_title') }}</h2>

                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4">
                            <h4 class="font-medium text-amber-800 mb-2">{{ t('legal_terms_s7_warn_title') }}</h4>
                            <p class="text-sm text-amber-700">{{ t('legal_terms_s7_warn_p') }}</p>
                        </div>

                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s7_p1') }}</p>
                        <ul class="list-disc list-inside text-slate-600 space-y-1 mb-4">
                            <li>{{ t('legal_terms_s7_li1') }}</li>
                            <li>{{ t('legal_terms_s7_li2') }}</li>
                        </ul>

                        <p class="text-slate-600">{{ t('legal_terms_s7_p2') }}</p>
                    </section>

                    <!-- 8. Obligations de l'utilisateur -->
                    <section id="obligations-utilisateur" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_terms_s8_title') }}</h2>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s8_p') }}</p>
                        <ul class="list-disc list-inside text-slate-600 space-y-2">
                            <li>{{ t('legal_terms_s8_li1') }}</li>
                            <li>{{ t('legal_terms_s8_li2') }}</li>
                            <li>{{ t('legal_terms_s8_li3') }}</li>
                            <li>{{ t('legal_terms_s8_li4') }}</li>
                            <li>{{ t('legal_terms_s8_li5') }}</li>
                            <li>{{ t('legal_terms_s8_li6') }}</li>
                            <li>{{ t('legal_terms_s8_li7') }}</li>
                        </ul>
                    </section>

                    <!-- 9. Obligations de {{ marqueNom }} -->
                    <section id="obligations-faktur" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_terms_s9_title') }}</h2>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s9_1_title') }}</h3>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s9_1_p1') }}</p>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s9_1_p2') }}</p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s9_2_title') }}</h3>
                        <p class="text-slate-600 mb-4">
                            {{ t('legal_terms_s9_2_p_before') }} <a href="mailto:${marqueEmailContact}" class="text-primary-500">{{ marqueEmailContact }}</a>{{ t('legal_terms_s9_2_p_after') }}
                        </p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s9_3_title') }}</h3>
                        <p class="text-slate-600">{{ t('legal_terms_s9_3_p') }}</p>
                    </section>

                    <!-- 10. Données et propriété -->
                    <section id="donnees" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_terms_s10_title') }}</h2>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s10_1_title') }}</h3>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s10_1_p') }}</p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s10_2_title') }}</h3>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s10_2_p') }}</p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s10_3_title') }}</h3>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s10_3_p') }}</p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s10_4_title') }}</h3>
                        <p class="text-slate-600">
                            {{ t('legal_terms_s10_4_p_before') }}
                            <Link :href="localizedRoute('legal.privacy')" class="text-primary-500 hover:underline">{{ t('legal_terms_s10_4_link') }}</Link>{{ t('legal_terms_s10_4_p_after') }}
                        </p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s10_5_title') }}</h3>
                        <p class="text-slate-600">
                            {{ t('legal_terms_s10_5_p_before') }}<a :href="localizedRoute('legal.dpa')" class="text-primary-500 hover:underline">{{ t('legal_terms_s10_5_link') }}</a>{{ t('legal_terms_s10_5_p_after') }}
                        </p>
                    </section>

                    <!-- 11. Propriété intellectuelle -->
                    <section id="propriete-intellectuelle" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_terms_s11_title') }}</h2>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s11_p1') }}</p>
                        <p class="text-slate-600">{{ t('legal_terms_s11_p2') }}</p>
                    </section>

                    <!-- 12. Responsabilité -->
                    <section id="responsabilite" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_terms_s12_title') }}</h2>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s12_1_title') }}</h3>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s12_1_p') }}</p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s12_2_title') }}</h3>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s12_2_p1') }}</p>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s12_2_p2') }}</p>
                        <ul class="list-disc list-inside text-slate-600 space-y-1">
                            <li>{{ t('legal_terms_s12_2_li1') }}</li>
                            <li>{{ t('legal_terms_s12_2_li2') }}</li>
                            <li>{{ t('legal_terms_s12_2_li3') }}</li>
                            <li>{{ t('legal_terms_s12_2_li4') }}</li>
                            <li>{{ t('legal_terms_s12_2_li5') }}</li>
                        </ul>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s12_3_title') }}</h3>
                        <p class="text-slate-600">{{ t('legal_terms_s12_3_p') }}</p>
                    </section>

                    <!-- 13. Résiliation -->
                    <section id="resiliation" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_terms_s13_title') }}</h2>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s13_1_title') }}</h3>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s13_1_p1') }}</p>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s13_1_p2') }}</p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s13_2_title') }}</h3>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s13_2_p') }}</p>
                        <ul class="list-disc list-inside text-slate-600 space-y-1 mb-4">
                            <li>{{ t('legal_terms_s13_2_li1') }}</li>
                            <li>{{ t('legal_terms_s13_2_li2') }}</li>
                            <li>{{ t('legal_terms_s13_2_li3') }}</li>
                            <li>{{ t('legal_terms_s13_2_li4') }}</li>
                        </ul>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">{{ t('legal_terms_s13_3_title') }}</h3>
                        <p class="text-slate-600">{{ t('legal_terms_s13_3_p') }}</p>
                    </section>

                    <!-- 14. Modifications -->
                    <section id="modifications" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_terms_s14_title') }}</h2>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s14_p1') }}</p>
                        <p class="text-slate-600">{{ t('legal_terms_s14_p2') }}</p>
                    </section>

                    <!-- 15. Droit applicable -->
                    <section id="droit-applicable" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_terms_s15_title') }}</h2>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s15_p1') }}</p>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s15_p2') }}</p>
                        <p class="text-slate-600">
                            <strong>{{ t('legal_terms_s15_p3_label') }}</strong> {{ t('legal_terms_s15_p3_text') }} <a href="https://ec.europa.eu/consumers/odr" target="_blank" rel="noopener" class="text-primary-500">https://ec.europa.eu/consumers/odr</a>
                        </p>
                    </section>

                    <!-- 16. Contact -->
                    <section id="contact">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_terms_s16_title') }}</h2>
                        <p class="text-slate-600 mb-4">{{ t('legal_terms_s16_p') }}</p>
                        <div class="bg-slate-50 rounded-lg p-4 text-sm">
                            <ul class="space-y-2 text-slate-700">
                                <li><strong>{{ t('legal_terms_s16_email_label') }}</strong> <a href="mailto:${marqueEmailContact}" class="text-primary-500">{{ marqueEmailContact }}</a></li>
                                <li><strong>{{ t('legal_terms_s16_address_label') }}</strong> {{ t('legal_terms_s16_address_value') }}</li>
                            </ul>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Other legal links -->
            <div class="mt-8 flex flex-wrap gap-4 justify-center text-sm">
                <Link :href="localizedRoute('legal.mentions')" class="text-slate-600 hover:text-primary-500">{{ t('legal_terms_link_mentions') }}</Link>
                <span class="text-slate-300">|</span>
                <Link :href="localizedRoute('legal.privacy')" class="text-slate-600 hover:text-primary-500">{{ t('legal_terms_link_privacy') }}</Link>
                <span class="text-slate-300">|</span>
                <Link :href="localizedRoute('legal.cookies')" class="text-slate-600 hover:text-primary-500">{{ t('legal_terms_link_cookies') }}</Link>
            </div>
        </div>
    </MarketingLayout>
</template>
