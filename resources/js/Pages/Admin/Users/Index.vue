<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import debounce from 'lodash/debounce';

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
    <Head title="Gestion des utilisateurs" />

    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-white">Gestion des utilisateurs</h1>
        </template>

        <!-- Filters -->
        <div class="mb-6 rounded-xl bg-slate-800 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="flex-1">
                    <TextInput
                        v-model="search"
                        type="search"
                        placeholder="Rechercher par nom, email ou société..."
                        class="w-full border-slate-600 bg-slate-700 text-white placeholder-slate-400 focus:border-purple-500 focus:ring-purple-500"
                    />
                </div>
                <div class="sm:w-48">
                    <select
                        v-model="status"
                        class="w-full rounded-md border-slate-600 bg-slate-700 text-white focus:border-purple-500 focus:ring-purple-500"
                    >
                        <option value="">Tous les statuts</option>
                        <option value="verified">Email vérifié</option>
                        <option value="unverified">Email non vérifié</option>
                        <option value="with_2fa">Avec 2FA</option>
                        <option value="inactive">Désactivés</option>
                        <option value="deleted">Supprimés</option>
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
                                Utilisateur {{ getSortIcon('name') }}
                            </th>
                            <th
                                class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-300 hover:text-white"
                                @click="sortBy('email')"
                            >
                                Email {{ getSortIcon('email') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-300">
                                Statut
                            </th>
                            <th
                                class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-300 hover:text-white"
                                @click="sortBy('invoices_count')"
                            >
                                Factures {{ getSortIcon('invoices_count') }}
                            </th>
                            <th
                                class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-300 hover:text-white"
                                @click="sortBy('invoices_sum_total_ttc')"
                            >
                                CA {{ getSortIcon('invoices_sum_total_ttc') }}
                            </th>
                            <th
                                class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-300 hover:text-white"
                                @click="sortBy('created_at')"
                            >
                                Inscrit le {{ getSortIcon('created_at') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-300">
                                Actions
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
                                        Vérifié
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex rounded-full bg-yellow-500/20 px-2 py-1 text-xs font-medium text-yellow-400"
                                    >
                                        Non vérifié
                                    </span>
                                    <span
                                        v-if="user.two_factor_confirmed_at"
                                        class="inline-flex rounded-full bg-purple-500/20 px-2 py-1 text-xs font-medium text-purple-400"
                                    >
                                        2FA
                                    </span>
                                    <span
                                        v-if="user.is_active === false"
                                        class="inline-flex rounded-full bg-red-500/20 px-2 py-1 text-xs font-medium text-red-400"
                                    >
                                        Désactivé
                                    </span>
                                    <span
                                        v-if="user.deleted_at"
                                        class="inline-flex rounded-full bg-slate-500/20 px-2 py-1 text-xs font-medium text-slate-400"
                                    >
                                        Supprimé
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
                                    class="text-purple-400 hover:text-purple-300"
                                >
                                    Voir détails
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!users.data.length">
                            <td colspan="7" class="px-6 py-8 text-center text-slate-500">
                                Aucun utilisateur trouvé
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="users.last_page > 1" class="border-t border-slate-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-slate-400">
                        {{ users.from }} à {{ users.to }} sur {{ users.total }}
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
