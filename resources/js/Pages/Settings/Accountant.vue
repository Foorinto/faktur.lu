<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    accountants: Array,
    pendingInvitations: Array,
    recentDownloads: Array,
    quota: { type: Object, default: () => ({ used: 0, max: null }) },
});

// max === null : aucun plafond sur ce plan, rien à afficher.
const quotaVisible = computed(() => props.quota.max !== null);
const quotaReached = computed(
    () => quotaVisible.value && props.quota.used >= props.quota.max,
);

const showInviteModal = ref(false);

const inviteForm = useForm({
    email: '',
    name: '',
});

const submitInvite = () => {
    inviteForm.post(route('settings.accountant.invite'), {
        preserveScroll: true,
        onSuccess: () => {
            showInviteModal.value = false;
            inviteForm.reset();
        },
    });
};

const resendInvitation = (invitationId) => {
    router.post(route('settings.accountant.resend', invitationId), {}, {
        preserveScroll: true,
    });
};

const cancelInvitation = (invitationId) => {
    if (confirm(t('confirm_cancel_invitation_action'))) {
        router.delete(route('settings.accountant.cancel', invitationId), {
            preserveScroll: true,
        });
    }
};

const revokeAccess = (accountantId, accountantName) => {
    if (confirm(t('confirm_revoke_access').replace(':name', accountantName))) {
        router.delete(route('settings.accountant.revoke', accountantId), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head :title="t('accountant_access_title')" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('settings_label') }}
            </h1>
        </template>

        <!-- Settings Navigation -->
        <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
            <nav class="flex space-x-4 sm:space-x-8 overflow-x-auto" aria-label="Settings tabs">
                <Link
                    :href="route('settings.email')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-gray-300 dark:text-slate-400 dark:hover:text-slate-300"
                >
                    Email
                </Link>
                <Link
                    :href="route('settings.email.provider')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-gray-300 dark:text-slate-400 dark:hover:text-slate-300"
                >
                    {{ t('email_provider_tab') }}
                </Link>
                <Link
                    :href="route('settings.accountant')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-accent-rose text-accent-rose dark:text-pink-400"
                >
                    {{ t('accountant_access_tab') }}
                </Link>
                <Link
                    :href="route('subscription.index')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-gray-300 dark:text-slate-400 dark:hover:text-slate-300"
                >
                    {{ t('subscription_tab') }}
                </Link>
            </nav>
        </div>

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('accountant_access_title') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ t('manage_export_clients') }}
                    </p>
                    <p
                        v-if="quotaVisible"
                        class="mt-1 text-sm"
                        :class="quotaReached ? 'font-medium text-pink-600 dark:text-pink-400' : 'text-slate-500 dark:text-slate-400'"
                    >
                        {{ t('accountant_quota', { used: quota.used, max: quota.max }) }}
                    </p>
                </div>
                <button
                    @click="showInviteModal = true"
                    :disabled="quotaReached"
                    class="inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-500 w-full sm:w-auto disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500 disabled:hover:bg-slate-300 dark:disabled:bg-slate-700 dark:disabled:text-slate-400"
                >
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    {{ t('invite_accountant_action') }}
                </button>
            </div>

            <!-- Active Accountants -->
            <div class="bg-white dark:bg-surface-card rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-200 dark:border-gray-700 dark:shadow-gray-900/50 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-medium text-slate-900 dark:text-white">{{ t('active_accountants') }}</h3>
                </div>
                <div v-if="accountants.length === 0" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                    {{ t('no_accountant_access') }}
                </div>
                <ul v-else class="divide-y divide-slate-200 dark:divide-slate-700">
                    <li v-for="accountant in accountants" :key="accountant.id" class="px-6 py-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">{{ accountant.name }}</p>
                                <p class="text-sm text-slate-500 dark:text-slate-400">{{ accountant.email }}</p>
                                <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-400">
                                    <span>{{ t('access_since') }} {{ accountant.granted_at }}</span>
                                    <span v-if="accountant.last_download">{{ t('last_download') }} {{ accountant.last_download }}</span>
                                </div>
                            </div>
                            <button
                                @click="revokeAccess(accountant.id, accountant.name)"
                                class="text-sm text-pink-600 hover:text-pink-800 dark:text-pink-400 dark:hover:text-pink-300"
                            >
                                {{ t('revoke_label') }}
                            </button>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Pending Invitations -->
            <div v-if="pendingInvitations.length > 0" class="bg-white dark:bg-surface-card rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-200 dark:border-gray-700 dark:shadow-gray-900/50 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-medium text-slate-900 dark:text-white">{{ t('invitations_pending') }}</h3>
                </div>
                <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                    <li v-for="invitation in pendingInvitations" :key="invitation.id" class="px-6 py-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">{{ invitation.email }}</p>
                                <p v-if="invitation.name" class="text-sm text-slate-500 dark:text-slate-400">{{ invitation.name }}</p>
                                <p class="text-xs text-slate-400 mt-1">
                                    {{ t('sent_on_at') }} {{ invitation.created_at }} - {{ t('expires_on_at') }} {{ invitation.expires_at }}
                                </p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button
                                    @click="resendInvitation(invitation.id)"
                                    class="text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400"
                                >
                                    {{ t('resend') }}
                                </button>
                                <button
                                    @click="cancelInvitation(invitation.id)"
                                    class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400"
                                >
                                    {{ t('cancel') }}
                                </button>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Recent Downloads -->
            <div v-if="recentDownloads.length > 0" class="bg-white dark:bg-surface-card rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-200 dark:border-gray-700 dark:shadow-gray-900/50 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-medium text-slate-900 dark:text-white">{{ t('recent_downloads_section') }}</h3>
                </div>
                <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                    <li v-for="download in recentDownloads" :key="download.id" class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-slate-900 dark:text-white">
                                    <span class="font-medium">{{ download.accountant_name }}</span>
                                    {{ t('has_downloaded') }}
                                    <span class="font-medium">{{ download.export_type_label }}</span>
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ t('period_colon') }} {{ download.period }}
                                </p>
                            </div>
                            <span class="text-xs text-slate-400">{{ download.downloaded_at }}</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Invite Modal -->
        <Teleport to="body">
            <div v-if="showInviteModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="showInviteModal = false"></div>

                    <div class="relative bg-white dark:bg-surface-card rounded-2xl shadow-xl max-w-md w-full p-6">
                        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">
                            {{ t('invite_accountant_action') }}
                        </h3>

                        <form @submit.prevent="submitInvite" class="space-y-4">
                            <div>
                                <label for="invite_email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    {{ t('accountant_email_label') }} *
                                </label>
                                <input
                                    id="invite_email"
                                    v-model="inviteForm.email"
                                    type="email"
                                    required
                                    class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    placeholder="comptable@fiduciaire.lu"
                                />
                                <p v-if="inviteForm.errors.email" class="mt-1 text-sm text-pink-600">{{ inviteForm.errors.email }}</p>
                            </div>

                            <div>
                                <label for="invite_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    {{ t('name_optional') }}
                                </label>
                                <input
                                    id="invite_name"
                                    v-model="inviteForm.name"
                                    type="text"
                                    class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    placeholder="Jean Dupont"
                                />
                            </div>

                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end pt-4">
                                <button
                                    type="button"
                                    @click="showInviteModal = false"
                                    class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 w-full sm:w-auto justify-center"
                                >
                                    {{ t('cancel') }}
                                </button>
                                <button
                                    type="submit"
                                    :disabled="inviteForm.processing"
                                    class="w-full sm:w-auto justify-center inline-flex items-center px-4 py-2 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-500 disabled:opacity-50"
                                >
                                    <svg v-if="inviteForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ t('send_invitation_action') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
