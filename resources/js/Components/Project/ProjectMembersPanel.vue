<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import axios from 'axios';
import { useTranslations } from '@/Composables/useTranslations';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const { t } = useTranslations();

const props = defineProps({
    project: { type: Object, required: true },
});

const loading = ref(true);
const employees = ref([]);
const collaborators = ref([]);
const quota = ref({ used: 0, max: null, plan: '' });
const showInviteModal = ref(false);

const inviteForm = useForm({ email: '' });

const fetchMembers = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get(route('projects.members.index', props.project.id));
        employees.value = data.employees;
        collaborators.value = data.collaborators;
        quota.value = data.quota;
    } catch (e) {
        console.error('Failed to fetch members', e);
    } finally {
        loading.value = false;
    }
};

const toggleEmployee = (employee) => {
    router.patch(
        route('projects.members.employees.toggle', [props.project.id, employee.id]),
        {},
        {
            preserveScroll: true,
            onSuccess: () => fetchMembers(),
        }
    );
};

const isEmployeeActive = (emp) => emp.pivot?.active ?? false;

const submitInvite = () => {
    inviteForm.post(route('projects.members.collaborators.invite', props.project.id), {
        preserveScroll: true,
        onSuccess: () => {
            showInviteModal.value = false;
            inviteForm.reset();
            fetchMembers();
        },
    });
};

const removeCollaborator = (memberId) => {
    if (!confirm(t('project_members_remove_collab_confirm'))) return;
    router.delete(
        route('projects.members.collaborators.remove', [props.project.id, memberId]),
        {
            preserveScroll: true,
            onSuccess: () => fetchMembers(),
        }
    );
};

const canInvite = computed(() =>
    quota.value.max === null || quota.value.used < quota.value.max
);

onMounted(fetchMembers);
</script>

<template>
    <div class="space-y-6">
        <!-- Employees section -->
        <div class="rounded-2xl bg-white p-6 border border-gray-200 dark:bg-surface-card dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-slate-900 dark:text-white">
                    {{ t('project_members_employees') }}
                    <span class="ml-2 text-sm font-normal text-slate-500">
                        {{ employees.filter(e => isEmployeeActive(e)).length }}/{{ employees.length }}
                    </span>
                </h3>
            </div>

            <div v-if="loading" class="text-center py-8 text-slate-500">{{ t('loading') }}</div>
            <div v-else-if="employees.length === 0" class="text-center py-8 text-slate-500">
                {{ t('project_members_no_employees') }}
            </div>
            <ul v-else class="divide-y divide-gray-100 dark:divide-gray-700">
                <li
                    v-for="emp in employees"
                    :key="emp.id"
                    class="flex items-center justify-between py-3"
                >
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center text-xs font-medium text-slate-600 dark:bg-gray-700 dark:text-slate-300">
                            {{ emp.first_name?.[0] }}{{ emp.last_name?.[0] }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">
                                {{ emp.first_name }} {{ emp.last_name }}
                            </p>
                            <p class="text-xs" :class="isEmployeeActive(emp) ? 'text-emerald-600' : 'text-slate-400'">
                                {{ isEmployeeActive(emp) ? t('project_members_active') : t('project_members_inactive') }}
                            </p>
                        </div>
                    </div>
                    <button
                        @click="toggleEmployee(emp)"
                        :class="[
                            'inline-flex items-center rounded-xl px-3 py-1.5 text-sm font-medium shadow-sm ring-1 ring-inset transition-colors',
                            isEmployeeActive(emp)
                                ? 'bg-white text-pink-600 ring-gray-200 hover:bg-pink-50 dark:bg-gray-800 dark:ring-slate-600'
                                : 'bg-emerald-50 text-emerald-700 ring-emerald-200 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 dark:ring-emerald-700'
                        ]"
                    >
                        {{ isEmployeeActive(emp) ? t('project_members_deactivate') : t('project_members_activate') }}
                    </button>
                </li>
            </ul>
        </div>

        <!-- External collaborators section -->
        <div class="rounded-2xl bg-white p-6 border border-gray-200 dark:bg-surface-card dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-slate-900 dark:text-white">
                    {{ t('project_members_collaborators') }}
                    <span class="ml-2 text-sm font-normal text-slate-500">
                        {{ quota.used }}/{{ quota.max ?? '∞' }} — {{ quota.plan }}
                    </span>
                </h3>
                <button
                    @click="showInviteModal = true"
                    :disabled="!canInvite"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-primary-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ t('project_members_invite_collab') }}
                </button>
            </div>

            <p v-if="!canInvite" class="mb-3 text-sm text-amber-700 dark:text-amber-400">
                {{ t('project_members_quota_reached', { plan: quota.plan, max: quota.max }) }}
            </p>

            <ul v-if="collaborators.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
                <li
                    v-for="collab in collaborators"
                    :key="collab.id"
                    class="flex items-center justify-between py-3"
                >
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ collab.email }}</p>
                            <p class="text-xs text-slate-500">
                                <span v-if="collab.accepted_at">{{ t('project_members_accepted') }}</span>
                                <span v-else>{{ t('project_members_pending') }}</span>
                            </p>
                        </div>
                    </div>
                    <button
                        @click="removeCollaborator(collab.id)"
                        class="text-sm text-pink-600 hover:underline"
                    >
                        {{ t('project_members_remove') }}
                    </button>
                </li>
            </ul>
            <p v-else-if="!loading" class="text-sm text-slate-500 py-2">
                {{ t('project_members_no_collaborators') }}
            </p>
        </div>

        <!-- Invite modal -->
        <Teleport to="body">
            <div
                v-if="showInviteModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="showInviteModal = false"
            >
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-surface-card">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                        {{ t('project_members_invite_collab') }}
                    </h2>
                    <form @submit.prevent="submitInvite" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ t('email') }}
                            </label>
                            <input
                                v-model="inviteForm.email"
                                type="email"
                                required
                                placeholder="collaborateur@exemple.com"
                                class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                            <InputError :message="inviteForm.errors.email" class="mt-2" />
                        </div>
                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                @click="showInviteModal = false"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300"
                            >
                                {{ t('cancel') }}
                            </button>
                            <PrimaryButton :disabled="inviteForm.processing">
                                {{ inviteForm.processing ? t('loading') : t('project_members_send_invite') }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>
