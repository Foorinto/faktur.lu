<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import SeoHead from '@/Components/SeoHead.vue';
import HoneypotFields from '@/Components/HoneypotFields.vue';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();
const page = usePage();

const props = defineProps({
    state: { type: String, required: true }, // form | completed | expired | invalid
    submitUrl: { type: String, default: null },
});

const success = computed(() => page.props.flash?.success);

const form = useForm({
    nps_score: null,
    comment: '',
    homepage_url: '',
    form_loaded_at: '',
});

const scores = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

const submit = () => {
    if (form.nps_score === null) return;
    form.post(props.submitUrl, { preserveScroll: true });
};
</script>

<template>
    <SeoHead :title="t('survey.page_title')" :noindex="true" />

    <MarketingLayout>
        <section class="py-16 sm:py-24">
            <div class="max-w-2xl mx-auto px-6 lg:px-8">

                <!-- Thank you (after successful submit) -->
                <div v-if="success" class="text-center">
                    <div class="w-16 h-16 mx-auto bg-emerald-100 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <h1 class="text-3xl font-bold text-slate-900">{{ t('survey.thank_you_title') }}</h1>
                    <p class="mt-4 text-lg text-slate-600">{{ t('survey.thank_you_text') }}</p>
                </div>

                <!-- Already completed -->
                <div v-else-if="state === 'completed'" class="text-center">
                    <h1 class="text-3xl font-bold text-slate-900">{{ t('survey.completed_title') }}</h1>
                    <p class="mt-4 text-lg text-slate-600">{{ t('survey.completed_text') }}</p>
                </div>

                <!-- Expired / invalid -->
                <div v-else-if="state === 'expired' || state === 'invalid'" class="text-center">
                    <h1 class="text-3xl font-bold text-slate-900">{{ t('survey.expired_title') }}</h1>
                    <p class="mt-4 text-lg text-slate-600">{{ t('survey.expired_text') }}</p>
                </div>

                <!-- Form -->
                <div v-else>
                    <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 text-center">{{ t('survey.heading') }}</h1>
                    <p class="mt-4 text-lg text-slate-600 text-center">{{ t('survey.subtitle') }}</p>

                    <form @submit.prevent="submit" class="mt-10 bg-white rounded-2xl p-8 shadow-sm border border-gray-200 space-y-8">
                        <HoneypotFields
                            v-model:honeypot="form.homepage_url"
                            v-model:loadedAt="form.form_loaded_at"
                        />

                        <!-- NPS 0-10 -->
                        <div>
                            <label class="block text-base font-semibold text-slate-900 mb-4">{{ t('survey.nps_question') }}</label>
                            <div class="grid grid-cols-11 gap-1.5">
                                <button
                                    v-for="score in scores"
                                    :key="score"
                                    type="button"
                                    @click="form.nps_score = score"
                                    :class="[
                                        'aspect-square rounded-lg text-sm font-semibold border transition-colors',
                                        form.nps_score === score
                                            ? 'bg-primary-500 text-white border-primary-500'
                                            : 'bg-white text-slate-700 border-gray-300 hover:border-primary-400'
                                    ]"
                                >{{ score }}</button>
                            </div>
                            <div class="flex justify-between mt-2 text-xs text-slate-500">
                                <span>{{ t('survey.nps_low') }}</span>
                                <span>{{ t('survey.nps_high') }}</span>
                            </div>
                            <p v-if="form.errors.nps_score" class="mt-2 text-sm text-red-600">{{ form.errors.nps_score }}</p>
                        </div>

                        <!-- Comment -->
                        <div>
                            <label for="comment" class="block text-base font-semibold text-slate-900 mb-2">{{ t('survey.comment_label') }}</label>
                            <textarea
                                id="comment"
                                v-model="form.comment"
                                rows="5"
                                :placeholder="t('survey.comment_placeholder')"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors resize-none"
                            ></textarea>
                            <p v-if="form.errors.comment" class="mt-1 text-sm text-red-600">{{ form.errors.comment }}</p>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing || form.nps_score === null"
                            class="w-full py-3.5 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition-colors"
                        >
                            <span v-if="form.processing">{{ t('survey.sending') }}</span>
                            <span v-else>{{ t('survey.submit') }}</span>
                        </button>
                    </form>
                </div>

            </div>
        </section>
    </MarketingLayout>
</template>
