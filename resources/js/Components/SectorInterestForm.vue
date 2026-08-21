<script setup>
import { computed, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import HoneypotFields from '@/Components/HoneypotFields.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    /** Clé du secteur — doit figurer dans User::BUSINESS_SECTORS. */
    sector: { type: String, required: true },
});

const page = usePage();

/**
 * Une seule question ouverte, et elle est choisie.
 *
 * « Qu'est-ce qui vous prend le plus de temps ? » dit quoi construire.
 * « Comment facturez-vous ? » dirait contre quoi se battre — utile au
 * commercial, mais ce n'est pas la question que cette page doit trancher.
 *
 * L'email est facultatif : quelqu'un peut décrire sa situation sans vouloir
 * être recontacté, et cette réponse compte autant pour la mesure.
 */
const form = useForm({
    sector: props.sector,
    email: '',
    message: '',
    wants_newsletter: false,
    homepage_url: '',
    form_loaded_at: Math.floor(Date.now() / 1000),
});

const envoye = ref(false);
const flash = computed(() => page.props.flash ?? {});

const submit = () => {
    form.post(route('sector-lead.store'), {
        preserveScroll: true,
        onSuccess: () => {
            envoye.value = true;
            form.reset('email', 'message', 'wants_newsletter');
        },
    });
};
</script>

<template>
    <section class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-surface-card sm:p-8">
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white">
            {{ t('sector_lead.title') }}
        </h2>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
            {{ t('sector_lead.intro') }}
        </p>

        <div
            v-if="envoye || flash.success"
            class="mt-4 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200"
        >
            {{ flash.success || t('sector_lead.thanks') }}
        </div>

        <form v-else class="mt-5 space-y-4" @submit.prevent="submit">
            <HoneypotFields
                v-model:honeypot="form.homepage_url"
                v-model:loaded-at="form.form_loaded_at"
            />

            <div>
                <InputLabel for="sector_message" :value="t('sector_lead.question')" />
                <textarea
                    id="sector_message"
                    v-model="form.message"
                    rows="3"
                    maxlength="2000"
                    class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    :placeholder="t('sector_lead.question_placeholder')"
                />
                <InputError class="mt-2" :message="form.errors.message" />
            </div>

            <div>
                <InputLabel for="sector_email" :value="t('sector_lead.email')" />
                <input
                    id="sector_email"
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                />
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ t('sector_lead.email_help') }}</p>
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <!-- Consentement distinct : répondre à une question n'est pas
                 s'abonner à une lettre d'information. -->
            <label class="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-400">
                <input
                    v-model="form.wants_newsletter"
                    type="checkbox"
                    class="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                />
                <span>{{ t('sector_lead.newsletter') }}</span>
            </label>

            <div
                v-if="flash.error"
                class="rounded-xl bg-rose-50 p-3 text-sm text-rose-800 dark:bg-rose-900/30 dark:text-rose-200"
            >
                {{ flash.error }}
            </div>

            <PrimaryButton :disabled="form.processing">
                {{ form.processing ? t('sending') : t('sector_lead.submit') }}
            </PrimaryButton>
        </form>
    </section>
</template>
