<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
import ConfirmsPassword from '@/Components/ConfirmsPassword.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useTranslations } from '@/Composables/useTranslations';

/**
 * Le code par e-mail dans le profil : l'activer, le désactiver.
 *
 * Un compte exposé (IBAN et factures émises) garde au moins un second
 * facteur : si le code par e-mail est le seul, « Désactiver » disparaît et le
 * serveur refuse de toute façon (EmailSecondFactorController). Quand
 * l'application d'authentification est active, elle prend le dessus et le
 * code par e-mail n'est pas demandé.
 */
const props = defineProps({
    required: { type: Boolean, default: false },
});

const page = usePage();
const { t } = useTranslations();

const user = computed(() => page.props.auth?.user);
const emailEnabled = computed(() => Boolean(user.value?.email_otp_enabled_at));
const appEnabled = computed(() => Boolean(user.value?.two_factor_enabled));
const canDisable = computed(() => !props.required || appEnabled.value);
const processing = ref(false);

const enable = () => {
    processing.value = true;
    router.post(route('email-second-factor.enable'), {}, {
        preserveScroll: true,
        onFinish: () => { processing.value = false; },
    });
};

const disable = () => {
    processing.value = true;
    router.delete(route('email-second-factor.disable'), {
        preserveScroll: true,
        onFinish: () => { processing.value = false; },
    });
};
</script>

<template>
    <ActionSection>
        <template #title>
            {{ t('email_otp.title') }}
        </template>

        <template #description>
            {{ t('email_otp.description') }}
        </template>

        <template #content>
            <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100">
                {{ emailEnabled ? t('email_otp.enabled') : t('email_otp.not_enabled') }}
            </h3>

            <div class="mt-3 max-w-xl text-sm text-slate-600 dark:text-slate-400">
                <p v-if="emailEnabled && appEnabled">{{ t('email_otp.superseded') }}</p>
                <p v-else-if="emailEnabled && !canDisable">{{ t('two_factor_notice.required') }}</p>
                <p v-else-if="!emailEnabled">{{ t('email_otp.recommended_app') }}</p>
            </div>

            <InputError :message="page.props.errors?.email_otp" class="mt-2" />

            <div class="mt-5">
                <ConfirmsPassword v-if="!emailEnabled" @confirmed="enable">
                    <PrimaryButton type="button" :class="{ 'opacity-25': processing }" :disabled="processing">
                        {{ t('email_otp.enable') }}
                    </PrimaryButton>
                </ConfirmsPassword>

                <ConfirmsPassword v-else-if="canDisable" @confirmed="disable">
                    <DangerButton type="button" :class="{ 'opacity-25': processing }" :disabled="processing">
                        {{ t('email_otp.disable') }}
                    </DangerButton>
                </ConfirmsPassword>
            </div>
        </template>
    </ActionSection>
</template>
