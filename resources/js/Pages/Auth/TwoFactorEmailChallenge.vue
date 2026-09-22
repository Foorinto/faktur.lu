<script setup>
import { onBeforeUnmount, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useTranslations } from '@/Composables/useTranslations';

/**
 * Le défi par e-mail après le mot de passe : le code vient de partir à
 * l'adresse du compte, on le saisit ici. « Se souvenir de cet appareil »
 * évite de le redemander sur ce navigateur pendant trente jours.
 */
const props = defineProps({
    email: { type: String, default: '' },
    resendAfter: { type: Number, default: 0 },
    status: { type: String, default: null },
});

const { t } = useTranslations();

const form = useForm({
    code: '',
    remember_device: false,
});

const resendForm = useForm({});
const resent = ref(props.status === 'resent');
const countdown = ref(props.resendAfter);
let timer = null;

const startCountdown = (seconds) => {
    if (timer) clearInterval(timer);
    countdown.value = seconds;
    timer = setInterval(() => {
        countdown.value = Math.max(0, countdown.value - 1);
        if (countdown.value === 0) {
            clearInterval(timer);
            timer = null;
        }
    }, 1000);
};

if (countdown.value > 0) startCountdown(countdown.value);
onBeforeUnmount(() => timer && clearInterval(timer));

const submit = () => {
    form.post(route('two-factor.email.store'), {
        onFinish: () => form.reset('code'),
    });
};

const resend = () => {
    resendForm.post(route('two-factor.email.resend'), {
        preserveScroll: true,
        onSuccess: () => {
            resent.value = true;
            startCountdown(30);
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="t('email_otp.challenge_title')" />

        <div class="mb-4 text-sm text-slate-600 dark:text-slate-400">
            {{ t('email_otp.challenge_description', { email, minutes: 10 }) }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="code" :value="t('email_otp.code')" />
                <TextInput
                    id="code"
                    v-model="form.code"
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9 ]*"
                    maxlength="7"
                    class="mt-1 block w-full text-center text-2xl tracking-widest"
                    autofocus
                    autocomplete="one-time-code"
                    placeholder="000000"
                />
                <InputError class="mt-2" :message="form.errors.code" />
            </div>

            <label class="mt-4 flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                <input
                    v-model="form.remember_device"
                    type="checkbox"
                    name="remember_device"
                    class="rounded border-slate-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-800"
                >
                <span>{{ t('email_otp.remember_device', { days: 30 }) }}</span>
            </label>

            <div class="mt-4 space-y-1 text-xs text-slate-500 dark:text-slate-400">
                <p v-if="resent" class="text-emerald-700 dark:text-emerald-400">{{ t('email_otp.resent') }}</p>
                <p v-if="resendForm.errors.code" class="text-red-600 dark:text-red-400">{{ resendForm.errors.code }}</p>
                <p>
                    {{ t('email_otp.check_spam') }}
                    <button
                        type="button"
                        class="ml-1 underline hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-60 dark:hover:text-slate-100"
                        :disabled="resendForm.processing || countdown > 0"
                        @click="resend"
                    >
                        {{ countdown > 0 ? t('email_otp.resend_in', { seconds: countdown }) : t('email_otp.resend') }}
                    </button>
                </p>
            </div>

            <div class="mt-6 flex items-center justify-end">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    {{ t('verify') }}
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
