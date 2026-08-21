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

        <!-- Les réponses écrites : pourquoi. -->
        <div class="rounded-2xl border border-gray-700 bg-gray-800 p-6">
            <h2 class="text-lg font-semibold text-white">
                Dernières réponses
                <span class="text-sm font-normal text-slate-400">({{ reponses.length }})</span>
            </h2>

            <div v-if="reponses.length" class="mt-4 divide-y divide-gray-700">
                <div v-for="r in reponses" :key="r.id" class="py-4">
                    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                        <span class="rounded-full bg-gray-700 px-2 py-0.5 text-xs font-medium text-slate-200">
                            {{ r.secteur }}
                        </span>
                        <a v-if="r.email" :href="'mailto:' + r.email" class="text-sm text-primary-400 hover:underline">
                            {{ r.email }}
                        </a>
                        <span v-else class="text-sm text-slate-500">sans email</span>
                        <span v-if="r.newsletter" class="text-xs text-emerald-400">newsletter acceptée</span>
                        <span class="ml-auto text-xs text-slate-500">{{ r.date }}</span>
                    </div>
                    <p v-if="r.message" class="mt-2 whitespace-pre-line text-sm text-slate-300">{{ r.message }}</p>
                </div>
            </div>

            <p v-else class="mt-4 text-sm text-slate-500">
                Rien encore. Les pages métier doivent d'abord être publiées et explorées.
            </p>
        </div>
    </AdminLayout>
</template>
