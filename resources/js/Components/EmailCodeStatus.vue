<script setup>
import { useTranslations } from '@/Composables/useTranslations';

/**
 * Sous le champ du code par e-mail : à qui il est parti, l'erreur d'envoi
 * s'il y en a une, et le bouton pour le renvoyer, grisé le temps du compte
 * à rebours. Ne décide rien : tout vient de useEmailCode().
 */
defineProps({
    sending: { type: Boolean, default: false },
    sentTo: { type: String, default: '' },
    error: { type: String, default: '' },
    countdown: { type: Number, default: 0 },
});

const emit = defineEmits(['resend']);

const { t } = useTranslations();
</script>

<template>
    <div class="space-y-1 text-xs text-slate-500 dark:text-slate-400">
        <p v-if="sending">{{ t('email_otp.sending') }}</p>
        <p v-else-if="sentTo">{{ t('email_otp.sent_to', { email: sentTo }) }}</p>
        <p v-if="error" class="text-red-600 dark:text-red-400">{{ error }}</p>
        <p>
            {{ t('email_otp.check_spam') }}
            <button
                type="button"
                class="ml-1 underline hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-60 dark:hover:text-slate-100"
                :disabled="sending || countdown > 0"
                @click="emit('resend')"
            >
                {{ countdown > 0 ? t('email_otp.resend_in', { seconds: countdown }) : t('email_otp.resend') }}
            </button>
        </p>
    </div>
</template>
