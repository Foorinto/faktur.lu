<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    userStats: Object,
    invoiceStats: Object,
    revenueStats: Object,
    userGrowthChart: Array,
    revenueChart: Array,
    recentUsers: Array,
    sectorStats: Object,
    topUsers: Array,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
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
</script>

<template>
    <Head title="Dashboard Admin" />

    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-white">Dashboard</h1>
        </template>

        <!-- KPI Cards -->
        <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Total Users -->
            <div class="rounded-xl bg-slate-800 p-6">
                <div class="flex items-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-500/20">
                        <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-slate-400">Utilisateurs</p>
                        <p class="text-2xl font-bold text-white">{{ userStats.total }}</p>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm">
                    <span class="text-green-400">+{{ userStats.new_this_month }}</span>
                    <span class="ml-2 text-slate-500">ce mois</span>
                </div>
            </div>

            <!-- Total Invoices -->
            <div class="rounded-xl bg-slate-800 p-6">
                <div class="flex items-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-500/20">
                        <svg class="h-6 w-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-slate-400">Factures</p>
                        <p class="text-2xl font-bold text-white">{{ invoiceStats.total }}</p>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm">
                    <span class="text-green-400">{{ invoiceStats.paid }}</span>
                    <span class="ml-2 text-slate-500">payées</span>
                </div>
            </div>

            <!-- Revenue -->
            <div class="rounded-xl bg-slate-800 p-6">
                <div class="flex items-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-500/20">
                        <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-slate-400">CA Total</p>
                        <p class="text-2xl font-bold text-white">{{ formatCurrency(revenueStats.total_revenue) }}</p>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm">
                    <span class="text-green-400">{{ formatCurrency(revenueStats.this_month) }}</span>
                    <span class="ml-2 text-slate-500">ce mois</span>
                </div>
            </div>

            <!-- Pending Revenue -->
            <div class="rounded-xl bg-slate-800 p-6">
                <div class="flex items-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-yellow-500/20">
                        <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-slate-400">En attente</p>
                        <p class="text-2xl font-bold text-white">{{ formatCurrency(revenueStats.pending) }}</p>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm">
                    <span class="text-yellow-400">{{ invoiceStats.sent + invoiceStats.overdue }}</span>
                    <span class="ml-2 text-slate-500">factures</span>
                </div>
            </div>
        </div>

        <!-- Charts & Tables -->
        <!-- Répartition par secteur d'activité.
             Raison d'être de l'écran de sélection à l'inscription : décider
             quel pack métier mérite d'être construit, sur des comptes plutôt
             que sur des intuitions. -->
        <div class="mb-6 rounded-2xl border border-gray-700 bg-gray-800 p-6">
            <div class="flex items-baseline justify-between">
                <h2 class="text-lg font-semibold text-white">Secteurs d'activité</h2>
                <p class="text-xs text-slate-400">
                    {{ sectorStats?.repondants ?? 0 }} réponse(s),
                    {{ sectorStats?.sans_reponse ?? 0 }} compte(s) jamais interrogé(s)
                </p>
            </div>

            <div v-if="sectorStats?.par_secteur?.length" class="mt-4 space-y-2">
                <div v-for="ligne in sectorStats.par_secteur" :key="ligne.secteur" class="flex items-center gap-3">
                    <span class="w-44 shrink-0 text-sm text-slate-300">
                        {{ ligne.libelle }}
                    </span>
                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-700">
                        <div
                            class="h-full bg-primary-500"
                            :style="{ width: (100 * ligne.total / Math.max(sectorStats.repondants, 1)) + '%' }"
                        />
                    </div>
                    <span class="w-10 shrink-0 text-right text-sm font-medium text-white">{{ ligne.total }}</span>
                </div>
            </div>

            <p v-else class="mt-4 text-sm text-slate-500">
                Aucune réponse pour l'instant. Les comptes créés avant août 2026 n'ont jamais vu la question.
            </p>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Recent Users -->
            <div class="rounded-xl bg-slate-800 p-6">
                <h2 class="mb-4 text-lg font-semibold text-white">Derniers inscrits</h2>
                <div class="space-y-3">
                    <div
                        v-for="user in recentUsers"
                        :key="user.id"
                        class="flex items-center justify-between rounded-lg bg-slate-700/50 p-3"
                    >
                        <div>
                            <p class="font-medium text-white">{{ user.name }}</p>
                            <p class="text-sm text-slate-400">{{ user.email }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-slate-400">{{ formatDate(user.created_at) }}</p>
                            <p class="text-sm text-slate-500">{{ user.invoices_count }} factures</p>
                        </div>
                    </div>
                    <div v-if="!recentUsers?.length" class="py-4 text-center text-slate-500">
                        Aucun utilisateur
                    </div>
                </div>
            </div>

            <!-- Top Users by Revenue -->
            <div class="rounded-xl bg-slate-800 p-6">
                <h2 class="mb-4 text-lg font-semibold text-white">Top utilisateurs (CA)</h2>
                <div class="space-y-3">
                    <div
                        v-for="(user, index) in topUsers"
                        :key="user.id"
                        class="flex items-center justify-between rounded-lg bg-slate-700/50 p-3"
                    >
                        <div class="flex items-center">
                            <span
                                :class="[
                                    'flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold',
                                    index === 0 ? 'bg-yellow-500/20 text-yellow-400' :
                                    index === 1 ? 'bg-slate-400/20 text-slate-300' :
                                    index === 2 ? 'bg-orange-500/20 text-orange-400' :
                                    'bg-slate-600/20 text-slate-400'
                                ]"
                            >
                                {{ index + 1 }}
                            </span>
                            <div class="ml-3">
                                <p class="font-medium text-white">{{ user.name }}</p>
                                <p class="text-sm text-slate-400">{{ user.invoices_count }} factures</p>
                            </div>
                        </div>
                        <p class="font-semibold text-green-400">
                            {{ formatCurrency(user.invoices_sum_total_ttc) }}
                        </p>
                    </div>
                    <div v-if="!topUsers?.length" class="py-4 text-center text-slate-500">
                        Aucune donnée
                    </div>
                </div>
            </div>

            <!-- User Stats Details -->
            <div class="rounded-xl bg-slate-800 p-6">
                <h2 class="mb-4 text-lg font-semibold text-white">Détails utilisateurs</h2>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Email vérifié</dt>
                        <dd class="font-medium text-green-400">{{ userStats.verified }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">2FA activé</dt>
                        <dd class="font-medium text-purple-400">{{ userStats.with_2fa }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Actifs</dt>
                        <dd class="font-medium text-blue-400">{{ userStats.active }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Nouveaux ce mois</dt>
                        <dd class="font-medium text-white">{{ userStats.new_this_month }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Invoice Stats Details -->
            <div class="rounded-xl bg-slate-800 p-6">
                <h2 class="mb-4 text-lg font-semibold text-white">Détails factures</h2>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Brouillons</dt>
                        <dd class="font-medium text-slate-300">{{ invoiceStats.draft }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Envoyées</dt>
                        <dd class="font-medium text-blue-400">{{ invoiceStats.sent }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Payées</dt>
                        <dd class="font-medium text-green-400">{{ invoiceStats.paid }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">En retard</dt>
                        <dd class="font-medium text-red-400">{{ invoiceStats.overdue }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Ce mois</dt>
                        <dd class="font-medium text-white">{{ invoiceStats.this_month }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </AdminLayout>
</template>
