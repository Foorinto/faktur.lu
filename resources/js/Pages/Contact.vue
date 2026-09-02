<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted } from 'vue';
import SeoHead from '@/Components/SeoHead.vue';
import HoneypotFields from '@/Components/HoneypotFields.vue';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';
import { useMarque } from "@/Composables/useMarque";

const { emailContact: marqueEmailContact, nom: marqueNom, url: marqueUrl } = useMarque();

const { t } = useTranslations();
const { localizedRoute } = useLocalizedRoute();
const appUrl = computed(() => usePage().props.appUrl || marqueUrl.value);

const schemaBreadcrumb = computed(() => JSON.stringify({
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        { "@type": "ListItem", "position": 1, "name": marqueNom.value, "item": appUrl.value },
        { "@type": "ListItem", "position": 2, "name": t('contact.breadcrumb') },
    ],
}));

onMounted(() => {
    const script = document.createElement('script');
    script.id = 'schema-breadcrumb';
    script.type = 'application/ld+json';
    script.textContent = schemaBreadcrumb.value;
    document.head.appendChild(script);
});
onUnmounted(() => { document.getElementById('schema-breadcrumb')?.remove(); });
const page = usePage();

const form = useForm({
    name: '',
    email: '',
    subject: '',
    message: '',
    homepage_url: '',
    form_loaded_at: '',
});

const success = computed(() => page.props.flash?.success);

const submit = () => {
    form.post(localizedRoute('contact.send'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <SeoHead
        :title="t('contact.page_title')"
        :description="t('contact.meta_description')"
        canonical-path="/contact"
        route-name="contact"
    />

    <MarketingLayout>
        <!-- Breadcrumb -->
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-4">
            <nav class="flex text-sm text-slate-500">
                <Link :href="localizedRoute('home')" class="hover:text-slate-900 transition-colors">{{ t('features.breadcrumb.home') }}</Link>
                <span class="mx-2">/</span>
                <span class="text-slate-900 font-medium">{{ t('contact.breadcrumb') }}</span>
            </nav>
        </div>

        <!-- Main -->
        <section class="py-16 sm:py-24">
            <div class="max-w-6xl mx-auto px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-16">
                    <!-- Left: Info -->
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary-500/10 text-primary-500 text-sm font-medium mb-6">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            {{ t('contact.badge') }}
                        </div>
                        <h1 class="text-4xl sm:text-5xl font-bold text-slate-900">
                            {{ t('contact.title') }}
                        </h1>
                        <p class="mt-6 text-xl text-slate-600 leading-relaxed">
                            {{ t('contact.subtitle') }}
                        </p>

                        <div class="mt-12 space-y-6">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 bg-primary-500/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-slate-900">{{ t('contact.info.email_title') }}</h3>
                                    <p class="text-slate-600">{{ marqueEmailContact }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 bg-primary-500/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-slate-900">{{ t('contact.info.response_title') }}</h3>
                                    <p class="text-slate-600">{{ t('contact.info.response_text') }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 bg-primary-500/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-slate-900">{{ t('contact.info.location_title') }}</h3>
                                    <p class="text-slate-600">{{ t('contact.info.location_text') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Form -->
                    <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-200">
                        <!-- Success message -->
                        <div v-if="success" class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-emerald-700 font-medium">{{ t('contact.success') }}</p>
                            </div>
                        </div>

                        <form @submit.prevent="submit" class="space-y-5">
                            <HoneypotFields
                                v-model:honeypot="form.homepage_url"
                                v-model:loadedAt="form.form_loaded_at"
                            />
                            <div>
                                <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">{{ t('contact.form.name') }}</label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    :placeholder="t('contact.form.name_placeholder')"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">{{ t('contact.form.email') }}</label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    :placeholder="t('contact.form.email_placeholder')"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                            </div>

                            <div>
                                <label for="subject" class="block text-sm font-medium text-slate-700 mb-1.5">{{ t('contact.form.subject') }}</label>
                                <input
                                    id="subject"
                                    v-model="form.subject"
                                    type="text"
                                    :placeholder="t('contact.form.subject_placeholder')"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                />
                                <p v-if="form.errors.subject" class="mt-1 text-sm text-red-600">{{ form.errors.subject }}</p>
                            </div>

                            <div>
                                <label for="message" class="block text-sm font-medium text-slate-700 mb-1.5">{{ t('contact.form.message') }}</label>
                                <textarea
                                    id="message"
                                    v-model="form.message"
                                    rows="5"
                                    :placeholder="t('contact.form.message_placeholder')"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors resize-none"
                                ></textarea>
                                <p v-if="form.errors.message" class="mt-1 text-sm text-red-600">{{ form.errors.message }}</p>
                            </div>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full py-3.5 bg-accent-rose hover:bg-pink-500 disabled:opacity-50 text-white font-semibold rounded-xl transition-colors"
                            >
                                <span v-if="form.processing">{{ t('contact.form.sending') }}</span>
                                <span v-else>{{ t('contact.form.submit') }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </MarketingLayout>
</template>
