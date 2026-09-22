<script setup>
import EmailCodeStatus from '@/Components/EmailCodeStatus.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { useEmailCode } from '@/Composables/useEmailCode';
import { useTranslations } from '@/Composables/useTranslations';

/**
 * Les champs de réauthentification à l'acte : le mot de passe, et le code du
 * second facteur si l'utilisateur en a un (application d'authentification,
 * ou code par e-mail : dans ce cas le code part quand les champs s'affichent,
 * et se renvoie d'ici).
 *
 * Le composant ne soumet rien : il écrit dans le formulaire Inertia qu'on lui
 * passe, sous les noms de champs que le serveur attend (voir
 * App\Auth\Reauthenticator). Le parent décide quand envoyer, et à quelle
 * route. Un seul rendu pour l'IBAN, le QR de paiement, l'email et le mot de
 * passe, sinon chaque formulaire finirait par demander la chose un peu
 * différemment.
 */
const props = defineProps({
    form: { type: Object, required: true },
    passwordField: { type: String, default: 'current_password' },
    codeField: { type: String, default: 'two_factor_code' },
    idPrefix: { type: String, default: 'reauth' },
    autofocus: { type: Boolean, default: false },
});

const emit = defineEmits(['submit']);

const { t } = useTranslations();
const page = usePage();
const emailCode = useEmailCode();

// 'app', 'email' ou null. Le champ du code n'apparaît que s'il y a un second
// facteur : le demander à qui n'en a pas serait une impasse.
const method = computed(() => page.props.auth?.user?.second_factor ?? null);
const requiresCode = computed(() => method.value !== null);
const emailMode = computed(() => method.value === 'email');

const passwordInput = ref(null);

onMounted(() => {
    if (props.autofocus) {
        passwordInput.value?.focus();
    }
    // Les champs ne sont montés qu'à l'ouverture du formulaire : c'est le
    // bon moment pour envoyer le code.
    if (emailMode.value) {
        emailCode.send();
    }
});

defineExpose({ focus: () => passwordInput.value?.focus() });
</script>

<template>
    <div class="space-y-4">
        <div>
            <InputLabel :for="`${idPrefix}_password`" :value="t('current_password')" />
            <TextInput
                :id="`${idPrefix}_password`"
                ref="passwordInput"
                v-model="form[passwordField]"
                type="password"
                class="mt-1 block w-full"
                autocomplete="current-password"
                @keyup.enter="emit('submit')"
            />
            <InputError :message="form.errors[passwordField]" class="mt-2" />
        </div>

        <div v-if="requiresCode">
            <InputLabel :for="`${idPrefix}_code`" :value="emailMode ? t('email_otp.code') : t('authentication_code')" />
            <TextInput
                :id="`${idPrefix}_code`"
                v-model="form[codeField]"
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                class="mt-1 block w-full font-mono"
                @keyup.enter="emit('submit')"
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
            <InputError :message="form.errors[codeField]" class="mt-2" />
        </div>
    </div>
</template>
