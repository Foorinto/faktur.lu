<script setup>
import EmployeePortalLayout from '@/Layouts/EmployeePortalLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

defineProps({
    evaluations: { type: Array, default: () => [] },
});

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('fr-FR');
};

const getScoreClass = (score) => {
    if (score >= 4) return 'text-emerald-600 dark:text-emerald-400';
    if (score >= 3) return 'text-amber-600 dark:text-amber-400';
    return 'text-rose-600 dark:text-rose-400';
};
</script>

<template>
    <Head :title="t('employee_portal.my_evaluations')" />

    <EmployeePortalLayout>
        <div class="mb-6">
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ t('employee_portal.my_evaluations') }}</h1>
        </div>

        <div v-if="evaluations.length === 0" class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <svg class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">{{ t('hr.no_data') }}</p>
        </div>

        <div v-else class="space-y-3">
            <Link
                v-for="evaluation in evaluations"
                :key="evaluation.id"
                :href="route('employee-portal.evaluations.show', evaluation.id)"
                class="block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:border-primary-300 hover:shadow-md transition-all dark:border-slate-700 dark:bg-slate-800 dark:hover:border-primary-600"
            >
                <div class="flex items-start justify-between">
                    <div class="min-w-0 flex-1">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                            {{ evaluation.title || t('hr.evaluation') }}
                        </h3>
                        <div class="mt-1.5 flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                            <span class="flex items-center gap-1">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ formatDate(evaluation.date) }}
                            </span>
                            <span v-if="evaluation.evaluator" class="flex items-center gap-1">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                {{ evaluation.evaluator.first_name }} {{ evaluation.evaluator.last_name }}
                            </span>
                        </div>
                    </div>
                    <div v-if="evaluation.overall_score" class="ml-4 flex flex-col items-center">
                        <span class="text-2xl font-bold" :class="getScoreClass(evaluation.overall_score)">
                            {{ evaluation.overall_score }}
                        </span>
                        <span class="text-[10px] text-slate-400">/5</span>
                    </div>
                </div>
                <p v-if="evaluation.description" class="mt-2 text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                    {{ evaluation.description?.replace(/<[^>]*>/g, '').substring(0, 200) }}
                </p>
            </Link>
        </div>
    </EmployeePortalLayout>
</template>
