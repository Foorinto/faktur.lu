<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    summary: Object,
    byClient: Array,
    filters: Object,
    clients: Array,
});

const clientFilter = ref(props.filters.client_id || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

const updateFilters = () => {
    router.get(route('time-entries.summary'), {
        client_id: clientFilter.value || undefined,
        start_date: startDate.value || undefined,
        end_date: endDate.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

watch([clientFilter, startDate, endDate], updateFilters);

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount);
};
</script>

<template>
    <Head :title="t('time_summary')" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ t('time_summary') }}
                </h1>
                <Link
                    :href="route('time-entries.index')"
                    class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600"
                >
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                    </svg>
                    {{ t('back') }}
                </Link>
            </div>
        </template>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-4">
            <select
                v-model="clientFilter"
                class="rounded-xl border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-primary-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600 sm:text-sm"
            >
                <option value="">{{ t('all_clients') }}</option>
                <option v-for="client in clients" :key="client.id" :value="client.id">
                    {{ client.name }}
                </option>
            </select>

            <input
                v-model="startDate"
                type="date"
                class="rounded-xl border-0 py-1.5 px-3 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-primary-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600 sm:text-sm"
                :placeholder="t('start_date')"
            />

            <input
                v-model="endDate"
                type="date"
                class="rounded-xl border-0 py-1.5 px-3 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-primary-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600 sm:text-sm"
                :placeholder="t('end_date')"
            />
        </div>

        <!-- Global Summary -->
        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800 px-4 py-5">
                <dt class="truncate text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('total_time') }}</dt>
                <dd class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white">
                    {{ summary.total_formatted }}
                </dd>
                <dd class="text-sm text-slate-500 dark:text-slate-400">
                    {{ summary.total_hours }} {{ t('hours') }}
                </dd>
            </div>
            <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800 px-4 py-5">
                <dt class="truncate text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('unbilled_time') }}</dt>
                <dd class="mt-1 text-2xl font-semibold text-amber-600 dark:text-amber-400">
                    {{ summary.unbilled_formatted }}
                </dd>
                <dd class="text-sm text-slate-500 dark:text-slate-400">
                    {{ summary.unbilled_hours }} {{ t('hours') }}
                </dd>
            </div>
            <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800 px-4 py-5">
                <dt class="truncate text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('billed_time') }}</dt>
                <dd class="mt-1 text-2xl font-semibold text-green-600 dark:text-green-400">
                    {{ summary.billed_formatted }}
                </dd>
                <dd class="text-sm text-slate-500 dark:text-slate-400">
                    {{ summary.billed_hours }} {{ t('hours') }}
                </dd>
            </div>
            <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800 px-4 py-5">
                <dt class="truncate text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('entries_count') }}</dt>
                <dd class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white">
                    {{ summary.count }}
                </dd>
            </div>
        </div>

        <!-- By Client -->
        <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ t('by_client') }}</h3>
            </div>
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                        <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 dark:text-white sm:pl-6">
                            {{ t('client') }}
                        </th>
                        <th class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900 dark:text-white">
                            {{ t('total') }}
                        </th>
                        <th class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900 dark:text-white">
                            {{ t('unbilled') }}
                        </th>
                        <th class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">{{ t('actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-800">
                    <tr v-if="byClient.length === 0">
                        <td colspan="4" class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                            {{ t('no_data_available') }}
                        </td>
                    </tr>
                    <tr v-for="item in byClient" :key="item.client_id" class="hover:bg-slate-50 dark:hover:bg-slate-700">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-6">
                            <div class="font-medium text-slate-900 dark:text-white">
                                {{ item.client_name }}
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-right text-sm font-mono font-medium text-slate-900 dark:text-white">
                            {{ item.total_formatted }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-right text-sm">
                            <span
                                :class="[
                                    'font-mono font-medium',
                                    item.unbilled_seconds > 0
                                        ? 'text-amber-600 dark:text-amber-400'
                                        : 'text-slate-500 dark:text-slate-400'
                                ]"
                            >
                                {{ item.unbilled_formatted }}
                            </span>
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            <Link
                                :href="route('time-entries.index', { client_id: item.client_id })"
                                class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                            >
                                {{ t('view_details') }}
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
