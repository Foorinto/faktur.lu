<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import HRNav from '@/Components/HRNav.vue';
import RichTextDisplay from '@/Components/RichTextDisplay.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
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

// Document upload
const fileInput = ref(null);
const docForm = useForm({
    file: null,
    name: '',
});

const selectFile = () => {
    fileInput.value?.click();
};

const onFileSelected = (e) => {
    if (e.target.files.length > 0) {
        docForm.file = e.target.files[0];
        if (!docForm.name) {
            docForm.name = e.target.files[0].name.replace(/\.[^/.]+$/, '');
        }
    }
};

const uploadDocument = () => {
    docForm.post(route('hr.employees.evaluations.upload-document', [props.employee.id, props.evaluation.id]), {
        preserveScroll: true,
        onSuccess: () => {
            docForm.reset();
            if (fileInput.value) fileInput.value.value = '';
        },
    });
};

const deleteDocument = (doc) => {
    if (confirm(t('confirm_delete_document'))) {
        router.delete(route('hr.employees.evaluations.delete-document', [props.employee.id, props.evaluation.id, doc.id]), {
            preserveScroll: true,
        });
    }
};

const formatFileSize = (bytes) => {
    if (!bytes) return '';
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' Mo';
    if (bytes >= 1024) return (bytes / 1024).toFixed(0) + ' Ko';
    return bytes + ' o';
};
</script>

<template>
    <Head :title="evaluation.title" />

    <AppLayout>
        <template #header>
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
        </template>
        <template #header-actions>
            <a
                :href="route('hr.employees.evaluations.pdf', [employee.id, evaluation.id])"
                class="inline-flex items-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-white dark:ring-slate-600 dark:hover:bg-gray-800"
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
        </template>

        <HRNav class="mb-6" />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <!-- Evaluation content -->
                <div class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-surface-card">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr.evaluation_description') }}</h2>
                    </div>
                    <div class="px-6 py-5">
                        <RichTextDisplay :content="evaluation.description" />
                    </div>
                </div>

                <!-- Documents -->
                <div class="rounded-2xl bg-white shadow dark:bg-surface-card">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('documents_label') }}</h2>
                        <span class="text-sm text-slate-500 dark:text-slate-400">{{ evaluation.documents?.length || 0 }} {{ t('documents_count_files') }}</span>
                    </div>

                    <!-- Upload form -->
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <form @submit.prevent="uploadDocument" class="flex flex-col sm:flex-row items-start sm:items-end gap-3">
                            <input ref="fileInput" type="file" accept=".pdf,.jpg,.jpeg,.png" class="hidden" @change="onFileSelected" />
                            <div class="flex-1 w-full sm:w-auto">
                                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">{{ t('document_name') }}</label>
                                <input
                                    v-model="docForm.name"
                                    type="text"
                                    :placeholder="t('document_name_placeholder')"
                                    class="w-full rounded-xl border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="selectFile"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-slate-300 dark:hover:bg-gray-700"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                    </svg>
                                    {{ docForm.file ? docForm.file.name : t('choose_file') }}
                                </button>
                                <button
                                    v-if="docForm.file"
                                    type="submit"
                                    :disabled="docForm.processing"
                                    class="inline-flex items-center rounded-xl bg-primary-500 px-3 py-2 text-sm font-medium text-white hover:bg-primary-600 disabled:opacity-50"
                                >
                                    {{ docForm.processing ? t('uploading_short') : t('add_short') }}
                                </button>
                            </div>
                        </form>
                        <p v-if="docForm.errors.file" class="mt-1 text-sm text-red-600">{{ docForm.errors.file }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ t('files_help_pdf') }}</p>
                    </div>

                    <!-- Documents list -->
                    <div v-if="evaluation.documents && evaluation.documents.length > 0">
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
                                <div class="flex items-center gap-2 flex-shrink-0 ml-4">
                                    <a
                                        :href="`/storage/${doc.file_path}`"
                                        target="_blank"
                                        class="text-slate-400 hover:text-primary-500 transition-colors"
                                        :title="t('download_short')"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </a>
                                    <button
                                        @click="deleteDocument(doc)"
                                        class="text-slate-400 hover:text-red-500 transition-colors"
                                        :title="t('delete_short')"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div v-else class="px-6 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                        {{ t('no_documents_attached') }}
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Metadata -->
                <div class="rounded-2xl bg-white shadow dark:bg-surface-card">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
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
