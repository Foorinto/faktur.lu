<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    responses: Object,
    stats: Object,
});

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleString('fr-LU', {
        day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
};

const scoreClass = (score) => {
    if (score >= 9) return 'bg-emerald-100 text-emerald-700';
    if (score <= 6) return 'bg-rose-100 text-rose-700';
    return 'bg-amber-100 text-amber-700';
};

const exportUrl = route('admin.surveys.export');
</script>

<template>
    <Head title="Sondages de satisfaction" />

    <AdminLayout>
        <div class="p-6 sm:p-8 max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-white">Sondages de satisfaction</h1>
                    <p class="text-slate-400 mt-1">Réponses NPS des utilisateurs</p>
                </div>
                <a
                    :href="exportUrl"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm font-semibold rounded-lg transition-colors"
                >
                    Exporter CSV
                </a>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-surface-card rounded-xl p-5 border border-slate-700">
                    <p class="text-slate-400 text-sm">Score NPS</p>
                    <p class="text-3xl font-bold text-white mt-1">{{ stats.nps !== null ? stats.nps : '—' }}</p>
                    <p class="text-xs text-slate-500 mt-1">(-100 à +100)</p>
                </div>
                <div class="bg-surface-card rounded-xl p-5 border border-slate-700">
                    <p class="text-slate-400 text-sm">Taux de réponse</p>
                    <p class="text-3xl font-bold text-white mt-1">{{ stats.response_rate }}%</p>
                    <p class="text-xs text-slate-500 mt-1">{{ stats.completed }} / {{ stats.sent }} envoyés</p>
                </div>
                <div class="bg-surface-card rounded-xl p-5 border border-slate-700">
                    <p class="text-slate-400 text-sm">Réponses</p>
                    <p class="text-3xl font-bold text-white mt-1">{{ stats.completed }}</p>
                </div>
                <div class="bg-surface-card rounded-xl p-5 border border-slate-700">
                    <p class="text-slate-400 text-sm">Répartition</p>
                    <p class="text-sm text-white mt-2">
                        <span class="text-emerald-400">{{ stats.promoters }} prom.</span> ·
                        <span class="text-amber-400">{{ stats.passives }} pass.</span> ·
                        <span class="text-rose-400">{{ stats.detractors }} détr.</span>
                    </p>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-surface-card rounded-xl border border-slate-700 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-800 text-slate-300">
                        <tr>
                            <th class="text-left px-4 py-3 font-semibold">Utilisateur</th>
                            <th class="text-center px-4 py-3 font-semibold">NPS</th>
                            <th class="text-left px-4 py-3 font-semibold">Commentaire</th>
                            <th class="text-left px-4 py-3 font-semibold">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        <tr v-for="r in responses.data" :key="r.id" class="text-slate-200">
                            <td class="px-4 py-3">
                                <div class="font-medium text-white">{{ r.user?.name ?? '—' }}</div>
                                <div class="text-xs text-slate-400">{{ r.user?.email ?? '—' }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span :class="['inline-block w-8 h-8 leading-8 rounded-full text-center font-semibold', scoreClass(r.nps_score)]">{{ r.nps_score }}</span>
                            </td>
                            <td class="px-4 py-3 max-w-md">
                                <span v-if="r.comment" class="text-slate-300">{{ r.comment }}</span>
                                <span v-else class="text-slate-600 italic">—</span>
                            </td>
                            <td class="px-4 py-3 text-slate-400 whitespace-nowrap">{{ formatDate(r.completed_at) }}</td>
                        </tr>
                        <tr v-if="responses.data.length === 0">
                            <td colspan="4" class="px-4 py-12 text-center text-slate-500">Aucune réponse pour le moment.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="responses.links && responses.links.length > 3" class="flex flex-wrap gap-1 mt-6 justify-center">
                <template v-for="(link, i) in responses.links" :key="i">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        v-html="link.label"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-sm',
                            link.active ? 'bg-primary-500 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'
                        ]"
                    />
                    <span v-else v-html="link.label" class="px-3 py-1.5 rounded-lg text-sm text-slate-600" />
                </template>
            </div>
        </div>
    </AdminLayout>
</template>
