<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    user: Object,
    stats: Object,
    recentInvoices: Array,
});

const processing = ref(false);
const confirmAction = ref(null);

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
        hour: '2-digit',
        minute: '2-digit',
    });
};

const executeAction = (action, routeName) => {
    processing.value = true;
    router.post(route(routeName, props.user.id), {}, {
        onFinish: () => {
            processing.value = false;
            confirmAction.value = null;
        },
    });
};

const deleteUser = () => {
    processing.value = true;
    router.delete(route('admin.users.destroy', props.user.id), {
        onFinish: () => {
            processing.value = false;
            confirmAction.value = null;
        },
    });
};

const restoreUser = () => {
    processing.value = true;
    router.post(route('admin.users.restore', props.user.id), {}, {
        onFinish: () => {
            processing.value = false;
            confirmAction.value = null;
        },
    });
};

const forceDeleteUser = () => {
    processing.value = true;
    router.delete(route('admin.users.force-delete', props.user.id), {
        onFinish: () => {
            processing.value = false;
            confirmAction.value = null;
        },
    });
};

const getStatusBadge = (status) => {
    const badges = {
        draft: { class: 'bg-slate-500/20 text-slate-400', label: 'Brouillon' },
        sent: { class: 'bg-blue-500/20 text-blue-400', label: 'Envoyée' },
        paid: { class: 'bg-green-500/20 text-green-400', label: 'Payée' },
        overdue: { class: 'bg-red-500/20 text-red-400', label: 'En retard' },
        cancelled: { class: 'bg-slate-500/20 text-slate-400', label: 'Annulée' },
    };
    return badges[status] || { class: 'bg-slate-500/20 text-slate-400', label: status };
};
</script>

