<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    logs: Object,
    filters: Object,
    categories: Array,
});

const localFilters = ref({
    category: props.filters.category || '',
    from: props.filters.from || '',
    to: props.filters.to || '',
    status: props.filters.status || '',
    search: props.filters.search || '',
});

const selectedLog = ref(null);
const showModal = ref(false);

const applyFilters = () => {
    router.get(route('audit-logs.index'), {
        ...localFilters.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    localFilters.value = {
        category: '',
        from: '',
        to: '',
        status: '',
        search: '',
    };
    applyFilters();
};

const viewDetail = (log) => {
    selectedLog.value = log;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedLog.value = null;
};

const exportCsv = () => {
    window.location.href = route('audit-logs.export', localFilters.value);
};

const getStatusBadgeClass = (status) => {
    return status === 'success'
        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
        : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
};

// Debounced search
let searchTimeout;
watch(() => localFilters.value.search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 500);
});
</script>

<template>
    <Head :title="t('audit_logs')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-slate-800 dark:text-slate-200">
                    {{ t('audit_logs') }}
                </h2>
                <button
                    @click="exportCsv"
                    class="inline-flex items-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:bg-slate-700 dark:text-slate-200 dark:ring-slate-600 dark:hover:bg-slate-600"
                >
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    {{ t('export_csv') }}
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="mb-6 rounded-2xl bg-white p-4 shadow dark:bg-slate-800">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                        <!-- Category filter -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('category') }}</label>
                            <select
                                v-model="localFilters.category"
                                @change="applyFilters"
                                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200"
                            >
                                <option value="">{{ t('all') }}</option>
                                <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                                    {{ cat.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Date from -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('from_date') }}</label>
                            <input
                                type="date"
                                v-model="localFilters.from"
                                @change="applyFilters"
                                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200"
                            />
                        </div>

                        <!-- Date to -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('to_date') }}</label>
                            <input
                                type="date"
                                v-model="localFilters.to"
                                @change="applyFilters"
                                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200"
                            />
                        </div>

                        <!-- Status filter -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('status') }}</label>
                            <select
                                v-model="localFilters.status"
                                @change="applyFilters"
                                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200"
                            >
                                <option value="">{{ t('all') }}</option>
                                <option value="success">{{ t('success') }}</option>
                                <option value="failed">{{ t('failed') }}</option>
                            </select>
                        </div>

                        <!-- Search -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('search') }}</label>
                            <input
                                type="text"
                                v-model="localFilters.search"
                                :placeholder="t('search_placeholder')"
                                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200"
                            />
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button
                            @click="resetFilters"
                            class="text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
                        >
                            {{ t('reset_filters') }}
                        </button>
                    </div>
                </div>

                <!-- Logs table -->
                <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-300">
                                    {{ t('date_time') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-300">
                                    {{ t('action') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-300">
                                    {{ t('resource') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-300">
                                    {{ t('status') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-300">
                                    IP
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-300">
                                    {{ t('actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-800">
                            <tr
                                v-for="log in logs.data"
                                :key="log.id"
                                class="hover:bg-slate-50 dark:hover:bg-slate-700"
                            >
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900 dark:text-slate-200">
                                    <div>{{ log.created_at }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ log.created_at_human }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900 dark:text-slate-200">
                                    <span class="mr-2">{{ log.action_emoji }}</span>
                                    {{ log.action_label }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                                    <span v-if="log.auditable_type">
                                        {{ log.auditable_type }} #{{ log.auditable_id }}
                                    </span>
                                    <span v-else>-</span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span
                                        :class="getStatusBadgeClass(log.status)"
                                        class="inline-flex rounded-full px-2 text-xs font-semibold leading-5"
                                    >
                                        {{ log.status === 'success' ? t('success') : t('failed') }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                                    {{ log.ip_address || '-' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                    <button
                                        @click="viewDetail(log)"
                                        class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                                    >
                                        {{ t('details') }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="logs.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                                    {{ t('no_audit_entries') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="logs.data.length > 0" class="border-t border-slate-200 px-4 py-3 dark:border-slate-700">
                        <Pagination :links="logs.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-slate-500 opacity-75 dark:bg-slate-900"></div>
                </div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div class="inline-block transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all dark:bg-slate-800 sm:my-8 sm:w-full sm:max-w-2xl sm:align-middle">
                    <div class="bg-white px-4 pb-4 pt-5 dark:bg-slate-800 sm:p-6 sm:pb-4">
                        <div class="flex items-start justify-between">
                            <h3 class="text-lg font-medium leading-6 text-slate-900 dark:text-slate-100">
                                {{ selectedLog?.action_emoji }} {{ selectedLog?.action_label }}
                            </h3>
                            <button @click="closeModal" class="text-slate-400 hover:text-slate-500">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="mt-4 space-y-4">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="font-medium text-slate-500 dark:text-slate-400">{{ t('date') }} :</span>
                                    <span class="ml-2 text-slate-900 dark:text-slate-100">{{ selectedLog?.created_at }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-slate-500 dark:text-slate-400">{{ t('status') }} :</span>
                                    <span
                                        :class="getStatusBadgeClass(selectedLog?.status)"
                                        class="ml-2 inline-flex rounded-full px-2 text-xs font-semibold leading-5"
                                    >
                                        {{ selectedLog?.status === 'success' ? t('success') : t('failed') }}
                                    </span>
                                </div>
                                <div v-if="selectedLog?.auditable_type">
                                    <span class="font-medium text-slate-500 dark:text-slate-400">{{ t('resource') }} :</span>
                                    <span class="ml-2 text-slate-900 dark:text-slate-100">
                                        {{ selectedLog.auditable_type }} #{{ selectedLog.auditable_id }}
                                    </span>
                                </div>
                                <div>
                                    <span class="font-medium text-slate-500 dark:text-slate-400">IP :</span>
                                    <span class="ml-2 text-slate-900 dark:text-slate-100">{{ selectedLog?.ip_address || '-' }}</span>
                                </div>
                            </div>

                            <!-- Changes diff -->
                            <div v-if="selectedLog?.changed_fields && Object.keys(selectedLog.changed_fields).length > 0">
                                <h4 class="mb-2 font-medium text-slate-900 dark:text-slate-100">{{ t('changes') }} :</h4>
                                <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-600">
                                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-600">
                                        <thead class="bg-slate-50 dark:bg-slate-700">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-300">{{ t('field') }}</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-300">{{ t('before') }}</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-300">{{ t('after') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 dark:divide-slate-600">
                                            <tr v-for="(change, field) in selectedLog.changed_fields" :key="field">
                                                <td class="px-4 py-2 text-sm font-medium text-slate-900 dark:text-slate-100">{{ field }}</td>
                                                <td class="px-4 py-2 text-sm text-red-600 dark:text-red-400">
                                                    {{ change.old ?? '-' }}
                                                </td>
                                                <td class="px-4 py-2 text-sm text-green-600 dark:text-green-400">
                                                    {{ change.new ?? '-' }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Metadata -->
                            <div v-if="selectedLog?.metadata && Object.keys(selectedLog.metadata).length > 0">
                                <h4 class="mb-2 font-medium text-slate-900 dark:text-slate-100">{{ t('metadata') }} :</h4>
                                <pre class="overflow-auto rounded-2xl bg-slate-100 p-3 text-xs dark:bg-slate-700">{{ JSON.stringify(selectedLog.metadata, null, 2) }}</pre>
                            </div>

                            <!-- User agent -->
                            <div v-if="selectedLog?.user_agent">
                                <h4 class="mb-2 font-medium text-slate-900 dark:text-slate-100">{{ t('browser') }} :</h4>
                                <p class="break-all text-sm text-slate-600 dark:text-slate-400">{{ selectedLog.user_agent }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-4 py-3 dark:bg-slate-700 sm:flex sm:flex-row-reverse sm:px-6">
                        <button
                            @click="closeModal"
                            class="mt-3 inline-flex w-full justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-base font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-600 sm:ml-3 sm:mt-0 sm:w-auto sm:text-sm"
                        >
                            {{ t('close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
