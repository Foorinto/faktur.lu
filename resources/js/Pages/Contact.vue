<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import SeoHead from '@/Components/SeoHead.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';

const { t } = useTranslations();
const { localizedRoute } = useLocalizedRoute();
const page = usePage();

const form = useForm({
    name: '',
    email: '',
    subject: '',
    message: '',
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

    <div class="min-h-screen bg-slate-50">
        <!-- Navigation -->
        <nav class="bg-white border-b border-slate-200">
            <div class="max-w-6xl mx-auto px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <Link :href="localizedRoute('home')" class="flex items-center space-x-2.5">
                            <div class="bg-[#9b5de5] p-2 rounded-xl">
                                <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <span class="text-lg font-bold text-slate-900">faktur.lu</span>
                        </Link>
                    </div>
                    <div class="flex items-center space-x-4">
                        <Link :href="localizedRoute('features.index')" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            {{ t('landing.nav.features') }}
                        </Link>
                        <Link :href="localizedRoute('pricing')" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            {{ t('landing.nav.pricing') }}
                        </Link>
                        <Link :href="route('login')" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            {{ t('landing.nav.login') }}
                        </Link>
                        <Link :href="route('register')" class="bg-[#9b5de5] hover:bg-[#8b4ed5] text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors">
                            {{ t('landing.nav.create_account') }}
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

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
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#9b5de5]/10 text-[#9b5de5] text-sm font-medium mb-6">
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
                                <div class="w-10 h-10 bg-[#9b5de5]/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-[#9b5de5]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-slate-900">{{ t('contact.info.email_title') }}</h3>
                                    <p class="text-slate-600">support@faktur.lu</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 bg-[#9b5de5]/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-[#9b5de5]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-slate-900">{{ t('contact.info.response_title') }}</h3>
                                    <p class="text-slate-600">{{ t('contact.info.response_text') }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 bg-[#9b5de5]/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-[#9b5de5]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
                    <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-200">
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
                            <div>
                                <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">{{ t('contact.form.name') }}</label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    :placeholder="t('contact.form.name_placeholder')"
                                    class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#9b5de5] focus:border-[#9b5de5] transition-colors"
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
                                    class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#9b5de5] focus:border-[#9b5de5] transition-colors"
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
                                    class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#9b5de5] focus:border-[#9b5de5] transition-colors"
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
                                    class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#9b5de5] focus:border-[#9b5de5] transition-colors resize-none"
                                ></textarea>
                                <p v-if="form.errors.message" class="mt-1 text-sm text-red-600">{{ form.errors.message }}</p>
                            </div>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full py-3.5 bg-[#9b5de5] hover:bg-[#8b4ed5] disabled:opacity-50 text-white font-semibold rounded-xl transition-colors"
                            >
                                <span v-if="form.processing">{{ t('contact.form.sending') }}</span>
                                <span v-else>{{ t('contact.form.submit') }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-slate-900 py-12">
            <div class="max-w-6xl mx-auto px-6 lg:px-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="flex items-center">
                        <ApplicationLogo class="h-8 w-auto text-white" />
                    </div>
                    <div class="mt-4 md:mt-0 flex space-x-6">
                        <Link :href="localizedRoute('legal.mentions')" class="text-slate-400 hover:text-white text-sm">{{ t('landing.footer.legal_notice') }}</Link>
                        <Link :href="localizedRoute('legal.privacy')" class="text-slate-400 hover:text-white text-sm">{{ t('landing.footer.privacy') }}</Link>
                        <Link :href="localizedRoute('legal.terms')" class="text-slate-400 hover:text-white text-sm">{{ t('landing.footer.terms') }}</Link>
                    </div>
                </div>
                <div class="mt-8 text-center text-slate-500 text-sm">
                    &copy; {{ new Date().getFullYear() }} faktur.lu. {{ t('landing.footer.all_rights') }}
                </div>
            </div>
        </footer>
    </div>
</template>
