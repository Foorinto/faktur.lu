<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useAvatarColor } from '@/Composables/useAvatarColor';

const { t } = useTranslations();
const { getAvatarClasses } = useAvatarColor();

const props = defineProps({
    organization: Object,
    members: Array,
    pendingInvitations: Array,
    projects: Array,
});

const showInviteModal = ref(false);
const showCreateForm = ref(!props.organization);

const createForm = useForm({
    name: '',
});

const updateForm = useForm({
    name: props.organization?.name || '',
});

const inviteForm = useForm({
    email: '',
    name: '',
});

const submitCreate = () => {
    createForm.post(route('settings.organization.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateForm.value = false;
        },
    });
};

const submitUpdate = () => {
    updateForm.put(route('settings.organization.update'), {
        preserveScroll: true,
    });
};

const submitInvite = () => {
    inviteForm.post(route('settings.organization.invite'), {
        preserveScroll: true,
        onSuccess: () => {
            showInviteModal.value = false;
            inviteForm.reset();
        },
    });
};

const resendInvitation = (invitationId) => {
    router.post(route('settings.organization.invitation.resend', invitationId), {}, {
        preserveScroll: true,
    });
};

const cancelInvitation = (invitationId) => {
    if (confirm(t('confirm_cancel_invitation') || 'Annuler cette invitation ?')) {
        router.delete(route('settings.organization.invitation.cancel', invitationId), {
            preserveScroll: true,
        });
    }
};

const removeMember = (memberId, memberName) => {
    if (confirm((t('confirm_remove_member') || 'Retirer ce membre ?') + ` ${memberName}`)) {
        router.delete(route('settings.organization.member.remove', memberId), {
            preserveScroll: true,
        });
    }
};

