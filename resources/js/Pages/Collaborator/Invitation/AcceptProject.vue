<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    token: String,
    email: String,
    isNewUser: Boolean,
    project: Object,
    orgOwnerName: String,
});

const form = useForm({
    name: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('collaborator.project-invitation.accept', props.token), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head :title="t('accept_invitation')" />

    <div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-slate-100 dark:bg-surface-dark">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center">
                <svg class="h-12 w-12 text-primary-600" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14H6v-2h6v2zm4-4H6v-2h10v2zm0-4H6V7h10v2z"/>
                </svg>
            </div>
            <h2 class="mt-6 text-center text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                {{ t('project_invitation_heading') }}
            </h2>
            <p class="mt-2 text-center text-sm text-slate-600 dark:text-slate-400">
                <strong v-if="orgOwnerName">{{ orgOwnerName }}</strong>
                <span>{{ t('project_invitation_invites_you_to') }}</span>
                <strong>{{ project.title }}</strong>
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white dark:bg-surface-card py-8 px-4 shadow-xl rounded-2xl sm:px-10 border border-gray-200 dark:border-gray-700">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('email_label') || 'Email' }}</label>
                        <input type="email" :value="email" disabled class="mt-1 block w-full rounded-xl border-gray-200 bg-slate-50 dark:bg-gray-800 dark:border-gray-700 dark:text-slate-300 text-sm" />
                    </div>

                    <div v-if="isNewUser">
                        <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('full_name') || 'Nom complet' }}</label>
                        <input id="name" v-model="form.name" type="text" required class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm" />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-pink-600">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                            {{ isNewUser ? t('password_choose') : t('password') }}
                        </label>
                        <input id="password" v-model="form.password" type="password" required autocomplete="new-password" class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm" />
                        <p v-if="form.errors.password" class="mt-1 text-sm text-pink-600">{{ form.errors.password }}</p>
                    </div>

                    <div v-if="isNewUser">
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('password_confirmation') || 'Confirmer le mot de passe' }}</label>
                        <input id="password_confirmation" v-model="form.password_confirmation" type="password" required autocomplete="new-password" class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm" />
                    </div>

                    <button type="submit" :disabled="form.processing" class="w-full inline-flex justify-center py-2 px-4 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-500 disabled:opacity-50">
                        {{ t('accept_invitation') }}
                    </button>

                    <p v-if="form.errors.token" class="text-sm text-pink-600">{{ form.errors.token }}</p>
                </form>
            </div>
        </div>
    </div>
</template>
