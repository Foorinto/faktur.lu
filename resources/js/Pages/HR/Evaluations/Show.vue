<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import HRNav from '@/Components/HRNav.vue';
import RichTextDisplay from '@/Components/RichTextDisplay.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    employee: { type: Object, required: true },
    evaluation: { type: Object, required: true },
});

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('fr-FR');
};

const deleteEvaluation = () => {
    if (confirm(t('hr.confirm_delete_evaluation'))) {
        router.delete(route('hr.employees.evaluations.destroy', [props.employee.id, props.evaluation.id]));
    }
};
</script>

<template>
    <Head :title="evaluation.title" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('hr.employees.evaluations', employee.id)"
                        class="text-slate-400 hover:text-slate-500 dark:text-slate-500 dark:hover:text-slate-400"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                        </svg>
                    </Link>
                    <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                        {{ evaluation.title }}
                    </h1>
                </div>
                <div class="flex items-center space-x-3">
                    <a
                        :href="route('hr.employees.evaluations.pdf', [employee.id, evaluation.id])"
                        class="inline-flex items-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-slate-50 dark:bg-slate-700 dark:text-white dark:ring-slate-600 dark:hover:bg-slate-600"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z" />
                            <path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" />
                        </svg>
                        {{ t('hr.export_pdf') }}
                    </a>
                    <button
                        @click="deleteEvaluation"
                        class="inline-flex items-center rounded-xl bg-pink-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-pink-500"
                    >
                        {{ t('delete') }}
                    </button>
                </div>
            </div>
        </template>

        <HRNav class="mb-6" />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <!-- Evaluation content -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr.evaluation_description') }}</h2>
                    </div>
                    <div class="px-6 py-5">
                        <RichTextDisplay :content="evaluation.description" />
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Metadata -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr.evaluation_details') }}</h2>
                    </div>
                    <dl class="divide-y divide-slate-200 dark:divide-slate-700">
                        <div class="px-6 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.evaluated_employee') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">
                                <Link
                                    :href="route('hr.employees.show', employee.id)"
                                    class="text-primary-600 hover:text-primary-500 dark:text-primary-400"
                                >
                                    {{ employee.full_name }}
                                </Link>
                            </dd>
                        </div>
                        <div v-if="employee.department" class="px-6 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.department') }}</dt>
                            <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                                <span
                                    class="inline-flex items-center rounded-xl px-2.5 py-0.5 text-xs font-medium"
                                    :style="{ backgroundColor: employee.department.color + '20', color: employee.department.color }"
                                >
                                    {{ employee.department.name }}
                                </span>
                            </dd>
                        </div>
                        <div v-if="evaluation.evaluator" class="px-6 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.evaluator') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">
                                {{ evaluation.evaluator.first_name }} {{ evaluation.evaluator.last_name }}
                            </dd>
                        </div>
                        <div class="px-6 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.evaluation_date') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">
                                {{ formatDate(evaluation.date) }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