const toggleProjectVisibility = (projectId) => {
    router.post(route('settings.organization.project.toggle', projectId), {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="t('organization_settings')" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('settings') }}
            </h1>
        </template>

        <!-- Settings Navigation -->
        <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
            <nav class="flex space-x-8 overflow-x-auto" aria-label="Settings tabs">
                <Link
                    :href="route('settings.business.edit')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-gray-300 dark:text-slate-400 dark:hover:text-slate-300"
                >
                    {{ t('business') }}
                </Link>
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
                    {{ t('email_provider') || 'Fournisseur Email' }}
                </Link>
                <Link
                    :href="route('settings.accountant')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-gray-300 dark:text-slate-400 dark:hover:text-slate-300"
                >
                    {{ t('accountant_access') }}
                </Link>
                <Link
                    :href="route('settings.organization.index')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-accent-rose text-accent-rose dark:text-pink-400"
                >
                    {{ t('organization') }}
                </Link>
                <Link
                    :href="route('subscription.index')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-gray-300 dark:text-slate-400 dark:hover:text-slate-300"
                >
                    {{ t('subscription') }}
                </Link>
            </nav>
        </div>

        <div class="space-y-6">
            <!-- Create Organization -->
            <div v-if="!organization" class="bg-white dark:bg-surface-card rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-200 dark:border-gray-700 dark:shadow-gray-900/50 p-6">
                <h2 class="text-lg font-medium text-slate-900 dark:text-white mb-2">{{ t('create_organization') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                    {{ t('create_organization_description') || 'Créez une organisation pour inviter des collaborateurs à travailler sur vos projets.' }}
                </p>

                <form @submit.prevent="submitCreate" class="max-w-md space-y-4">
                    <div>
                        <label for="org_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                            {{ t('organization_name') }} *
                        </label>
                        <input
                            id="org_name"
                            v-model="createForm.name"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            :placeholder="t('organization_name_placeholder') || 'Mon entreprise'"
                        />
                        <p v-if="createForm.errors.name" class="mt-1 text-sm text-pink-600">{{ createForm.errors.name }}</p>
                    </div>
                    <button
                        type="submit"
                        :disabled="createForm.processing"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-500 disabled:opacity-50"
                    >
                        {{ t('create_organization') }}
                    </button>
                </form>
            </div>

            <!-- Organization Settings -->
            <template v-if="organization">
                <!-- Org Name -->
                <div class="bg-white dark:bg-surface-card rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-200 dark:border-gray-700 dark:shadow-gray-900/50 p-6">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white mb-4">{{ t('organization') }}</h2>
                    <form @submit.prevent="submitUpdate" class="max-w-md flex items-end gap-3">
                        <div class="flex-1">
                            <label for="update_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                {{ t('organization_name') }}
                            </label>
                            <input
                                id="update_name"
                                v-model="updateForm.name"
                                type="text"
                                required
                                class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                            <p v-if="updateForm.errors.name" class="mt-1 text-sm text-pink-600">{{ updateForm.errors.name }}</p>
                        </div>
                        <button
                            type="submit"
                            :disabled="updateForm.processing"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-500 disabled:opacity-50"
                        >
                            {{ t('save') }}
                        </button>
                    </form>
                </div>

                <!-- Members -->
                <div class="bg-white dark:bg-surface-card rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-200 dark:border-gray-700 dark:shadow-gray-900/50 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-medium text-slate-900 dark:text-white">{{ t('collaborators') }}</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                {{ organization.collaborators_count }} / {{ organization.max_collaborators }} {{ t('collaborators').toLowerCase() }}
                            </p>
                        </div>
                        <button
                            v-if="organization.collaborators_count < organization.max_collaborators"
                            @click="showInviteModal = true"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-500"
                        >
                            <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            {{ t('invite_collaborator') }}
                        </button>
                    </div>

                    <div v-if="members.length === 0" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                        {{ t('no_members_yet') }}
                    </div>
                    <ul v-else class="divide-y divide-slate-200 dark:divide-slate-700">
                        <li v-for="member in members" :key="member.id" class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div :class="['flex h-10 w-10 items-center justify-center rounded-xl', getAvatarClasses(member.name)]">
                                        <span class="text-sm font-bold">
                                            {{ member.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ member.name }}</p>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ member.email }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <span class="text-xs text-slate-400">{{ member.joined_at }}</span>
                                    <button
                                        @click="removeMember(member.id, member.name)"
                                        class="text-sm text-pink-600 hover:text-pink-800 dark:text-pink-400 dark:hover:text-pink-300"
                                    >
                                        {{ t('remove') }}
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Pending Invitations -->
                <div v-if="pendingInvitations.length > 0" class="bg-white dark:bg-surface-card rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-200 dark:border-gray-700 dark:shadow-gray-900/50 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base font-medium text-slate-900 dark:text-white">{{ t('pending_invitations') }}</h3>
                    </div>
                    <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                        <li v-for="invitation in pendingInvitations" :key="invitation.id" class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ invitation.email }}</p>
                                    <p v-if="invitation.name" class="text-sm text-slate-500 dark:text-slate-400">{{ invitation.name }}</p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        {{ t('sent_on') || 'Envoyée le' }} {{ invitation.created_at }} - {{ t('expires_on') || 'Expire le' }} {{ invitation.expires_at }}
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

                <!-- Project Visibility -->
                <div class="bg-white dark:bg-surface-card rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-200 dark:border-gray-700 dark:shadow-gray-900/50 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base font-medium text-slate-900 dark:text-white">{{ t('project_visibility') || 'Visibilité des projets' }}</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            {{ t('project_visibility_description') || 'Choisissez quels projets sont visibles par vos collaborateurs.' }}
                        </p>
                    </div>
                    <div v-if="projects.length === 0" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                        {{ t('no_active_projects') || 'Aucun projet actif.' }}
                    </div>
                    <ul v-else class="divide-y divide-slate-200 dark:divide-slate-700">
                        <li v-for="project in projects" :key="project.id" class="px-6 py-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-900 dark:text-white">{{ project.title }}</span>
                                <button
                                    @click="toggleProjectVisibility(project.id)"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                                    :class="project.hidden_from_collaborators ? 'bg-slate-200 dark:bg-slate-600' : 'bg-primary-600'"
                                >
                                    <span
                                        :class="project.hidden_from_collaborators ? 'translate-x-0' : 'translate-x-5'"
                                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                    />
                                </button>
                            </div>
                        </li>
                    </ul>
                </div>
            </template>
        </div>

        <!-- Invite Modal -->
        <Teleport to="body">
            <div v-if="showInviteModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="showInviteModal = false"></div>

                    <div class="relative bg-white dark:bg-surface-card rounded-2xl shadow-xl max-w-md w-full p-6">
                        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">
                            {{ t('invite_collaborator') }}
                        </h3>

                        <form @submit.prevent="submitInvite" class="space-y-4">
                            <div>
                                <label for="invite_email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Email *
                                </label>
                                <input
                                    id="invite_email"
                                    v-model="inviteForm.email"
                                    type="email"
                                    required
                                    class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    placeholder="collaborateur@example.com"
                                />
                                <p v-if="inviteForm.errors.email" class="mt-1 text-sm text-pink-600">{{ inviteForm.errors.email }}</p>
                            </div>

                            <div>
                                <label for="invite_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    {{ t('name') }} ({{ t('optional') || 'optionnel' }})
                                </label>
                                <input
                                    id="invite_name"
                                    v-model="inviteForm.name"
                                    type="text"
                                    class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />
                            </div>

                            <div class="flex justify-end space-x-3 pt-4">
                                <button
                                    type="button"
                                    @click="showInviteModal = false"
                                    class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900"
                                >
                                    {{ t('cancel') }}
                                </button>
                                <button
                                    type="submit"
                                    :disabled="inviteForm.processing"
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-500 disabled:opacity-50"
                                >
                                    <svg v-if="inviteForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ t('send_invitation') || 'Envoyer l\'invitation' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
