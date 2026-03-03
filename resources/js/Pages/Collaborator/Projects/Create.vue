<script setup>
import CollaboratorLayout from '@/Layouts/CollaboratorLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    statuses: Object,
    colors: Object,
});

const form = useForm({
    title: '',
    description: '',
    status: 'backlog',
    color: '#6366f1',
    due_date: '',
    budget_hours: '',
});

const submit = () => {
    form.post(route('collaborator.projects.store'));
};
</script>

<template>
    <Head :title="t('new_project') || 'Nouveau projet'" />

    <CollaboratorLayout>
        <div class="mb-6">
            <Link
                :href="route('collaborator.projects.index')"
                class="text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400 flex items-center"
            >
                <svg class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                {{ t('back_to_projects') || 'Retour aux projets' }}
            </Link>
        </div>

        <div class="max-w-2xl">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">{{ t('new_project') || 'Nouveau projet' }}</h1>

            <div class="bg-white dark:bg-surface-card rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-200 dark:border-gray-700 dark:shadow-gray-900/50 p-6">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label for="title" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                            {{ t('title') }} *
                        </label>
                        <input
                            id="title"
                            v-model="form.title"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                        <p v-if="form.errors.title" class="mt-1 text-sm text-pink-600">{{ form.errors.title }}</p>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                            {{ t('description') }}
                        </label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="status" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                {{ t('status') }}
                            </label>
                            <select
                                id="status"
                                v-model="form.status"
                                class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            >
                                <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>

                        <div>
                            <label for="color" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                {{ t('color') }}
                            </label>
                            <div class="mt-1 flex flex-wrap gap-2">
                                <button
                                    v-for="(label, hex) in colors"
                                    :key="hex"
                                    type="button"
                                    @click="form.color = hex"
                                    class="w-8 h-8 rounded-lg border-2 transition-transform"
                                    :class="form.color === hex ? 'border-slate-900 dark:border-white scale-110' : 'border-transparent'"
                                    :style="{ backgroundColor: hex }"
                                    :title="label"
                                ></button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="due_date" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                {{ t('due_date') }}
                            </label>
                            <input
                                id="due_date"
                                v-model="form.due_date"
                                type="date"
                                class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                        </div>

                        <div>
                            <label for="budget_hours" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                {{ t('budget_hours') || 'Budget (heures)' }}
                            </label>
                            <input
                                id="budget_hours"
                                v-model="form.budget_hours"
                                type="number"
                                min="0"
                                step="0.5"
                                class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end pt-4">
                        <Link
                            :href="route('collaborator.projects.index')"
                            class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 w-full sm:w-auto justify-center inline-flex items-center"
                        >
                            {{ t('cancel') }}
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full sm:w-auto justify-center inline-flex items-center px-4 py-2 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-500 disabled:opacity-50"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ t('create') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </CollaboratorLayout>
</template>
