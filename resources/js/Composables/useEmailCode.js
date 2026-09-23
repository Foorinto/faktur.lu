import { onBeforeUnmount, ref } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

/**
 * Le code par e-mail d'une réauthentification à l'acte : le demander au
 * serveur, retenir à qui il est parti, et tenir le compte à rebours avant de
 * pouvoir le renvoyer.
 *
 * Partagé par les champs de réauthentification, la modale de confirmation et
 * la page de confirmation : les trois doivent se comporter pareil. Le code
 * est vérifié côté serveur par App\Auth\Reauthenticator, sous le même nom de
 * champ que le code de l'application (two_factor_code).
 */
export function useEmailCode() {
    const { t } = useTranslations();

    const sending = ref(false);
    const sentTo = ref('');
    const error = ref('');
    const countdown = ref(0);
    let timer = null;

    const stopTimer = () => {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    };

    const startCountdown = (seconds) => {
        stopTimer();
        countdown.value = seconds;
        timer = setInterval(() => {
            countdown.value = Math.max(0, countdown.value - 1);
            if (countdown.value === 0) stopTimer();
        }, 1000);
    };

    const send = () => {
        if (sending.value || countdown.value > 0) return Promise.resolve();

        sending.value = true;
        error.value = '';

        return axios.post(route('security.email-code.send'))
            .then((response) => {
                sentTo.value = response.data.email ?? '';
                startCountdown(response.data.resend_after ?? 30);
            })
            .catch((e) => {
                error.value = e.response?.data?.errors?.code?.[0]
                    ?? e.response?.data?.message
                    ?? t('email_otp.send_failed');
            })
            .finally(() => {
                sending.value = false;
            });
    };

    const reset = () => {
        stopTimer();
        sentTo.value = '';
        error.value = '';
        countdown.value = 0;
    };

    onBeforeUnmount(stopTimer);

    return { sending, sentTo, error, countdown, send, reset };
}
