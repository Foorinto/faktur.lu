<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    clients: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    clientTypes: {
        type: Array,
        required: true,
    },
});

const search = ref(props.filters.search || '');
const typeFilter = ref(props.filters.type || '');

const updateFilters = debounce(() => {
    router.get(route('clients.index'), {
        search: search.value || undefined,
        type: typeFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, typeFilter], updateFilters);

const deleteClient = (client) => {
    if (confirm(t('confirm_delete_name', { name: client.name }))) {
        router.delete(route('clients.destroy', client.id));
    }
};

const getTypeLabel = (type) => {
    return type === 'b2b' ? t('company') : t('individual');
};

const getTypeBadgeClass = (type) => {
    return type === 'b2b'
        ? 'bg-sky-100 text-sky-700 dark:bg-sky-900 dark:text-sky-300'
        : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300';
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount);
};
</script>

<template>
    <Head :title="t('clients')" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ t('clients') }}
                </h1>
                <Link
                    :href="route('clients.create')"
                    class="inline-flex items-center rounded-xl bg-primary-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                >
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    {{ t('new_client') }}
                </Link>
            </div>
        </template>

        <!-- Filters -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 gap-4">
                <div class="relative flex-1 max-w-md">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="t('search_client')"
                        class="block w-full rounded-xl border-0 py-1.5 pl-10 pr-3 text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-primary-500 dark:bg-slate-800 dark:text-white dark:ring-slate-600 dark:placeholder:text-slate-500 sm:text-sm sm:leading-6"
                    />
                </div>

                <select
                    v-model="typeFilter"
                    class="rounded-xl border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-800 dark:text-white dark:ring-slate-600 sm:text-sm sm:leading-6"
                >
                    <option value="">{{ t('all_types') }}</option>
                    <option v-for="type in clientTypes" :key="type.value" :value="type.value">
                        {{ type.label }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Clients list -->
        <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 dark:text-white sm:pl-6">
                            {{ t('client') }}
                        </th>
                        <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white lg:table-cell">
                            {{ t('email') }}
                        </th>
                        <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white sm:table-cell">
                            {{ t('client_type') }}
                        </th>
                        <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white md:table-cell">
                            {{ t('country') }}
                        </th>
                        <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white lg:table-cell">
                            {{ t('vat') }}
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white">
                            {{ t('currency') }}
                        </th>
                        <th scope="col" class="hidden px-3 py-3.5 text-right text-sm font-semibold text-slate-900 dark:text-white xl:table-cell">
                            {{ t('total_invoiced') }}
                        </th>
                        <th scope="col" class="hidden px-3 py-3.5 text-right text-sm font-semibold text-slate-900 dark:text-white xl:table-cell">
                            {{ t('total_paid') }}
                        </th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">{{ t('actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-800">
                    <tr v-if="clients.data.length === 0">
                        <td colspan="9" class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <p class="mt-2">{{ t('no_clients') }}</p>
                            <Link
                                :href="route('clients.create')"
                                class="mt-4 inline-flex items-center text-primary-600 hover:text-primary-500 dark:text-primary-400"
                            >
                                <svg class="mr-1 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                                </svg>
                                {{ t('create_first_client') }}
                            </Link>
                        </td>
                    </tr>
                    <tr v-for="client in clients.data" :key="client.id" class="hover:bg-slate-50 dark:hover:bg-slate-700">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-6">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-100 dark:bg-primary-900/30">
                                        <span class="text-sm font-medium text-primary-600 dark:text-primary-400">
                                            {{ client.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <Link
                                        :href="route('clients.show', client.id)"
                                        class="font-medium text-slate-900 hover:text-primary-600 dark:text-white dark:hover:text-primary-400"
                                    >
                                        {{ client.name }}
                                    </Link>
                                    <div class="text-sm text-slate-500 dark:text-slate-400 lg:hidden">
                                        {{ client.email }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="hidden whitespace-nowrap px-3 py-4 text-sm text-slate-500 dark:text-slate-400 lg:table-cell">
                            {{ client.email }}
                        </td>
                        <td class="hidden whitespace-nowrap px-3 py-4 text-sm sm:table-cell">
                            <span
                                :class="getTypeBadgeClass(client.type)"
                                class="inline-flex items-center rounded-xl px-2.5 py-0.5 text-xs font-medium"
                            >
                                {{ getTypeLabel(client.type) }}
                            </span>
                        </td>
                        <td class="hidden whitespace-nowrap px-3 py-4 text-sm text-slate-500 dark:text-slate-400 md:table-cell">
                            {{ client.country_code }}
                        </td>
                        <td class="hidden whitespace-nowrap px-3 py-4 text-sm text-slate-500 dark:text-slate-400 lg:table-cell">
                            {{ client.vat_number || '-' }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500 dark:text-slate-400">
                            {{ client.currency }}
                        </td>
                        <td class="hidden whitespace-nowrap px-3 py-4 text-sm text-right text-slate-900 dark:text-white xl:table-cell">
                            {{ formatCurrency(client.total_invoiced || 0) }}
                        </td>
                        <td class="hidden whitespace-nowrap px-3 py-4 text-sm text-right xl:table-cell">
                            <span :class="client.total_paid > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400'">
                                {{ formatCurrency(client.total_paid || 0) }}
                            </span>
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            <div class="flex items-center justify-end space-x-1">
                                <Link
                                    :href="route('clients.edit', client.id)"
                                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-primary-600 dark:hover:bg-slate-700 dark:hover:text-primary-400"
                                    :title="t('edit')"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" />
                                    </svg>
                                </Link>
                                <button
                                    v-if="client.invoices_count === 0"
                                    @click="deleteClient(client)"
                                    class="rounded-lg p-2 text-slate-400 hover:bg-pink-50 hover:text-pink-600 dark:hover:bg-pink-900/20 dark:hover:text-pink-400"
                                    :title="t('delete')"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.519.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="clients.links && clients.links.length > 3" class="mt-6 flex items-center justify-between">
            <div class="text-sm text-slate-700 dark:text-slate-400">
                {{ t('showing_x_to_y_of_z', { from: clients.from, to: clients.to, total: clients.total, items: t('clients').toLowerCase() }) }}
            </div>
            <nav class="isolate inline-flex -space-x-px rounded-xl shadow-sm">
                <template v-for="(link, index) in clients.links" :key="index">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            link.active
                                ? 'z-10 bg-primary-500 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500'
                                : 'text-slate-900 ring-1 ring-inset ring-slate-200 hover:bg-slate-50 dark:text-slate-300 dark:ring-slate-600 dark:hover:bg-slate-700',
                            'relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20',
                            index === 0 ? 'rounded-l-xl' : '',
                            index === clients.links.length - 1 ? 'rounded-r-xl' : '',
                        ]"
                        v-html="link.label"
                        preserve-scroll
                    />
                    <span
                        v-else
                        :class="[
                            'relative inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-400 dark:text-slate-500',
                            index === 0 ? 'rounded-l-xl' : '',
                            index === clients.links.length - 1 ? 'rounded-r-xl' : '',
                        ]"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </div>
    </AppLayout>
</template>
