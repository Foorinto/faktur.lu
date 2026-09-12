<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();
const user = page.props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
    locale: user.locale ?? 'fr',
    business_sector: user.business_sector ?? '',
    current_password: '',
});

// Changer l'adresse e-mail exige le mot de passe courant (sécurité : une
// session ouverte ne doit pas pouvoir préparer une prise de contrôle). On le
// demande dans une modale dédiée, pour que l'erreur s'affiche ici et non dans
// la section « Mettre à jour le mot de passe » (qui a un champ du même nom).
const confirmingEmailChange = ref(false);
const passwordInput = ref(null);

const submit = () => {
    if (form.email !== user.email) {
        confirmingEmailChange.value = true;
        nextTick(() => passwordInput.value?.focus());

        return;
    }

    form.patch(route('profile.update'), { preserveScroll: true });
};

const submitWithPassword = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => closeEmailModal(),
        onError: () => passwordInput.value?.focus(),
    });
};

const closeEmailModal = () => {
    confirmingEmailChange.value = false;
    form.reset('current_password');
    form.clearErrors('current_password');
};

/**
 * Secteurs proposés, dans l'ordre de l'écran d'inscription.
 *
 * La liste est recopiée ici plutôt que transmise : sept clés stables, que le
 * test `BusinessSectorTest` confronte à celles du modèle. Les faire transiter
 * par les props de chaque page aurait coûté plus que ce que ça protège.
 */
const secteurs = ['construction', 'freelance', 'health', 'real_estate', 'retail', 'hospitality', 'other'];
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-slate-900 dark:text-slate-100">
                {{ t('profile_information') }}
            </h2>

            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                {{ t('update_profile_info') }}
            </p>
        </header>

        <form
            @submit.prevent="submit"
            class="mt-6 space-y-6"
        >
            <div>
                <InputLabel for="name" :value="t('name')" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" :value="t('email')" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div id="language-section" class="rounded-xl p-3 -m-3 transition-colors duration-700">
                <InputLabel for="locale" :value="t('interface_language')" />

                <select
                    id="locale"
                    v-model="form.locale"
                    class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
                    <option v-for="(label, code) in page.props.availableLocales" :key="code" :value="code">
                        {{ label }}
                    </option>
                </select>

                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {{ t('interface_language_help') }}
                </p>

                <InputError class="mt-2" :message="form.errors.locale" />
            </div>

            <div>
                <InputLabel for="business_sector" :value="t('business_sector')" />
                <select
                    id="business_sector"
                    v-model="form.business_sector"
                    class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
                    <option value="">{{ t('business_sector_none') }}</option>
                    <option v-for="s in secteurs" :key="s" :value="s">
                        {{ t('business_sectors.' + s + '.label') }}
                    </option>
                </select>
                <InputError class="mt-2" :message="form.errors.business_sector" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-slate-800 dark:text-slate-200">
                    {{ t('email_unverified') }}
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-xl text-sm text-slate-600 underline hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:text-slate-400 dark:hover:text-slate-100 dark:focus:ring-offset-surface-dark"
                    >
                        {{ t('resend_verification_email') }}
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-emerald-600 dark:text-emerald-400"
                >
                    {{ t('verification_link_sent') }}
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                <PrimaryButton :disabled="form.processing" class="w-full sm:w-auto justify-center">{{ t('save') }}</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-slate-600 dark:text-slate-400"
                    >
                        {{ t('saved') }}
                    </p>
                </Transition>
            </div>
        </form>

        <!-- Confirmation du mot de passe pour changer l'adresse e-mail (sécurité). -->
        <Modal :show="confirmingEmailChange" @close="closeEmailModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-slate-900 dark:text-slate-100">
                    {{ t('confirm_password_title') }}
                </h2>

                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                    {{ t('confirm_password_for_email') }}
                </p>

                <div class="mt-6">
                    <InputLabel for="current_password_email" :value="t('current_password')" class="sr-only" />

                    <TextInput
                        id="current_password_email"
                        ref="passwordInput"
                        v-model="form.current_password"
                        type="password"
                        class="mt-1 block w-3/4"
                        :placeholder="t('current_password')"
                        autocomplete="current-password"
                        @keyup.enter="submitWithPassword"
                    />

                    <InputError :message="form.errors.current_password" class="mt-2" />
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <SecondaryButton class="w-full sm:w-auto justify-center" @click="closeEmailModal">
                        {{ t('cancel') }}
                    </SecondaryButton>

                    <PrimaryButton
                        class="w-full sm:w-auto justify-center"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="submitWithPassword"
                    >
                        {{ t('save') }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
