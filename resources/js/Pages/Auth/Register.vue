<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import HoneypotFields from '@/Components/HoneypotFields.vue';
import PasswordStrengthChecker from '@/Components/PasswordStrengthChecker.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';

const { t } = useTranslations();
const { localizedRoute } = useLocalizedRoute();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
    homepage_url: '',
    form_loaded_at: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="t('register')" />

        <form @submit.prevent="submit">
            <HoneypotFields
                v-model:honeypot="form.homepage_url"
                v-model:loadedAt="form.form_loaded_at"
            />
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

            <div class="mt-4">
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

            <div class="mt-4">
                <InputLabel for="password" :value="t('password')" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />

                <PasswordStrengthChecker :password="form.password" :min-length="12" />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel
                    for="password_confirmation"
                    :value="t('confirm_password')"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="mt-4">
                <label class="flex items-start">
                    <input
                        type="checkbox"
                        v-model="form.terms"
                        class="rounded-md border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-gray-700 dark:bg-surface-dark dark:focus:ring-primary-600 dark:focus:ring-offset-surface-dark mt-0.5"
                    />
                    <span class="ms-2 text-sm text-slate-600 dark:text-slate-400">
                        {{ t('accept_terms_prefix') }}
                        <Link :href="localizedRoute('legal.terms')" class="text-primary-600 hover:underline dark:text-primary-400" target="_blank">{{ t('terms_of_service') }}</Link>
                        {{ t('accept_terms_and') }}
                        <Link :href="localizedRoute('legal.privacy')" class="text-primary-600 hover:underline dark:text-primary-400" target="_blank">{{ t('privacy_policy') }}</Link>
                    </span>
                </label>
                <InputError class="mt-2" :message="form.errors.terms" />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    :href="route('login')"
                    class="rounded-xl text-sm text-slate-600 underline hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:text-slate-400 dark:hover:text-slate-100 dark:focus:ring-offset-surface-dark"
                >
                    {{ t('already_registered') }}
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ t('register') }}
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