<template>
    <Head :title="`Utilisateur: ${user.name}`" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('admin.users.index')" class="text-slate-400 hover:text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h1 class="text-xl font-semibold text-white">{{ user.name }}</h1>
            </div>
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- User info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Profile card -->
                <div class="rounded-xl bg-slate-800 p-6">
                    <h2 class="mb-4 text-lg font-semibold text-white">Informations</h2>

                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm text-slate-400">Nom</dt>
                            <dd class="mt-1 text-white">{{ user.name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-400">Email</dt>
                            <dd class="mt-1 text-white">{{ user.email }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-400">Société</dt>
                            <dd class="mt-1 text-white">{{ user.company_name || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-400">N° TVA</dt>
                            <dd class="mt-1 text-white">{{ user.vat_number || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-400">Téléphone</dt>
                            <dd class="mt-1 text-white">{{ user.phone || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-400">Inscrit le</dt>
                            <dd class="mt-1 text-white">{{ formatDate(user.created_at) }}</dd>
                        </div>
                    </dl>

                    <!-- Status badges -->
                    <div class="mt-6 flex flex-wrap gap-2">
                        <span
                            v-if="user.email_verified_at"
                            class="inline-flex rounded-full bg-green-500/20 px-3 py-1 text-sm font-medium text-green-400"
                        >
                            Email vérifié
                        </span>
                        <span
                            v-else
                            class="inline-flex rounded-full bg-yellow-500/20 px-3 py-1 text-sm font-medium text-yellow-400"
                        >
                            Email non vérifié
                        </span>
                        <span
                            v-if="user.two_factor_confirmed_at"
                            class="inline-flex rounded-full bg-purple-500/20 px-3 py-1 text-sm font-medium text-purple-400"
                        >
                            2FA activé
                        </span>
                        <span
                            v-if="user.is_active === false"
                            class="inline-flex rounded-full bg-red-500/20 px-3 py-1 text-sm font-medium text-red-400"
                        >
                            Compte désactivé
                        </span>
                        <span
                            v-if="user.deleted_at"
                            class="inline-flex rounded-full bg-slate-500/20 px-3 py-1 text-sm font-medium text-slate-400"
                        >
                            Supprimé le {{ formatDate(user.deleted_at) }}
                        </span>
                    </div>
                </div>

                <!-- Recent invoices -->
                <div class="rounded-xl bg-slate-800 p-6">
                    <h2 class="mb-4 text-lg font-semibold text-white">Dernières factures</h2>

                    <div v-if="recentInvoices?.length" class="space-y-3">
                        <div
                            v-for="invoice in recentInvoices"
                            :key="invoice.id"
                            class="flex items-center justify-between rounded-lg bg-slate-700/50 p-3"
                        >
                            <div>
                                <p class="font-medium text-white">{{ invoice.invoice_number }}</p>
                                <p class="text-sm text-slate-400">{{ formatDate(invoice.created_at) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-medium text-white">{{ formatCurrency(invoice.total_ttc) }}</p>
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                        getStatusBadge(invoice.status).class,
                                    ]"
                                >
                                    {{ getStatusBadge(invoice.status).label }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-4 text-center text-slate-500">
                        Aucune facture
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Stats -->
                <div class="rounded-xl bg-slate-800 p-6">
                    <h2 class="mb-4 text-lg font-semibold text-white">Statistiques</h2>
                    <dl class="space-y-4">
                        <div class="flex justify-between">
                            <dt class="text-slate-400">Factures</dt>
                            <dd class="font-medium text-white">{{ stats.total_invoices }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-400">Payées</dt>
                            <dd class="font-medium text-green-400">{{ stats.paid_invoices }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-400">En attente</dt>
                            <dd class="font-medium text-yellow-400">{{ stats.pending_invoices }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-400">CA Total</dt>
                            <dd class="font-medium text-white">{{ formatCurrency(stats.total_revenue) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-400">Clients</dt>
                            <dd class="font-medium text-white">{{ stats.total_clients }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Actions -->
                <div class="rounded-xl bg-slate-800 p-6">
                    <h2 class="mb-4 text-lg font-semibold text-white">Actions</h2>
                    <div class="space-y-3">
                        <!-- Actions pour utilisateur supprimé -->
                        <template v-if="user.deleted_at">
                            <!-- Restore -->
                            <PrimaryButton
                                v-if="confirmAction !== 'restore'"
                                class="w-full justify-center bg-green-600 hover:bg-green-700"
                                @click="confirmAction = 'restore'"
                            >
                                Restaurer le compte
                            </PrimaryButton>
                            <div v-else-if="confirmAction === 'restore'" class="rounded-lg bg-green-500/10 p-3">
                                <p class="mb-2 text-sm text-green-300">Restaurer cet utilisateur ?</p>
                                <div class="flex gap-2">
                                    <PrimaryButton
                                        class="flex-1 justify-center bg-green-600"
                                        :disabled="processing"
                                        @click="restoreUser"
                                    >
                                        Oui
                                    </PrimaryButton>
                                    <SecondaryButton class="flex-1 justify-center" @click="confirmAction = null">
                                        Non
                                    </SecondaryButton>
                                </div>
                            </div>

                            <!-- Force Delete -->
                            <DangerButton
                                v-if="confirmAction !== 'force-delete'"
                                class="w-full justify-center"
                                @click="confirmAction = 'force-delete'"
                            >
                                Supprimer définitivement
                            </DangerButton>
                            <div v-else-if="confirmAction === 'force-delete'" class="rounded-lg bg-red-500/10 p-3">
                                <p class="mb-2 text-sm text-red-300">
                                    Cette action est <strong>irréversible</strong> ! Toutes les données seront perdues.
                                </p>
                                <div class="flex gap-2">
                                    <DangerButton
                                        class="flex-1 justify-center"
                                        :disabled="processing"
                                        @click="forceDeleteUser"
                                    >
                                        Supprimer
                                    </DangerButton>
                                    <SecondaryButton class="flex-1 justify-center" @click="confirmAction = null">
                                        Annuler
                                    </SecondaryButton>
                                </div>
                            </div>
                        </template>

                        <!-- Actions pour utilisateur actif (non supprimé) -->
                        <template v-else>
                            <!-- Impersonate -->
                            <PrimaryButton
                                v-if="confirmAction !== 'impersonate'"
                                class="w-full justify-center bg-purple-600 hover:bg-purple-700"
                                @click="confirmAction = 'impersonate'"
                            >
                                Se connecter en tant que
                            </PrimaryButton>
                            <div v-else-if="confirmAction === 'impersonate'" class="rounded-lg bg-purple-500/10 p-3">
                                <p class="mb-2 text-sm text-purple-300">Confirmer l'impersonation ?</p>
                                <div class="flex gap-2">
                                    <PrimaryButton
                                        class="flex-1 justify-center bg-purple-600"
                                        :disabled="processing"
                                        @click="executeAction('impersonate', 'admin.users.impersonate')"
                                    >
                                        Oui
                                    </PrimaryButton>
                                    <SecondaryButton class="flex-1 justify-center" @click="confirmAction = null">
                                        Non
                                    </SecondaryButton>
                                </div>
                            </div>

                            <!-- Reset password -->
                            <SecondaryButton
                                v-if="confirmAction !== 'reset-password'"
                                class="w-full justify-center"
                                @click="confirmAction = 'reset-password'"
                            >
                                Réinitialiser mot de passe
                            </SecondaryButton>
                            <div v-else-if="confirmAction === 'reset-password'" class="rounded-lg bg-yellow-500/10 p-3">
                                <p class="mb-2 text-sm text-yellow-300">Réinitialiser le mot de passe ?</p>
                                <div class="flex gap-2">
                                    <PrimaryButton
                                        class="flex-1 justify-center bg-yellow-600"
                                        :disabled="processing"
                                        @click="executeAction('reset-password', 'admin.users.reset-password')"
                                    >
                                        Oui
                                    </PrimaryButton>
                                    <SecondaryButton class="flex-1 justify-center" @click="confirmAction = null">
                                        Non
                                    </SecondaryButton>
                                </div>
                            </div>

                            <!-- Reset 2FA -->
                            <SecondaryButton
                                v-if="user.two_factor_confirmed_at && confirmAction !== 'reset-2fa'"
                                class="w-full justify-center"
                                @click="confirmAction = 'reset-2fa'"
                            >
                                Réinitialiser 2FA
                            </SecondaryButton>
                            <div v-else-if="confirmAction === 'reset-2fa'" class="rounded-lg bg-yellow-500/10 p-3">
                                <p class="mb-2 text-sm text-yellow-300">Réinitialiser le 2FA ?</p>
                                <div class="flex gap-2">
                                    <PrimaryButton
                                        class="flex-1 justify-center bg-yellow-600"
                                        :disabled="processing"
                                        @click="executeAction('reset-2fa', 'admin.users.reset-2fa')"
                                    >
                                        Oui
                                    </PrimaryButton>
                                    <SecondaryButton class="flex-1 justify-center" @click="confirmAction = null">
                                        Non
                                    </SecondaryButton>
                                </div>
                            </div>

                            <!-- Toggle active -->
                            <DangerButton
                                v-if="user.is_active !== false && confirmAction !== 'toggle-active'"
                                class="w-full justify-center"
                                @click="confirmAction = 'toggle-active'"
                            >
                                Désactiver le compte
                            </DangerButton>
                            <PrimaryButton
                                v-else-if="user.is_active === false && confirmAction !== 'toggle-active'"
                                class="w-full justify-center bg-green-600 hover:bg-green-700"
                                @click="confirmAction = 'toggle-active'"
                            >
                                Réactiver le compte
                            </PrimaryButton>
                            <div v-else-if="confirmAction === 'toggle-active'" class="rounded-lg bg-red-500/10 p-3">
                                <p class="mb-2 text-sm text-red-300">
                                    {{ user.is_active === false ? 'Réactiver' : 'Désactiver' }} le compte ?
                                </p>
                                <div class="flex gap-2">
                                    <DangerButton
                                        class="flex-1 justify-center"
                                        :disabled="processing"
                                        @click="executeAction('toggle-active', 'admin.users.toggle-active')"
                                    >
                                        Oui
                                    </DangerButton>
                                    <SecondaryButton class="flex-1 justify-center" @click="confirmAction = null">
                                        Non
                                    </SecondaryButton>
                                </div>
                            </div>

                            <!-- Delete (Soft) -->
                            <DangerButton
                                v-if="confirmAction !== 'delete'"
                                class="w-full justify-center"
                                @click="confirmAction = 'delete'"
                            >
                                Supprimer le compte
                            </DangerButton>
                            <div v-else-if="confirmAction === 'delete'" class="rounded-lg bg-red-500/10 p-3">
                                <p class="mb-2 text-sm text-red-300">Le compte sera désactivé mais récupérable.</p>
                                <div class="flex gap-2">
                                    <DangerButton
                                        class="flex-1 justify-center"
                                        :disabled="processing"
                                        @click="deleteUser"
                                    >
                                        Supprimer
                                    </DangerButton>
                                    <SecondaryButton class="flex-1 justify-center" @click="confirmAction = null">
                                        Annuler
                                    </SecondaryButton>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
