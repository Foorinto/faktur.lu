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
    if (!confirm(t('upgrade_confirm'))) return;
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

                <div class="rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                        <div class="text-sm text-amber-800 dark:text-amber-200">
                            <p class="font-medium mb-2">{{ t('upgrade_warning_title') }}</p>
                            <ul class="space-y-1 list-disc list-inside">
                                <li>{{ t('upgrade_warning_orgs', { count: orgsCount }) }}</li>
                                <li>{{ t('upgrade_warning_projects', { count: projectsCount }) }}</li>
                                <li>{{ t('upgrade_warning_keep_data') }}</li>
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
