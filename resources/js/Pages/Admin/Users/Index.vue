<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import { useTranslations } from '@/Composables/useTranslations';
import debounce from 'lodash/debounce';

const { t } = useTranslations();

const props = defineProps({
    users: Object,
    filters: Object,
});

const search = ref(props.filters.search);
const status = ref(props.filters.status);

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-LU', {
        style: 'currency',
        currency: 'EUR',
    }).format(value || 0);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-LU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const applyFilters = debounce(() => {
    router.get(route('admin.users.index'), {
        search: search.value,
        status: status.value,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, status], () => {
    applyFilters();
});

const sortBy = (field) => {
    const direction = props.filters.sort === field && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get(route('admin.users.index'), {
        search: search.value,
        status: status.value,
        sort: field,
        direction: direction,
    }, {
        preserveState: true,
        replace: true,
    });
};

const getSortIcon = (field) => {
    if (props.filters.sort !== field) return '↕';
    return props.filters.direction === 'asc' ? '↑' : '↓';
};
</script>

<template>
    <Head :title="t('admin_users_head_title')" />

    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-white">{{ t('admin_users_title') }}</h1>
        </template>

        <!-- Filters -->
        <div class="mb-6 rounded-xl bg-slate-800 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="flex-1">
                    <TextInput
                        v-model="search"
                        type="search"
                        :placeholder="t('admin_users_search_placeholder')"
                        class="w-full border-slate-600 bg-slate-700 text-white placeholder-slate-400 focus:border-purple-500 focus:ring-purple-500"
                    />
                </div>
                <div class="sm:w-48">
                    <select
                        v-model="status"
                        class="w-full rounded-md border-slate-600 bg-slate-700 text-white focus:border-purple-500 focus:ring-purple-500"
                    >
                        <option value="">{{ t('admin_users_all_statuses') }}</option>
                        <option value="verified">{{ t('admin_users_status_verified') }}</option>
                        <option value="unverified">{{ t('admin_users_status_unverified') }}</option>
                        <option value="with_2fa">{{ t('admin_users_status_with_2fa') }}</option>
                        <option value="inactive">{{ t('admin_users_status_inactive') }}</option>
                        <option value="deleted">{{ t('admin_users_status_deleted') }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Users table -->
        <div class="overflow-hidden rounded-xl bg-slate-800">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-700">
                    <thead class="bg-slate-700/50">
                        <tr>
                            <th
                                class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-300 hover:text-white"
                                @click="sortBy('name')"
                            >
                                {{ t('admin_users_th_user') }} {{ getSortIcon('name') }}
                            </th>
                            <th
                                class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-300 hover:text-white"
                                @click="sortBy('email')"
                            >
                                {{ t('admin_email') }} {{ getSortIcon('email') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-300">
                                {{ t('admin_status') }}
                            </th>
                            <th
                                class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-300 hover:text-white"
                                @click="sortBy('invoices_count')"
                            >
                                {{ t('admin_users_th_invoices') }} {{ getSortIcon('invoices_count') }}
                            </th>
                            <th
                                class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-300 hover:text-white"
                                @click="sortBy('invoices_sum_total_ttc')"
                            >
                                {{ t('admin_users_th_revenue') }} {{ getSortIcon('invoices_sum_total_ttc') }}
                            </th>
                            <th
                                class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-300 hover:text-white"
                                @click="sortBy('created_at')"
                            >
                                {{ t('admin_users_th_registered_at') }} {{ getSortIcon('created_at') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-300">
                                {{ t('admin_actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="hover:bg-slate-700/50"
                        >
                            <td class="whitespace-nowrap px-6 py-4">
                                <div>
                                    <div class="font-medium text-white">{{ user.name }}</div>
                                    <div v-if="user.company_name" class="text-sm text-slate-400">
                                        {{ user.company_name }}
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-slate-300">
                                {{ user.email }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-if="user.email_verified_at"
                                        class="inline-flex rounded-full bg-green-500/20 px-2 py-1 text-xs font-medium text-green-400"
                                    >
                                        {{ t('admin_users_badge_verified') }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex rounded-full bg-yellow-500/20 px-2 py-1 text-xs font-medium text-yellow-400"
                                    >
                                        {{ t('admin_users_badge_unverified') }}
                                    </span>
                                    <span
                                        v-if="user.two_factor_confirmed_at"
                                        class="inline-flex rounded-full bg-purple-500/20 px-2 py-1 text-xs font-medium text-purple-400"
                                    >
                                        {{ t('admin_users_badge_2fa') }}
                                    </span>
                                    <span
                                        v-if="user.is_active === false"
                                        class="inline-flex rounded-full bg-red-500/20 px-2 py-1 text-xs font-medium text-red-400"
                                    >
                                        {{ t('admin_users_badge_inactive') }}
                                    </span>
                                    <span
                                        v-if="user.deleted_at"
                                        class="inline-flex rounded-full bg-slate-500/20 px-2 py-1 text-xs font-medium text-slate-400"
                                    >
                                        {{ t('admin_users_badge_deleted') }}
                                    </span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-slate-300">
                                {{ user.invoices_count }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-slate-300">
                                {{ formatCurrency(user.invoices_sum_total_ttc) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-slate-300">
                                {{ formatDate(user.created_at) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <Link
                                    :href="route('admin.users.show', user.id)"
                                    class="text-purple-400 hover:text-purple-300 transition-colors"
                                    :title="t('admin_users_view_details')"
                                >
                                    <svg class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.64 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.64 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!users.data.length">
                            <td colspan="7" class="px-6 py-8 text-center text-slate-500">
                                {{ t('admin_users_no_users') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="users.last_page > 1" class="border-t border-slate-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-slate-400">
                        {{ t('admin_users_pagination', { from: users.from, to: users.to, total: users.total }) }}
                    </div>
                    <div class="flex gap-2">
                        <Link
                            v-for="link in users.links"
                            :key="link.label"
                            :href="link.url"
                            :class="[
                                'rounded px-3 py-1 text-sm',
                                link.active
                                    ? 'bg-purple-600 text-white'
                                    : link.url
                                        ? 'bg-slate-700 text-slate-300 hover:bg-slate-600'
                                        : 'bg-slate-800 text-slate-500 cursor-not-allowed',
                            ]"
                            v-html="link.label"
                            preserve-state
                        />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
