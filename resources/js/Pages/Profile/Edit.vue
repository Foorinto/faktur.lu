<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import DocumentsSection from './Partials/DocumentsSection.vue';
import TwoFactorAuthenticationForm from './Partials/TwoFactorAuthenticationForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, nextTick } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    dpa: {
        type: Object,
        default: () => ({}),
    },
    confirmsTwoFactorAuthentication: {
        type: Boolean,
        default: true,
    },
});

onMounted(() => {
    if (window.location.hash !== '#language') return;

    nextTick(() => {
        const target = document.getElementById('language-section');
        if (!target) return;

        target.scrollIntoView({ behavior: 'smooth', block: 'center' });

        // Highlight: ring + soft background, then fade out
        target.classList.add(
            'ring-2',
            'ring-primary-500',
            'bg-primary-50',
            'dark:bg-primary-900/20',
        );

        setTimeout(() => {
            target.classList.remove(
                'ring-2',
                'ring-primary-500',
                'bg-primary-50',
                'dark:bg-primary-900/20',
            );
        }, 2500);
    });
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
                    class="bg-white p-4 shadow-xl shadow-gray-200/50 border border-gray-200 sm:rounded-2xl sm:p-8 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50"
                >
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div
                    class="bg-white p-4 shadow-xl shadow-gray-200/50 border border-gray-200 sm:rounded-2xl sm:p-8 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50"
                >
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div
                    class="bg-white p-4 shadow-xl shadow-gray-200/50 border border-gray-200 sm:rounded-2xl sm:p-8 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50"
                >
                    <TwoFactorAuthenticationForm
                        :requires-confirmation="confirmsTwoFactorAuthentication"
                    />
                </div>

                <div
                    class="bg-white p-4 shadow-xl shadow-gray-200/50 border border-gray-200 sm:rounded-2xl sm:p-8 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50"
                >
                    <DocumentsSection :dpa="dpa" />
                </div>

                <div
                    class="bg-white p-4 shadow-xl shadow-gray-200/50 border border-gray-200 sm:rounded-2xl sm:p-8 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50"
                >
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
