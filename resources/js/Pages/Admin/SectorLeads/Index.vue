<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";

defineProps({
    parSecteur: { type: Array, default: () => [] },
    parSource: { type: Array, default: () => [] },
    reponses: { type: Array, default: () => [] },
});

/**
 * La confirmation nomme la personne concernée.
 *
 * Ces lignes se ressemblent — même secteur, même mise en forme — et une
 * confirmation qui dirait seulement « supprimer cette réponse ? » ne
 * permettrait pas de voir qu'on a cliqué une ligne trop haut. La suppression
 * est définitive.
 */
const supprimerReponse = (reponse) => {
    if (confirm(`Supprimer définitivement la réponse de ${reponse.email} ?`)) {
        router.delete(route("admin.sector-leads.destroy", reponse.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Intérêts sectoriels" />

    <AdminLayout>
        <div class="mb-6 flex items-baseline justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white">
                    Intérêts sectoriels
                </h1>
                <p class="mt-1 text-sm text-slate-400">
                    Réponses laissées sur les pages métier. C'est ici que se
                    tranche quel pack mérite d'être construit.
                </p>
            </div>
            <a
                :href="route('admin.sector-leads.export')"
                class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700"
            >
                Exporter en CSV
            </a>
        </div>

        <!-- Décompte par secteur : lequel attire. -->
        <div class="mb-6 rounded-2xl border border-gray-700 bg-gray-800 p-6">
            <h2 class="text-lg font-semibold text-white">Répartition</h2>

            <div v-if="parSecteur.length" class="mt-4 space-y-2">
                <div
                    v-for="ligne in parSecteur"
                    :key="ligne.secteur"
                    class="flex items-center gap-3"
                >
                    <span class="w-44 shrink-0 text-sm text-slate-300">{{
                        ligne.libelle
                    }}</span>
                    <div
                        class="h-2 flex-1 overflow-hidden rounded-full bg-gray-700"
                    >
                        <div
                            class="h-full bg-primary-500"
                            :style="{
                                width:
                                    (100 * ligne.total) /
                                        Math.max(parSecteur[0].total, 1) +
                                    '%',
                            }"
                        />
                    </div>
                    <span
                        class="w-24 shrink-0 text-right text-sm text-slate-400"
                    >
                        {{ ligne.total }}
                        <span class="text-xs"
                            >({{ ligne.avec_email }} avec email)</span
                        >
                    </span>
                </div>
            </div>

            <p v-else class="mt-4 text-sm text-slate-500">
                Aucune réponse pour l'instant.
            </p>
        </div>

        <!-- Les réponses écrites : pourquoi.
             Même présentation que la section Support, pour qu'on s'y retrouve
             sans réapprendre une mise en page. -->
        <!-- Décompte par canal : lequel a produit ces réponses.
             Sans ce tableau, un secteur qui remonte parce qu'on a écrit à sa
             fédération ressemble à un signal de marché. Ce n'en est pas un :
             c'est la mesure de l'effort fourni, pas de la demande. -->
        <div class="mb-6 rounded-2xl border border-gray-700 bg-gray-800 p-6">
            <h2 class="text-lg font-semibold text-white">Par canal</h2>
            <p class="mt-1 text-sm text-slate-400">
                Rapprochez ces nombres des visites de Matomo : c'est le taux de
                conversion par canal qui dit lequel recommencer.
            </p>

            <div v-if="parSource.length" class="mt-4 space-y-2">
                <div
                    v-for="ligne in parSource"
                    :key="ligne.source"
                    class="flex items-center gap-3"
                >
                    <span class="w-44 shrink-0 text-sm text-slate-300">{{
                        ligne.source
                    }}</span>
                    <span class="text-sm font-semibold text-white">{{
                        ligne.total
                    }}</span>
                </div>
            </div>
            <p v-else class="mt-4 text-sm text-slate-400">
                Aucune réponse encore.
            </p>
        </div>

        <h2 class="mb-3 text-lg font-semibold text-white">
            Dernières réponses
            <span class="text-sm font-normal text-slate-400"
                >({{ reponses.length }})</span
            >
        </h2>

        <div
            class="overflow-hidden rounded-xl border border-slate-700 bg-slate-800"
        >
            <table class="min-w-full divide-y divide-slate-700">
                <thead>
                    <tr>
                        <th
                            scope="col"
                            class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-white sm:pl-6"
                        >
                            Secteur
                        </th>
                        <th
                            scope="col"
                            class="px-3 py-3.5 text-left text-sm font-semibold text-white"
                        >
                            Canal
                        </th>
                        <th
                            scope="col"
                            class="px-3 py-3.5 text-left text-sm font-semibold text-white"
                        >
                            Contact
                        </th>
                        <th
                            scope="col"
                            class="px-3 py-3.5 text-left text-sm font-semibold text-white"
                        >
                            Ce qui leur prend du temps
                        </th>
                        <th
                            scope="col"
                            class="hidden px-3 py-3.5 text-left text-sm font-semibold text-white md:table-cell"
                        >
                            Date
                        </th>
                        <th
                            scope="col"
                            class="relative py-3.5 pl-3 pr-4 sm:pr-6"
                        >
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    <tr v-if="reponses.length === 0">
                        <td
                            colspan="6"
                            class="py-10 text-center text-sm text-slate-400"
                        >
                            Rien encore. Les pages métier doivent d'abord être
                            publiées et explorées.
                        </td>
                    </tr>
                    <tr
                        v-for="r in reponses"
                        :key="r.id"
                        class="hover:bg-slate-700/50"
                    >
                        <td
                            class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6"
                        >
                            <span
                                class="rounded-full bg-slate-700 px-2 py-0.5 text-xs font-medium text-slate-200"
                            >
                                {{ r.secteur }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <span
                                v-if="r.source"
                                class="rounded bg-slate-700 px-2 py-0.5 text-xs text-slate-200"
                                >{{ r.source }}</span
                            >
                            <span v-else class="text-xs text-slate-500">-</span>
                        </td>
                        <td class="px-3 py-4 text-sm">
                            <a
                                :href="'mailto:' + r.email"
                                class="text-primary-400 hover:underline"
                                >{{ r.email }}</a
                            >
                            <div
                                v-if="r.newsletter"
                                class="text-xs text-emerald-400"
                            >
                                newsletter acceptée
                            </div>
                        </td>
                        <td class="px-3 py-4 text-sm text-slate-300">
                            <p class="whitespace-pre-line">
                                {{ r.message || "-" }}
                            </p>
                        </td>
                        <td
                            class="hidden whitespace-nowrap px-3 py-4 text-sm text-slate-400 md:table-cell"
                        >
                            {{ r.date }}
                        </td>
                        <td
                            class="whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm sm:pr-6"
                        >
                            <button
                                type="button"
                                @click="supprimerReponse(r)"
                                class="rounded-lg p-1.5 text-red-400 hover:bg-slate-700 hover:text-red-300"
                                title="Supprimer cette réponse"
                            >
                                <svg
                                    class="h-5 w-5"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.519.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                <span class="sr-only"
                                    >Supprimer la réponse de {{ r.email }}</span
                                >
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
