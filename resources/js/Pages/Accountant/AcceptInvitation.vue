<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    invitation: Object,
    accountantExists: Boolean,
});

const form = useForm({
    name: props.invitation.name || '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('accountant.accept.submit', props.invitation.token), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Accepter l'invitation" />

    <div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-slate-100 dark:bg-slate-900">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center">
                <svg class="h-12 w-12 text-primary-600" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14H6v-2h6v2zm4-4H6v-2h10v2zm0-4H6V7h10v2z"/>
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                Invitation à l'Espace Comptable
            </h2>
            <p class="mt-2 text-center text-sm text-slate-600 dark:text-slate-400">
                <strong>{{ invitation.user_name }}</strong> vous invite à accéder à ses exports comptables
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white dark:bg-slate-800 py-8 px-4 shadow-xl shadow-slate-200/50 sm:rounded-2xl sm:px-10 border border-slate-200 dark:border-slate-700">
                <div class="mb-6 p-4 bg-primary-50 dark:bg-primary-900/20 rounded-xl">
                    <p class="text-sm text-primary-700 dark:text-primary-300">
                        En acceptant cette invitation, vous pourrez télécharger :
                    </p>
                    <ul class="mt-2 text-sm text-primary-600 dark:text-primary-400 list-disc list-inside">
                        <li>Les exports FAIA (XML)</li>
                        <li>Les exports Excel des factures</li>
                        <li>Les archives PDF des factures</li>
                    </ul>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div v-if="accountantExists">
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                            Un compte existe déjà avec l'email <strong>{{ invitation.email }}</strong>.
                            Entrez votre mot de passe pour accepter cette invitation.
                        </p>
                        <div>
                            <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                Mot de passe
                            </label>
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                required
                                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                            />
                            <p v-if="form.errors.password" class="mt-1 text-sm text-pink-600">{{ form.errors.password }}</p>
                        </div>
                    </div>

                    <template v-else>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                            Créez votre compte comptable pour accéder aux exports.
                        </p>

                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                Email
                            </label>
                            <input
                                id="email"
                                type="email"
                                :value="invitation.email"
                                disabled
                                class="mt-1 block w-full rounded-xl border-slate-300 bg-slate-100 shadow-sm dark:border-slate-600 dark:bg-slate-600 dark:text-slate-300"
                            />
                        </div>

                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                Nom
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-pink-600">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                Mot de passe
                            </label>
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                required
                                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                            />
                            <p v-if="form.errors.password" class="mt-1 text-sm text-pink-600">{{ form.errors.password }}</p>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                Confirmer le mot de passe
                            </label>
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                required
                                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                            />
                        </div>
                    </template>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Accepter l'invitation
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
