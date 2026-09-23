<script setup>
import { computed, ref, reactive, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import EmailCodeStatus from '@/Components/EmailCodeStatus.vue';
import { useEmailCode } from '@/Composables/useEmailCode';

const emit = defineEmits(['confirmed']);

defineProps({
    title: {
        type: String,
        default: 'Confirmer le mot de passe',
    },
    content: {
        type: String,
        default: 'Pour votre sécurité, veuillez confirmer votre mot de passe pour continuer.',
    },
    button: {
        type: String,
        default: 'Confirmer',
    },
});

const { t } = useTranslations();

const confirmingPassword = ref(false);

// La confirmation exige le code 2FA quand elle est active : c'est la même
// règle que la page de confirmation, vérifiée par le même Reauthenticator.
// Sans ce champ, un utilisateur avec 2FA ne pourrait plus la désactiver ni
// régénérer ses codes depuis le profil.
// 'app', 'email' ou null (voir auth.user.second_factor). En mode e-mail, le
// code part à l'ouverture de la modale et se renvoie depuis elle.
const method = computed(() => usePage().props.auth?.user?.second_factor ?? null);
const requiresCode = computed(() => method.value !== null);
const emailMode = computed(() => method.value === 'email');
const emailCode = useEmailCode();

const form = reactive({
    password: '',
    two_factor_code: '',
    error: '',
    codeError: '',
    processing: false,
});

const passwordInput = ref(null);

const startConfirmingPassword = () => {
    axios.get(route('password.confirmation')).then(response => {
        if (response.data.confirmed) {
            emit('confirmed');
        } else {
            confirmingPassword.value = true;
            if (emailMode.value) emailCode.send();

            setTimeout(() => passwordInput.value.focus(), 250);
        }
    });
};

const confirmPassword = () => {
    form.processing = true;

    axios.post(route('password.confirm'), {
        password: form.password,
        two_factor_code: form.two_factor_code,
    }).then(() => {
        form.processing = false;

        closeModal();
        nextTick().then(() => emit('confirmed'));

    }).catch(error => {
        form.processing = false;
        // L'erreur peut porter sur l'un ou l'autre champ : lire le mot de
        // passe seul plantait en silence quand c'était le code qui manquait.
        const errors = error.response?.data?.errors ?? {};
        form.error = errors.password?.[0] ?? '';
        form.codeError = errors.two_factor_code?.[0] ?? '';
        passwordInput.value.focus();
    });
};

const closeModal = () => {
    confirmingPassword.value = false;
    emailCode.reset();
    form.password = '';
    form.two_factor_code = '';
    form.error = '';
    form.codeError = '';
};
</script>

<template>
    <span>
        <span @click="startConfirmingPassword">
            <slot />
        </span>

        <DialogModal :show="confirmingPassword" @close="closeModal">
            <template #title>
                {{ title }}
            </template>

            <template #content>
                {{ content }}

                <div class="mt-4">
                    <TextInput
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-3/4"
                        placeholder="Mot de passe"
                        autocomplete="current-password"
                        @keyup.enter="confirmPassword"
                    />

                    <InputError :message="form.error" class="mt-2" />
                </div>

                <div v-if="requiresCode" class="mt-4">
                    <TextInput
                        v-model="form.two_factor_code"
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        class="mt-1 block w-3/4 font-mono"
                        :placeholder="emailMode ? t('email_otp.code') : t('authentication_code')"
                        @keyup.enter="confirmPassword"
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
                    <InputError :message="form.codeError" class="mt-2" />
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal">
                    Annuler
                </SecondaryButton>

                <PrimaryButton
                    class="ms-3"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    @click="confirmPassword"
                >
                    {{ button }}
                </PrimaryButton>
            </template>
        </DialogModal>
    </span>
</template>
