<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import EmailCodeStatus from '@/Components/EmailCodeStatus.vue';
import { useEmailCode } from '@/Composables/useEmailCode';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

// Le serveur dit si la 2FA est active : le champ du code n'apparaît que dans
// ce cas, et le code lui-même est vérifié par le même Reauthenticator que les
// formulaires en ligne (voir App\Auth\ConfirmPasswordWithTwoFactor).
const props = defineProps({
    requiresTwoFactor: { type: Boolean, default: false },
    // 'app' ou 'email' : en mode e-mail, le code part à l'arrivée sur la page.
    secondFactor: { type: String, default: null },
});

const emailMode = props.secondFactor === 'email';
const emailCode = useEmailCode();

onMounted(() => {
    if (emailMode) emailCode.send();
});

const form = useForm({
    password: '',
    two_factor_code: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="t('confirm_password_title')" />

        <div class="mb-4 text-sm text-slate-600 dark:text-slate-400">
            {{ t('confirm_password_message') }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="password" :value="t('password')" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div v-if="requiresTwoFactor" class="mt-4">
                <InputLabel for="two_factor_code" :value="emailMode ? t('email_otp.code') : t('authentication_code')" />
                <TextInput
                    id="two_factor_code"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    class="mt-1 block w-full font-mono"
                    v-model="form.two_factor_code"
                />
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ emailMode ? t('reauth_email_code_help') : t('reauth_code_help') }}</p>
                <EmailCodeStatus
                    v-if="emailMode"
                    class="mt-2"
                    :sending="emailCode.sending.value"
                    :sent-to="emailCode.sentTo.value"
                    :error="emailCode.error.value"
                    :countdown="emailCode.countdown.value"
                    @resend="emailCode.send"
                />
                <InputError class="mt-2" :message="form.errors.two_factor_code" />
            </div>

            <div class="mt-4 flex justify-end">
                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ t('confirm') }}
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
