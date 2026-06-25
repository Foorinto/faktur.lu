<script setup>
import EmployeePortalLayout from '@/Layouts/EmployeePortalLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    employee: { type: Object, required: true },
    evaluation: { type: Object, required: true },
});

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR');
};

const getScoreClass = (score) => {
    if (score >= 4) return 'text-emerald-600 dark:text-emerald-400';
    if (score >= 3) return 'text-amber-600 dark:text-amber-400';
    return 'text-rose-600 dark:text-rose-400';
};
</script>

<template>
    <Head :title="evaluation.title || t('hr.evaluation')" />

    <EmployeePortalLayout>
        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
            <div class="flex items-center gap-3">
                <Link :href="route('employee-portal.evaluations.index')" class="rounded-lg p-1.5 text-slate-400 hover:bg-gray-50 dark:hover:bg-gray-800">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                    </svg>
                </Link>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ evaluation.title || t('hr.evaluation') }}</h1>
            </div>
            <a
                :href="route('employee-portal.evaluations.pdf', evaluation.id)"
                target="_blank"
                class="w-full sm:w-auto justify-center inline-flex items-center gap-1.5 rounded-xl bg-accent-rose px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-pink-500"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                PDF
            </a>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Main content -->
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-surface-card">
                    <div v-if="evaluation.description" class="prose prose-sm dark:prose-invert max-w-none" v-html="evaluation.description"></div>
                    <p v-else class="text-sm text-slate-500 dark:text-slate-400">{{ t('hr.no_data') }}</p>
                </div>

                <!-- Documents -->
                <div v-if="evaluation.documents && evaluation.documents.length > 0" class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-surface-card">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('documents_label') }} ({{ evaluation.documents.length }})</h2>
                    </div>
                    <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                        <li v-for="doc in evaluation.documents" :key="doc.id" class="px-6 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-800">
                            <div class="flex items-center gap-3 min-w-0">
                                <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ doc.name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ doc.original_name }}</p>
                                </div>
                            </div>
                            <a
                                :href="`/storage/${doc.file_path}`"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-primary-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-600 flex-shrink-0 ml-4"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                {{ t('download_short') }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-4">
                <!-- Score -->
                <div v-if="evaluation.overall_score" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-surface-card text-center">
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">{{ t('hr.overall_score') }}</p>
                    <p class="text-4xl font-bold" :class="getScoreClass(evaluation.overall_score)">
                        {{ evaluation.overall_score }}
                        <span class="text-lg font-normal text-slate-400">/5</span>
                    </p>
                </div>

                <!-- Details -->
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-surface-card">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">{{ t('hr.details') }}</h3>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">{{ t('hr.date') }}</dt>
                            <dd class="font-medium text-slate-900 dark:text-white">{{ formatDate(evaluation.date) }}</dd>
                        </div>
                        <div v-if="evaluation.evaluator">
                            <dt class="text-slate-500 dark:text-slate-400">{{ t('hr.evaluator') }}</dt>
                            <dd class="font-medium text-slate-900 dark:text-white">{{ evaluation.evaluator.first_name }} {{ evaluation.evaluator.last_name }}</dd>
                        </div>
                        <div v-if="employee.department">
                            <dt class="text-slate-500 dark:text-slate-400">{{ t('hr.department') }}</dt>
                            <dd>
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium" :style="{ backgroundColor: employee.department.color + '20', color: employee.department.color }">
                                    <span class="h-1.5 w-1.5 rounded-full" :style="{ backgroundColor: employee.department.color }"></span>
                                    {{ employee.department.name }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </EmployeePortalLayout>
</template>
