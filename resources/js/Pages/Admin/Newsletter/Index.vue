<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    subscribers: Object,
    stats: Object,
    filters: Object,
});

const search = ref(props.filters.search);
const source = ref(props.filters.source);
const locale = ref(props.filters.locale);

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleString('fr-LU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const applyFilters = debounce(() => {
    router.get(route('admin.newsletter.index'), {
        search: search.value,
        source: source.value,
        locale: locale.value,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, source, locale], () => {
    applyFilters();
});

const sortBy = (field) => {
    const direction = props.filters.sort === field && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get(route('admin.newsletter.index'), {
        search: search.value,
        source: source.value,
        locale: locale.value,
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

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (source.value) params.set('source', source.value);
    if (locale.value) params.set('locale', locale.value);
    const qs = params.toString();
    return route('admin.newsletter.export') + (qs ? '?' + qs : '');
});

const sourceLabel = (src) => {
    const labels = {
        templates_invoice_blank: 'Modèle facture',
        templates_aed_checklist: 'Checklist AED',
        templates_reminder_letter: 'Lettre relance',
        templates_vat_calendar: 'Calendrier TVA',
        footer: 'Footer',
    };
    return labels[src] || src;
};

const sourceBadgeClass = (src) => {
    if (src.startsWith('templates_')) return 'bg-orange-500/20 text-orange-300';
    if (src === 'footer') return 'bg-blue-500/20 text-blue-300';
    return 'bg-slate-500/20 text-slate-300';
};

const deleteSubscriber = (subscriber) => {
    if (!confirm(`Supprimer définitivement l'abonné ${subscriber.email} ?`)) return;
    router.delete(route('admin.newsletter.destroy', subscriber.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Newsletter — Abonnés" />

    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-white">Newsletter & Outils — Abonnés</h1>
        </template>

        <!-- Stats cards -->
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-xl bg-slate-800 p-4">
                <p class="text-xs uppercase text-slate-400">Total</p>
                <p class="mt-1 text-2xl font-bold text-white">{{ stats.total }}</p>
            </div>
            <div class="rounded-xl bg-slate-800 p-4">
                <p class="text-xs uppercase text-slate-400">Via outils</p>
                <p class="mt-1 text-2xl font-bold text-orange-400">{{ stats.tools }}</p>
            </div>
            <div class="rounded-xl bg-slate-800 p-4">
                <p class="text-xs uppercase text-slate-400">Via footer</p>
                <p class="mt-1 text-2xl font-bold text-blue-400">{{ stats.footer }}</p>
            </div>
            <div class="rounded-xl bg-slate-800 p-4">
                <p class="text-xs uppercase text-slate-400">Confirmés</p>
                <p class="mt-1 text-2xl font-bold text-emerald-400">{{ stats.confirmed }}</p>
            </div>
            <div class="rounded-xl bg-slate-800 p-4">
                <p class="text-xs uppercase text-slate-400">Désinscrits</p>
                <p class="mt-1 text-2xl font-bold text-rose-400">{{ stats.unsubscribed }}</p>
            </div>
        </div>

        <!-- Breakdown par template -->
        <div v-if="Object.keys(stats.by_template).length" class="mb-6 rounded-xl bg-slate-800 p-4">
            <p class="mb-3 text-xs uppercase text-slate-400">Téléchargements par modèle</p>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="(count, src) in stats.by_template" :key="src" class="flex items-center justify-between rounded-lg bg-slate-900 px-3 py-2">
                    <span class="text-sm text-slate-300">{{ sourceLabel(src) }}</span>
                    <span class="text-sm font-bold text-white">{{ count }}</span>
                </div>
            </div>
        </div>

        <!-- Filters + export -->
        <div class="mb-6 rounded-xl bg-slate-800 p-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                <div class="flex-1">
                    <TextInput
                        v-model="search"
                        type="search"
                        placeholder="Rechercher par email..."
                        class="w-full"
                    />
                </div>
                <select v-model="source" class="rounded-md border-gray-300 bg-slate-900 text-slate-100 focus:border-rose-500 focus:ring-rose-500">
                    <option value="">Toutes les sources</option>
                    <option value="tools">Tous les outils</option>
                    <option value="templates_invoice_blank">Modèle facture</option>
                    <option value="templates_aed_checklist">Checklist AED</option>
                    <option value="templates_reminder_letter">Lettre relance</option>
                    <option value="templates_vat_calendar">Calendrier TVA</option>
                    <option value="footer">Footer</option>
                </select>
                <select v-model="locale" class="rounded-md border-gray-300 bg-slate-900 text-slate-100 focus:border-rose-500 focus:ring-rose-500">
                    <option value="">Toutes langues</option>
                    <option value="fr">Français</option>
                    <option value="de">Deutsch</option>
                    <option value="en">English</option>
                    <option value="lb">Lëtzebuergesch</option>
                    <option value="pt">Português</option>
                </select>
                <a
                    :href="exportUrl"
                    class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-rose-700"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Exporter CSV
                </a>
            </div>
        </div>

        <!-- Subscribers table -->
        <div class="overflow-hidden rounded-xl bg-slate-800">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-900">
                        <tr>
                            <th class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase text-slate-400" @click="sortBy('email')">
                                Email {{ getSortIcon('email') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase text-slate-400" @click="sortBy('source')">
                                Source {{ getSortIcon('source') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase text-slate-400" @click="sortBy('locale')">
                                Langue {{ getSortIcon('locale') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase text-slate-400" @click="sortBy('confirmed_at')">
                                Confirmé {{ getSortIcon('confirmed_at') }}
                            </th>
                            <th class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase text-slate-400" @click="sortBy('created_at')">
                                Inscription {{ getSortIcon('created_at') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-slate-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        <tr v-for="sub in subscribers.data" :key="sub.id" class="hover:bg-slate-700/40">
                            <td class="px-4 py-3 text-sm">
                                <a :href="`mailto:${sub.email}`" class="font-medium text-white hover:text-rose-400">{{ sub.email }}</a>
                                <div v-if="sub.unsubscribed_at" class="text-xs text-rose-400">désinscrit</div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span :class="['inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium', sourceBadgeClass(sub.source)]">
                                    {{ sourceLabel(sub.source) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm uppercase text-slate-300">{{ sub.locale }}</td>
                            <td class="px-4 py-3 text-sm text-slate-300">{{ formatDate(sub.confirmed_at) }}</td>
                            <td class="px-4 py-3 text-sm text-slate-300">{{ formatDate(sub.created_at) }}</td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    class="text-xs text-rose-400 hover:text-rose-300"
                                    @click="deleteSubscriber(sub)"
                                >
                                    Supprimer
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!subscribers.data.length">
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">
                                Aucun abonné trouvé.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="subscribers.last_page > 1" class="flex items-center justify-between border-t border-slate-700 px-4 py-3">
                <p class="text-sm text-slate-400">
                    Affichage de {{ subscribers.from }} à {{ subscribers.to }} sur {{ subscribers.total }}
                </p>
                <div class="flex gap-2">
                    <Link
                        v-for="link in subscribers.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'rounded-md px-3 py-1 text-sm',
                            link.active ? 'bg-rose-600 text-white' : 'text-slate-300 hover:bg-slate-700',
                            !link.url ? 'cursor-not-allowed opacity-50' : '',
                        ]"
                        v-html="link.label"
                        :preserve-scroll="true"
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
