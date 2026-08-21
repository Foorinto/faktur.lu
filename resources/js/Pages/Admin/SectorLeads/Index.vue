<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    parSecteur: { type: Array, default: () => [] },
    reponses: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Intérêts sectoriels" />

    <AdminLayout>
        <div class="mb-6 flex items-baseline justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white">Intérêts sectoriels</h1>
                <p class="mt-1 text-sm text-slate-400">
                    Réponses laissées sur les pages métier. C'est ici que se tranche
                    quel pack mérite d'être construit.
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
                <div v-for="ligne in parSecteur" :key="ligne.secteur" class="flex items-center gap-3">
                    <span class="w-44 shrink-0 text-sm text-slate-300">{{ ligne.libelle }}</span>
                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-700">
                        <div
                            class="h-full bg-primary-500"
                            :style="{ width: (100 * ligne.total / Math.max(parSecteur[0].total, 1)) + '%' }"
                        />
                    </div>
                    <span class="w-24 shrink-0 text-right text-sm text-slate-400">
                        {{ ligne.total }} <span class="text-xs">({{ ligne.avec_email }} avec email)</span>
                    </span>
                </div>
            </div>

            <p v-else class="mt-4 text-sm text-slate-500">Aucune réponse pour l'instant.</p>
        </div>

        <!-- Les réponses écrites : pourquoi.
             Même présentation que la section Support, pour qu'on s'y retrouve
             sans réapprendre une mise en page. -->
        <h2 class="mb-3 text-lg font-semibold text-white">
            Dernières réponses
            <span class="text-sm font-normal text-slate-400">({{ reponses.length }})</span>
        </h2>

        <div class="overflow-hidden rounded-xl border border-slate-700 bg-slate-800">
            <table class="min-w-full divide-y divide-slate-700">
                <thead>
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-white sm:pl-6">
                            Secteur
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-white">
                            Contact
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-white">
                            Ce qui leur prend du temps
                        </th>
                        <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-white md:table-cell">
                            Date
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    <tr v-if="reponses.length === 0">
                        <td colspan="4" class="py-10 text-center text-sm text-slate-400">
                            Rien encore. Les pages métier doivent d'abord être publiées et explorées.
                        </td>
                    </tr>
                    <tr v-for="r in reponses" :key="r.id" class="hover:bg-slate-700/50">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                            <span class="rounded-full bg-slate-700 px-2 py-0.5 text-xs font-medium text-slate-200">
                                {{ r.secteur }}
                            </span>
                        </td>
                        <td class="px-3 py-4 text-sm">
                            <a :href="'mailto:' + r.email" class="text-primary-400 hover:underline">{{ r.email }}</a>
                            <div v-if="r.newsletter" class="text-xs text-emerald-400">newsletter acceptée</div>
                        </td>
                        <td class="px-3 py-4 text-sm text-slate-300">
                            <p class="whitespace-pre-line">{{ r.message || '-' }}</p>
                        </td>
                        <td class="hidden whitespace-nowrap px-3 py-4 text-sm text-slate-400 md:table-cell">
                            {{ r.date }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </AdminLayout>
</template>
