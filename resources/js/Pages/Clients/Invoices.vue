<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    client: {
        type: Object,
        required: true,
    },
    invoices: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
    activeTab: {
        type: String,
        default: 'invoices',
    },
});

const deleteClient = () => {
    if (confirm(t('confirm_delete_name', { name: props.client.name }))) {
        router.delete(route('clients.destroy', props.client.id));
    }
};

const getTypeLabel = (type) => {
    return type === 'b2b' ? t('client_b2b') : t('client_b2c');
};

const getTypeBadgeClass = (type) => {
    return type === 'b2b'
        ? 'bg-sky-100 text-sky-700 dark:bg-sky-900 dark:text-sky-300'
        : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300';
};

const getStatusLabel = (status) => {
    return t(status);
};

const getStatusClass = (status) => {
    const classes = {
        draft: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
        sent: 'bg-sky-100 text-sky-700 dark:bg-sky-900 dark:text-sky-300',
        paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300',
        overdue: 'bg-pink-100 text-pink-700 dark:bg-pink-900 dark:text-pink-300',
        cancelled: 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400',
    };
    return classes[status] || classes.draft;
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR');
};
</script>

<template>
    <Head :title="`${client.name} - ${t('invoices')}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('clients.index')"
                        class="text-slate-400 hover:text-slate-500 dark:text-slate-500 dark:hover:text-slate-400"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                        </svg>
                    </Link>
                    <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                        {{ client.name }}
                    </h1>
                    <span
                        :class="getTypeBadgeClass(client.type)"
                        class="inline-flex items-center rounded-xl px-3 py-1 text-xs font-medium"
                    >
                        {{ getTypeLabel(client.type) }}
                    </span>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('clients.edit', client.id)"
                        class="inline-flex items-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-slate-50 dark:bg-slate-700 dark:text-white dark:ring-slate-600 dark:hover:bg-slate-600"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" />
                        </svg>
                        {{ t('edit') }}
                    </Link>
                    <button
                        v-if="client.invoices_count === 0"
                        @click="deleteClient"
                        class="inline-flex items-center rounded-xl bg-pink-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-pink-500"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.519.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" />
                        </svg>
                        {{ t('delete') }}
                    </button>
                </div>
            </div>
        </template>

        <!-- Tabs Navigation -->
        <div class="mb-6 border-b border-slate-200 dark:border-slate-700">
            <nav class="flex space-x-8" aria-label="Client tabs">
                <Link
                    :href="route('clients.show', client.id)"
                    :class="[
                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm',
                        activeTab === 'info'
                            ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'
                    ]"
                >
                    {{ t('tab_info') }}
                </Link>
                <Link
                    :href="route('clients.invoices', client.id)"
                    :class="[
                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm',
                        activeTab === 'invoices'
                            ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'
                    ]"
                >
                    {{ t('tab_invoices') }} ({{ client.invoices_count || 0 }})
                </Link>
            </nav>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
            <div class="overflow-hidden rounded-2xl bg-white shadow-lg border border-slate-200 dark:bg-slate-800 dark:border-slate-700 p-6">
                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('total_invoiced') }}</dt>
                <dd class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white">
                    {{ formatCurrency(stats.total_invoiced) }}
                </dd>
            </div>
            <div class="overflow-hidden rounded-2xl bg-white shadow-lg border border-slate-200 dark:bg-slate-800 dark:border-slate-700 p-6">
                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('total_paid') }}</dt>
                <dd class="mt-1 text-2xl font-semibold text-emerald-600 dark:text-emerald-400">
                    {{ formatCurrency(stats.total_paid) }}
                </dd>
            </div>
            <div class="overflow-hidden rounded-2xl bg-white shadow-lg border border-slate-200 dark:bg-slate-800 dark:border-slate-700 p-6">
                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('pending_amount') }}</dt>
                <dd class="mt-1 text-2xl font-semibold text-amber-600 dark:text-amber-400">
                    {{ formatCurrency(stats.pending) }}
                </dd>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-6 flex items-center gap-3">
            <Link
                :href="route('invoices.create', { client_id: client.id })"
                class="inline-flex items-center rounded-xl bg-primary-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600"
            >
                <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                </svg>
                {{ t('new_invoice') }}
            </Link>
            <Link
                :href="route('quotes.create', { client_id: client.id })"
                class="inline-flex items-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-slate-50 dark:bg-slate-700 dark:text-white dark:ring-slate-600 dark:hover:bg-slate-600"
            >
                <svg class="-ml-0.5 mr-1.5 h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                </svg>
                {{ t('new_quote') }}
            </Link>
        </div>

        <!-- Invoices Table -->
        <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
            <div v-if="invoices.data.length === 0" class="px-6 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-4 text-sm font-medium text-slate-900 dark:text-white">{{ t('no_invoices_client') }}</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {{ t('no_invoices_client_desc') }}
                </p>
                <div class="mt-6">
                    <Link
                        :href="route('invoices.create', { client_id: client.id })"
                        class="inline-flex items-center rounded-xl bg-primary-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                        </svg>
                        {{ t('create_first_invoice_client') }}
                    </Link>
                </div>
            </div>

            <table v-else class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th scope="col" class="py-3.5 pl-6 pr-3 text-left text-sm font-semibold text-slate-900 dark:text-white">
                            {{ t('number') }}
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white">
                            {{ t('date') }}
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white">
                            {{ t('type') }}
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900 dark:text-white">
                            {{ t('amount_ttc') }}
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white">
                            {{ t('status') }}
                        </th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-6">
                            <span class="sr-only">{{ t('actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <tr v-for="invoice in invoices.data" :key="invoice.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                        <td class="whitespace-nowrap py-4 pl-6 pr-3">
                            <Link
                                :href="route('invoices.show', invoice.id)"
                                class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
                            >
                                {{ invoice.number || `#${invoice.id}` }}
                            </Link>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600 dark:text-slate-400">
                            {{ formatDate(invoice.finalized_at || invoice.created_at) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600 dark:text-slate-400">
                            {{ invoice.type === 'credit_note' ? t('credit_note_short') : t('invoice') }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-right font-medium text-slate-900 dark:text-white">
                            {{ formatCurrency(invoice.total_ttc) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4">
                            <span
                                :class="getStatusClass(invoice.status)"
                                class="inline-flex items-center rounded-xl px-2.5 py-0.5 text-xs font-medium"
                            >
                                {{ getStatusLabel(invoice.status) }}
                            </span>
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-6 text-right text-sm">
                            <Link
                                :href="route('invoices.show', invoice.id)"
                                class="text-primary-600 hover:text-primary-500 dark:text-primary-400 font-medium"
                            >
                                {{ t('view') }}
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="invoices.data.length > 0 && invoices.last_page > 1" class="border-t border-slate-200 dark:border-slate-700 px-6 py-4">
                <Pagination :links="invoices.links" />
            </div>
        </div>
    </AppLayout>
</template>
