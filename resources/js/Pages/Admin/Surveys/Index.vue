<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';

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

const scoreCategory = (score) => {
    if (score >= 9) return 'Promoteur';
    if (score <= 6) return 'Détracteur';
    return 'Passif';
};

const exportUrl = route('admin.surveys.export');

// Modal: full response detail
const selected = ref(null);
const open = (response) => { selected.value = response; };
const close = () => { selected.value = null; };
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
                        <tr
                            v-for="r in responses.data"
                            :key="r.id"
                            @click="open(r)"
                            class="text-slate-200 hover:bg-slate-800/60 cursor-pointer transition-colors"
                        >
                            <td class="px-4 py-3">
                                <div class="font-medium text-white">{{ r.user?.name ?? '—' }}</div>
                                <div class="text-xs text-slate-400">{{ r.user?.email ?? '—' }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span :class="['inline-block w-8 h-8 leading-8 rounded-full text-center font-semibold', scoreClass(r.nps_score)]">{{ r.nps_score }}</span>
                            </td>
                            <td class="px-4 py-3 max-w-md">
                                <p v-if="r.comment" class="text-slate-300 line-clamp-2">{{ r.comment }}</p>
                                <span v-else class="text-slate-600 italic">—</span>
                            </td>
                            <td class="px-4 py-3 text-slate-400 whitespace-nowrap">
                                <div class="flex items-center justify-between gap-3">
                                    <span>{{ formatDate(r.completed_at) }}</span>
                                    <span class="text-primary-400 text-xs">Voir →</span>
                                </div>
                            </td>
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

        <!-- Detail modal -->
        <Modal :show="selected !== null" max-width="2xl" @close="close">
            <div v-if="selected" class="p-6 text-slate-200">
                <div class="flex items-start justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-white">{{ selected.user?.name ?? '—' }}</h2>
                        <p class="text-sm text-slate-400">{{ selected.user?.email ?? '—' }}</p>
                    </div>
                    <button @click="close" class="text-slate-400 hover:text-white" aria-label="Fermer">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex items-center gap-3 mb-6">
                    <span :class="['inline-block w-10 h-10 leading-10 rounded-full text-center font-bold text-lg', scoreClass(selected.nps_score)]">{{ selected.nps_score }}</span>
                    <div>
                        <p class="text-sm font-semibold text-white">{{ scoreCategory(selected.nps_score) }}</p>
                        <p class="text-xs text-slate-400">Score NPS : {{ selected.nps_score }}/10</p>
                    </div>
                    <span class="ml-auto text-sm text-slate-400">{{ formatDate(selected.completed_at) }}</span>
                </div>

                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-500 mb-2">Commentaire</p>
                    <p v-if="selected.comment" class="text-slate-900 whitespace-pre-wrap leading-relaxed bg-white border border-slate-200 rounded-xl p-4">{{ selected.comment }}</p>
                    <p v-else class="text-slate-500 italic">Aucun commentaire.</p>
                </div>

                <div class="mt-6 flex justify-end">
                    <button @click="close" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white text-sm font-semibold rounded-lg transition-colors">Fermer</button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
