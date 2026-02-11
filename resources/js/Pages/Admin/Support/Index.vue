<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    tickets: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    categories: {
        type: Object,
        required: true,
    },
    statuses: {
        type: Object,
        required: true,
    },
    priorities: {
        type: Object,
        required: true,
    },
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const categoryFilter = ref(props.filters.category || '');
const priorityFilter = ref(props.filters.priority || '');

const updateFilters = debounce(() => {
    router.get(route('admin.support.index'), {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        category: categoryFilter.value || undefined,
        priority: priorityFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, statusFilter, categoryFilter, priorityFilter], updateFilters);

const getStatusBadgeClass = (status) => {
    const classes = {
        new: 'bg-purple-100 text-purple-700',
        open: 'bg-blue-100 text-blue-700',
        in_progress: 'bg-yellow-100 text-yellow-700',
        waiting: 'bg-orange-100 text-orange-700',
        resolved: 'bg-emerald-100 text-emerald-700',
        closed: 'bg-slate-100 text-slate-700',
    };
    return classes[status] || classes.closed;
};

const getPriorityBadgeClass = (priority) => {
    const classes = {
        urgent: 'bg-red-100 text-red-700',
        high: 'bg-orange-100 text-orange-700',
        normal: 'bg-blue-100 text-blue-700',
        low: 'bg-slate-100 text-slate-700',
    };
    return classes[priority] || classes.normal;
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};

const deleteTicket = (ticket) => {
    if (confirm(`Êtes-vous sûr de vouloir supprimer le ticket ${ticket.reference} ? Cette action est irréversible.`)) {
        router.delete(route('admin.support.destroy', ticket.id));
    }
};
</script>

<template>
    <Head title="Support - Admin" />

    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-white">Support</h1>
        </template>

        <!-- Stats cards -->
        <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
            <div class="rounded-xl bg-slate-800 p-4 border border-slate-700">
                <div class="text-2xl font-bold text-purple-400">{{ stats.new }}</div>
                <div class="text-sm text-slate-400">Nouveaux</div>
            </div>
            <div class="rounded-xl bg-slate-800 p-4 border border-slate-700">
                <div class="text-2xl font-bold text-blue-400">{{ stats.open }}</div>
                <div class="text-sm text-slate-400">Ouverts</div>
            </div>
            <div class="rounded-xl bg-slate-800 p-4 border border-slate-700">
                <div class="text-2xl font-bold text-orange-400">{{ stats.waiting }}</div>
                <div class="text-sm text-slate-400">En attente</div>
            </div>
            <div class="rounded-xl bg-slate-800 p-4 border border-slate-700">
                <div class="text-2xl font-bold text-emerald-400">{{ stats.resolved_today }}</div>
                <div class="text-sm text-slate-400">Résolus aujourd'hui</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-4">
            <div class="relative flex-1 min-w-[200px]">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Rechercher (réf., sujet, email)..."
                    class="block w-full rounded-lg border-0 bg-slate-700 py-2 pl-10 pr-3 text-white placeholder:text-slate-400 focus:ring-2 focus:ring-purple-500 sm:text-sm"
                />
            </div>

            <select
                v-model="statusFilter"
                class="rounded-lg border-0 bg-slate-700 py-2 pl-3 pr-10 text-white focus:ring-2 focus:ring-purple-500 sm:text-sm"
            >
                <option value="">Tous les statuts</option>
                <option v-for="(label, value) in statuses" :key="value" :value="value">
                    {{ label }}
                </option>
            </select>

            <select
                v-model="categoryFilter"
                class="rounded-lg border-0 bg-slate-700 py-2 pl-3 pr-10 text-white focus:ring-2 focus:ring-purple-500 sm:text-sm"
            >
                <option value="">Toutes catégories</option>
                <option v-for="(label, value) in categories" :key="value" :value="value">
                    {{ label }}
                </option>
            </select>

            <select
                v-model="priorityFilter"
                class="rounded-lg border-0 bg-slate-700 py-2 pl-3 pr-10 text-white focus:ring-2 focus:ring-purple-500 sm:text-sm"
            >
                <option value="">Toutes priorités</option>
                <option v-for="(label, value) in priorities" :key="value" :value="value">
                    {{ label }}
                </option>
            </select>
        </div>

        <!-- Tickets table -->
        <div class="overflow-hidden rounded-xl bg-slate-800 border border-slate-700">
            <table class="min-w-full divide-y divide-slate-700">
                <thead>
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-white sm:pl-6">
                            Réf.
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-white">
                            Utilisateur
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-white">
                            Sujet
                        </th>
                        <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-white md:table-cell">
                            Catégorie
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-white">
                            Priorité
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-white">
                            Statut
                        </th>
                        <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-white lg:table-cell">
                            Date
                        </th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    <tr v-if="tickets.data.length === 0">
                        <td colspan="8" class="py-10 text-center text-sm text-slate-400">
                            Aucun ticket trouvé.
                        </td>
                    </tr>
                    <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-slate-700/50">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-white sm:pl-6">
                            {{ ticket.reference }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <div class="text-white">{{ ticket.user?.name || '-' }}</div>
                            <div class="text-slate-400 text-xs">{{ ticket.user?.email }}</div>
                        </td>
                        <td class="px-3 py-4 text-sm text-white max-w-[200px] truncate">
                            {{ ticket.subject }}
                        </td>
                        <td class="hidden whitespace-nowrap px-3 py-4 text-sm text-slate-300 md:table-cell">
                            {{ categories[ticket.category] }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <span
                                :class="getPriorityBadgeClass(ticket.priority)"
                                class="inline-flex items-center rounded-lg px-2 py-0.5 text-xs font-medium"
                            >
                                {{ priorities[ticket.priority] }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <span
                                :class="getStatusBadgeClass(ticket.status)"
                                class="inline-flex items-center rounded-lg px-2 py-0.5 text-xs font-medium"
                            >
                                {{ statuses[ticket.status] }}
                            </span>
                        </td>
                        <td class="hidden whitespace-nowrap px-3 py-4 text-sm text-slate-300 lg:table-cell">
                            {{ formatDate(ticket.created_at) }}
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            <div class="flex items-center justify-end gap-2">
                                <Link
                                    :href="route('admin.support.show', ticket.id)"
                                    class="rounded-lg p-1.5 text-purple-400 hover:bg-slate-700 hover:text-purple-300"
                                    title="Voir le ticket"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" />
                                        <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 010-1.186A10.004 10.004 0 0110 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0110 17c-4.257 0-7.893-2.66-9.336-6.41zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                    </svg>
                                </Link>
                                <button
                                    type="button"
                                    @click="deleteTicket(ticket)"
                                    class="rounded-lg p-1.5 text-red-400 hover:bg-slate-700 hover:text-red-300"
                                    title="Supprimer le ticket"
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
        <div v-if="tickets.links && tickets.links.length > 3" class="mt-6 flex items-center justify-between">
            <div class="text-sm text-slate-400">
                Affichage {{ tickets.from }} à {{ tickets.to }} sur {{ tickets.total }} tickets
            </div>
            <nav class="isolate inline-flex -space-x-px rounded-lg shadow-sm">
                <template v-for="(link, index) in tickets.links" :key="index">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            link.active
                                ? 'z-10 bg-purple-600 text-white'
                                : 'bg-slate-700 text-slate-300 hover:bg-slate-600',
                            'relative inline-flex items-center px-4 py-2 text-sm font-semibold border border-slate-600 focus:z-20',
                            index === 0 ? 'rounded-l-lg' : '',
                            index === tickets.links.length - 1 ? 'rounded-r-lg' : '',
                        ]"
                        v-html="link.label"
                        preserve-scroll
                    />
                    <span
                        v-else
                        :class="[
                            'relative inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-500 bg-slate-800 border border-slate-600',
                            index === 0 ? 'rounded-l-lg' : '',
                            index === tickets.links.length - 1 ? 'rounded-r-lg' : '',
                        ]"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </div>
    </AdminLayout>
</template>
