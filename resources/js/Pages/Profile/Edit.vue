<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import TwoFactorAuthenticationForm from './Partials/TwoFactorAuthenticationForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    confirmsTwoFactorAuthentication: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <Head :title="t('profile')" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-slate-800 dark:text-slate-200"
            >
                {{ t('profile') }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div
                    class="bg-white p-4 shadow-xl shadow-slate-200/50 border border-slate-200 sm:rounded-2xl sm:p-8 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50"
                >
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div
                    class="bg-white p-4 shadow-xl shadow-slate-200/50 border border-slate-200 sm:rounded-2xl sm:p-8 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50"
                >
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div
                    class="bg-white p-4 shadow-xl shadow-slate-200/50 border border-slate-200 sm:rounded-2xl sm:p-8 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50"
                >
                    <TwoFactorAuthenticationForm
                        :requires-confirmation="confirmsTwoFactorAuthentication"
                    />
                </div>

                <div
                    class="bg-white p-4 shadow-xl shadow-slate-200/50 border border-slate-200 sm:rounded-2xl sm:p-8 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50"
                >
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
