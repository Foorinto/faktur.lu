<script setup>
import EmployeePortalLayout from '@/Layouts/EmployeePortalLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    documents: { type: Array, default: () => [] },
});

const typeLabels = {
    contract: () => t('hr.doc_contract'),
    amendment: () => t('hr.doc_amendment'),
    payslip: () => t('hr.doc_payslip'),
    id_card: () => t('hr.doc_id_card'),
    certificate: () => t('hr.doc_certificate'),
    other: () => t('hr.doc_other'),
};

const groupedDocuments = computed(() => {
    const groups = {};
    props.documents.forEach((doc) => {
        const type = doc.type || 'other';
        if (!groups[type]) groups[type] = [];
        groups[type].push(doc);
    });
    return groups;
});

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('fr-FR');
};

const getTypeIcon = (type) => {
    const icons = {
        contract: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        amendment: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
        payslip: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
        id_card: 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0',
        certificate: 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
        other: 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z',
    };
    return icons[type] || icons.other;
};
</script>

<template>
    <Head :title="t('employee_portal.my_documents')" />

    <EmployeePortalLayout>
        <div class="mb-6">
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ t('employee_portal.my_documents') }}</h1>
        </div>

        <div v-if="documents.length === 0" class="rounded-2xl border border-gray-200 bg-white p-12 text-center shadow-sm dark:border-gray-700 dark:bg-surface-card">
            <svg class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">{{ t('hr.no_data') }}</p>
        </div>

        <div v-else class="space-y-6">
            <div v-for="(docs, type) in groupedDocuments" :key="type">
                <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-white mb-3">
                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getTypeIcon(type)" />
                    </svg>
                    {{ typeLabels[type] ? typeLabels[type]() : type }}
                    <span class="text-xs font-normal text-slate-400">({{ docs.length }})</span>
                </h2>
                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-surface-card overflow-hidden">
                    <div class="divide-y divide-slate-200 dark:divide-slate-700">
                        <div v-for="doc in docs" :key="doc.id" class="flex items-center justify-between px-4 py-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ doc.name || doc.original_name }}</p>
                                <div class="flex items-center gap-3 mt-0.5">
                                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ formatDate(doc.created_at) }}</span>
                                    <span v-if="doc.expiry_date" class="text-xs" :class="doc.is_expired ? 'text-rose-600 dark:text-rose-400' : 'text-slate-500 dark:text-slate-400'">
                                        {{ t('hr.expires') }} {{ formatDate(doc.expiry_date) }}
                                    </span>
                                </div>
                            </div>
                            <a
                                :href="`/storage/${doc.file_path}`"
                                target="_blank"
                                class="ml-4 flex-shrink-0 rounded-lg p-2 text-primary-600 hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-900/20"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z" />
                                    <path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </EmployeePortalLayout>
</template>
