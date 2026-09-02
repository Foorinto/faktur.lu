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
        :title="t('legal_mentions_seo_title')"
        :description="t('legal_mentions_seo_description')"
        canonical-path="/mentions-legales"
    />

    <MarketingLayout>
        <!-- Content -->
        <div class="mx-auto max-w-4xl px-6 lg:px-8 py-12">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 lg:p-12">
                <h1 class="text-3xl font-bold text-slate-900 mb-2">{{ t('legal_mentions_title') }}</h1>
                <p class="text-sm text-slate-500 mb-8">{{ t('legal_mentions_last_updated_label') }} {{ t('legal_mentions_last_updated_date') }}</p>

                <!-- Table des matières -->
                <nav class="mb-10 p-4 bg-slate-50 rounded-xl">
                    <h2 class="font-semibold text-slate-900 mb-3">{{ t('legal_mentions_toc_title') }}</h2>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#editeur" class="text-primary-500 hover:underline">{{ t('legal_mentions_toc_1') }}</a></li>
                        <li><a href="#hebergeur" class="text-primary-500 hover:underline">{{ t('legal_mentions_toc_2') }}</a></li>
                        <li><a href="#propriete" class="text-primary-500 hover:underline">{{ t('legal_mentions_toc_3') }}</a></li>
                        <li><a href="#donnees" class="text-primary-500 hover:underline">{{ t('legal_mentions_toc_4') }}</a></li>
                        <li><a href="#cookies" class="text-primary-500 hover:underline">{{ t('legal_mentions_toc_5') }}</a></li>
                        <li><a href="#contact" class="text-primary-500 hover:underline">{{ t('legal_mentions_toc_6') }}</a></li>
                    </ul>
                </nav>

                <div class="prose prose-slate max-w-none">
                    <!-- 1. Éditeur -->
                    <section id="editeur" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_mentions_s1_title') }}</h2>
                        <p class="text-slate-600 mb-4">
                            {{ t('legal_mentions_s1_intro_before') }} <strong>{{ marqueNom }}</strong> {{ t('legal_mentions_s1_intro_after') }}
                        </p>
                        <div class="bg-slate-50 rounded-lg p-4 text-sm">
                            <ul class="space-y-2 text-slate-700">
                                <li><strong>{{ t('legal_mentions_s1_label_company') }}</strong> {{ t('legal_mentions_s1_value_company') }}</li>
                                <li><strong>{{ t('legal_mentions_s1_label_legal_form') }}</strong> {{ t('legal_mentions_s1_value_legal_form') }}</li>
                                <li><strong>{{ t('legal_mentions_s1_label_address') }}</strong> {{ t('legal_mentions_s1_value_address') }}</li>
                                <li><strong>{{ t('legal_mentions_s1_label_rcs') }}</strong> {{ t('legal_mentions_s1_value_rcs') }}</li>
                                <li><strong>{{ t('legal_mentions_s1_label_vat') }}</strong> {{ t('legal_mentions_s1_value_vat') }}</li>
                                <li><strong>{{ t('legal_mentions_s1_label_email') }}</strong> <a href="mailto:${marqueEmailContact}" class="text-primary-500">{{ marqueEmailContact }}</a></li>
                                <li><strong>{{ t('legal_mentions_s1_label_publication_director') }}</strong> {{ t('legal_mentions_s1_value_publication_director') }}</li>
                            </ul>
                        </div>
                    </section>

                    <!-- 2. Hébergeur -->
                    <section id="hebergeur" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_mentions_s2_title') }}</h2>
                        <p class="text-slate-600 mb-4">
                            {{ t('legal_mentions_s2_intro') }}
                        </p>
                        <div class="bg-slate-50 rounded-lg p-4 text-sm">
                            <ul class="space-y-2 text-slate-700">
                                <li><strong>{{ t('legal_mentions_s2_label_name') }}</strong> {{ t('legal_mentions_s2_value_name') }}</li>
                                <li><strong>{{ t('legal_mentions_s2_label_address') }}</strong> {{ t('legal_mentions_s2_value_address') }}</li>
                                <li><strong>{{ t('legal_mentions_s2_label_website') }}</strong> <a href="https://www.o2switch.fr" target="_blank" rel="noopener" class="text-primary-500">www.o2switch.fr</a></li>
                            </ul>
                        </div>
                    </section>

                    <!-- 3. Propriété intellectuelle -->
                    <section id="propriete" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_mentions_s3_title') }}</h2>
                        <p class="text-slate-600 mb-4">
                            {{ t('legal_mentions_s3_p1') }}
                        </p>
                        <p class="text-slate-600 mb-4">
                            {{ t('legal_mentions_s3_p2') }}
                        </p>
                        <p class="text-slate-600">
                            {{ t('legal_mentions_s3_p3') }}
                        </p>
                    </section>

                    <!-- 4. Protection des données -->
                    <section id="donnees" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_mentions_s4_title') }}</h2>
                        <p class="text-slate-600 mb-4">
                            {{ t('legal_mentions_s4_p1') }}
                        </p>
                        <p class="text-slate-600 mb-4">
                            {{ t('legal_mentions_s4_p2_before_link') }}
                            <Link :href="localizedRoute('legal.privacy')" class="text-primary-500 hover:underline">{{ t('legal_mentions_s4_p2_link_text') }}</Link>{{ t('legal_mentions_s4_p2_after_link') }}
                        </p>
                        <p class="text-slate-600">
                            <strong>{{ t('legal_mentions_s4_authority_label') }}</strong> {{ t('legal_mentions_s4_authority_value') }}<br>
                            <a href="https://cnpd.public.lu" target="_blank" rel="noopener" class="text-primary-500">https://cnpd.public.lu</a>
                        </p>
                    </section>

                    <!-- 5. Cookies -->
                    <section id="cookies" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_mentions_s5_title') }}</h2>
                        <p class="text-slate-600 mb-4">
                            {{ t('legal_mentions_s5_p1') }}
                        </p>
                        <p class="text-slate-600">
                            {{ t('legal_mentions_s5_p2_before_link') }}
                            <Link :href="localizedRoute('legal.cookies')" class="text-primary-500 hover:underline">{{ t('legal_mentions_s5_p2_link_text') }}</Link>{{ t('legal_mentions_s5_p2_after_link') }}
                        </p>
                    </section>

                    <!-- 6. Contact -->
                    <section id="contact">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">{{ t('legal_mentions_s6_title') }}</h2>
                        <p class="text-slate-600 mb-4">
                            {{ t('legal_mentions_s6_p') }}
                        </p>
                        <ul class="text-slate-600 space-y-1">
                            <li><strong>{{ t('legal_mentions_s6_label_email') }}</strong> <a href="mailto:${marqueEmailContact}" class="text-primary-500">{{ marqueEmailContact }}</a></li>
                            <li><strong>{{ t('legal_mentions_s6_label_address') }}</strong> {{ t('legal_mentions_s6_value_address') }}</li>
                        </ul>
                    </section>
                </div>
            </div>

            <!-- Other legal links -->
            <div class="mt-8 flex flex-wrap gap-4 justify-center text-sm">
                <Link :href="localizedRoute('legal.privacy')" class="text-slate-600 hover:text-primary-500">{{ t('legal_mentions_link_privacy') }}</Link>
                <span class="text-slate-300">|</span>
                <Link :href="localizedRoute('legal.terms')" class="text-slate-600 hover:text-primary-500">{{ t('legal_mentions_link_terms') }}</Link>
                <span class="text-slate-300">|</span>
                <Link :href="localizedRoute('legal.cookies')" class="text-slate-600 hover:text-primary-500">{{ t('legal_mentions_link_cookies') }}</Link>
            </div>
        </div>
    </MarketingLayout>
</template>
