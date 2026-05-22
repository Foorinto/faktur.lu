<script setup>
import CollaboratorLayout from '@/Layouts/CollaboratorLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    projectsCount: { type: Number, default: 0 },
    orgsCount: { type: Number, default: 0 },
});

const form = useForm({});

const submit = () => {
    form.post(route('collaborator.upgrade.store'));
};
</script>

<template>
    <Head :title="t('upgrade_title')" />

    <CollaboratorLayout>
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="rounded-2xl bg-white dark:bg-surface-card border border-gray-200 dark:border-gray-700 p-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-900/40 flex items-center justify-center">
                        <svg class="h-5 w-5 text-primary-600 dark:text-primary-400" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 2a1 1 0 011 1v2.586l1.707-1.707a1 1 0 111.414 1.414L12.414 7H15a1 1 0 110 2h-2.586l1.707 1.707a1 1 0 11-1.414 1.414L11 10.414V13a1 1 0 11-2 0v-2.586l-1.707 1.707a1 1 0 11-1.414-1.414L7.586 9H5a1 1 0 110-2h2.586L5.879 5.293a1 1 0 011.414-1.414L9 5.586V3a1 1 0 011-1z" />
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ t('upgrade_title') }}</h1>
                </div>

                <p class="text-slate-600 dark:text-slate-400 mb-6">{{ t('upgrade_intro') }}</p>

                <div class="rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                        <div class="text-sm text-emerald-800 dark:text-emerald-200">
                            <p class="font-medium mb-2">{{ t('upgrade_keep_title') }}</p>
                            <ul class="space-y-1 list-disc list-inside">
                                <li v-if="orgsCount > 0">{{ t('upgrade_keep_orgs', { count: orgsCount }) }}</li>
                                <li v-if="projectsCount > 0">{{ t('upgrade_keep_projects', { count: projectsCount }) }}</li>
                                <li>{{ t('upgrade_keep_data') }}</li>
                                <li>{{ t('upgrade_keep_switcher_hint') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-3">{{ t('upgrade_what_you_get') }}</h2>
                <ul class="space-y-2 mb-6 text-sm text-slate-600 dark:text-slate-400">
                    <li class="flex items-start gap-2">
                        <svg class="h-4 w-4 text-emerald-500 flex-shrink-0 mt-1" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                        <span>{{ t('upgrade_feature_invoices') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="h-4 w-4 text-emerald-500 flex-shrink-0 mt-1" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                        <span>{{ t('upgrade_feature_clients') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="h-4 w-4 text-emerald-500 flex-shrink-0 mt-1" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                        <span>{{ t('upgrade_feature_free_trial') }}</span>
                    </li>
                </ul>

                <div class="flex items-center justify-between gap-3">
                    <a :href="route('collaborator.dashboard')" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400">
                        {{ t('cancel') }}
                    </a>
                    <button @click="submit" :disabled="form.processing" class="inline-flex items-center px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-500 text-white text-sm font-medium disabled:opacity-50">
                        {{ t('upgrade_cta') }}
                    </button>
                </div>
            </div>
        </div>
    </CollaboratorLayout>
</template>
