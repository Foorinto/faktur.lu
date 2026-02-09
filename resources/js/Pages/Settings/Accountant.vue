<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    accountants: Array,
    pendingInvitations: Array,
    recentDownloads: Array,
});

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
    if (confirm('Annuler cette invitation ?')) {
        router.delete(route('settings.accountant.cancel', invitationId), {
            preserveScroll: true,
        });
    }
};

const revokeAccess = (accountantId, accountantName) => {
    if (confirm(`Révoquer l'accès de ${accountantName} ? Cette action est irréversible.`)) {
        router.delete(route('settings.accountant.revoke', accountantId), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Accès Comptable" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                Paramètres
            </h1>
        </template>

        <!-- Settings Navigation -->
        <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
            <nav class="flex space-x-8" aria-label="Settings tabs">
                <Link
                    :href="route('settings.business.edit')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300"
                >
                    Entreprise
                </Link>
                <Link
                    :href="route('settings.email')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300"
                >
                    Email
                </Link>
                <Link
                    :href="route('settings.email.provider')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300"
                >
                    Fournisseur Email
                </Link>
                <Link
                    :href="route('settings.accountant')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-indigo-500 text-indigo-600 dark:text-indigo-400"
                >
                    Accès Comptable
                </Link>
            </nav>
        </div>

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Accès Comptable</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Invitez votre comptable à accéder à vos exports comptables.
                    </p>
                </div>
                <button
                    @click="showInviteModal = true"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-500"
                >
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Inviter un comptable
                </button>
            </div>

            <!-- Active Accountants -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-medium text-gray-900 dark:text-white">Comptables actifs</h3>
                </div>
                <div v-if="accountants.length === 0" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                    Aucun comptable n'a accès à vos données.
                </div>
                <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                    <li v-for="accountant in accountants" :key="accountant.id" class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ accountant.name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ accountant.email }}</p>
                                <div class="mt-1 flex items-center space-x-4 text-xs text-gray-400">
                                    <span>Accès depuis : {{ accountant.granted_at }}</span>
                                    <span v-if="accountant.last_download">Dernier téléchargement : {{ accountant.last_download }}</span>
                                </div>
                            </div>
                            <button
                                @click="revokeAccess(accountant.id, accountant.name)"
                                class="text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                            >
                                Révoquer
                            </button>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Pending Invitations -->
            <div v-if="pendingInvitations.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-medium text-gray-900 dark:text-white">Invitations en attente</h3>
                </div>
                <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                    <li v-for="invitation in pendingInvitations" :key="invitation.id" class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ invitation.email }}</p>
                                <p v-if="invitation.name" class="text-sm text-gray-500 dark:text-gray-400">{{ invitation.name }}</p>
                                <p class="text-xs text-gray-400 mt-1">
                                    Envoyée le {{ invitation.created_at }} - Expire le {{ invitation.expires_at }}
                                </p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button
                                    @click="resendInvitation(invitation.id)"
                                    class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                                >
                                    Renvoyer
                                </button>
                                <button
                                    @click="cancelInvitation(invitation.id)"
                                    class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400"
                                >
                                    Annuler
                                </button>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Recent Downloads -->
            <div v-if="recentDownloads.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-medium text-gray-900 dark:text-white">Téléchargements récents</h3>
                </div>
                <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                    <li v-for="download in recentDownloads" :key="download.id" class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-900 dark:text-white">
                                    <span class="font-medium">{{ download.accountant_name }}</span>
                                    a téléchargé
                                    <span class="font-medium">{{ download.export_type_label }}</span>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Période : {{ download.period }}
                                </p>
                            </div>
                            <span class="text-xs text-gray-400">{{ download.downloaded_at }}</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Invite Modal -->
        <Teleport to="body">
            <div v-if="showInviteModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showInviteModal = false"></div>

                    <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            Inviter un comptable
                        </h3>

                        <form @submit.prevent="submitInvite" class="space-y-4">
                            <div>
                                <label for="invite_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Email du comptable *
                                </label>
                                <input
                                    id="invite_email"
                                    v-model="inviteForm.email"
                                    type="email"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="comptable@fiduciaire.lu"
                                />
                                <p v-if="inviteForm.errors.email" class="mt-1 text-sm text-red-600">{{ inviteForm.errors.email }}</p>
                            </div>

                            <div>
                                <label for="invite_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Nom (optionnel)
                                </label>
                                <input
                                    id="invite_name"
                                    v-model="inviteForm.name"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Jean Dupont"
                                />
                            </div>

                            <div class="flex justify-end space-x-3 pt-4">
                                <button
                                    type="button"
                                    @click="showInviteModal = false"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    :disabled="inviteForm.processing"
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50"
                                >
                                    <svg v-if="inviteForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Envoyer l'invitation
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
