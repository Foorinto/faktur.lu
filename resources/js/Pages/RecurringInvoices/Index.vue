<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BillingNav from '@/Components/BillingNav.vue';
import Pagination from '@/Components/Pagination.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    recurringInvoices: Object,
});

const frequencyLabels = {
    weekly: 'Hebdomadaire',
    monthly: 'Mensuelle',
    quarterly: 'Trimestrielle',
    yearly: 'Annuelle',
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
};

const formatAmount = (items) => {
    if (!items || items.length === 0) return '0,00 €';
    const total = items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(total);
};

const toggleActive = (recurring) => {
    router.post(route('recurring-invoices.toggle', recurring.id), {}, { preserveScroll: true });
};

const deleteRecurring = (recurring) => {
    if (confirm('Supprimer cette facturation récurrente ?')) {
        router.delete(route('recurring-invoices.destroy', recurring.id));
    }
};

const duplicateRecurring = (recurring) => {
    if (!confirm("Dupliquer cette récurrence ? La copie sera créée inactive — vous devrez l'activer manuellement après vérification.")) return;
    router.post(route('recurring-invoices.duplicate', recurring.id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head :title="t('recurring_invoices')" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">
                {{ t('recurring_invoices') }}
            </h1>
        </template>
        <template #header-actions>
            <Link
                :href="route('recurring-invoices.create')"
                class="inline-flex items-center rounded-xl bg-accent-rose px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-pink-500 transition-colors"
            >
                <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                </svg>
                Nouvelle récurrence
            </Link>
        </template>

        <BillingNav class="mb-6" />

        <!-- How it works -->
        <details class="mb-6 group rounded-2xl border border-blue-200 bg-blue-50 dark:border-blue-900 dark:bg-blue-950/30">
            <summary class="flex items-center gap-2 cursor-pointer list-none px-4 py-3 text-sm font-semibold text-blue-900 dark:text-blue-200 select-none">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="flex-1">Comment fonctionnent les facturations récurrentes ?</span>
                <svg class="h-4 w-4 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </summary>
            <div class="px-4 pb-4 pt-1 text-sm text-blue-900/90 dark:text-blue-200/90 space-y-3">
                <p>Une récurrence est un <strong>modèle</strong> qui génère automatiquement de nouvelles factures à intervalles réguliers (hebdomadaire, mensuel, trimestriel ou annuel).</p>
                <p>Chaque jour à <strong>06:00</strong>, le système vérifie les récurrences <strong>actives</strong> dont la prochaine date est arrivée et crée la facture correspondante. Selon les options choisies :</p>
                <ul class="list-disc pl-5 space-y-1">
                    <li><strong>Aucune option</strong> → la facture est créée en <strong>brouillon</strong> ; vous la finalisez et l'envoyez manuellement.</li>
                    <li><strong>Finalisation auto</strong> → la facture est <strong>finalisée</strong> automatiquement (numéro attribué) ; il ne reste qu'à l'envoyer.</li>
                    <li><strong>Finalisation auto + envoi auto</strong> → la facture est <strong>envoyée par email</strong> au client sans intervention.</li>
                </ul>
                <p>La date de la prochaine facture s'incrémente automatiquement après chaque génération. Si vous fixez une date de fin, la récurrence se désactive d'elle-même une fois passée.</p>
                <p class="text-xs text-blue-800/80 dark:text-blue-300/80">💡 Astuce : modifier le modèle n'affecte que les <em>prochaines</em> factures — celles déjà émises restent inchangées. Vous pouvez désactiver/réactiver une récurrence à tout moment en cliquant sur son badge de statut.</p>
            </div>
        </details>

        <!-- Empty state -->
        <div v-if="recurringInvoices.data.length === 0" class="bg-white dark:bg-surface-dark rounded-2xl border border-gray-200 dark:border-gray-700 p-12 text-center">
            <div class="mx-auto w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.678 48.678 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3l-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 003.7 3.7 48.656 48.656 0 007.324 0 4.006 4.006 0 003.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3l-3 3" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Aucune facturation récurrente</h3>
            <p class="text-slate-500 dark:text-slate-400 mb-6">Créez votre première récurrence pour automatiser vos factures.</p>
            <Link
                :href="route('recurring-invoices.create')"
                class="inline-flex items-center gap-2 bg-primary-500 hover:bg-primary-600 text-white font-medium px-5 py-2.5 rounded-xl transition-colors text-sm"
            >
                Créer une récurrence
            </Link>
        </div>

        <!-- Table -->
        <div v-else class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-surface-card">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-gray-800">
                    <tr>
                        <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 dark:text-white sm:pl-6">Client</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white">Titre</th>
                        <th class="hidden px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white md:table-cell">Fréquence</th>
                        <th class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900 dark:text-white">Montant HT</th>
                        <th class="hidden px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white lg:table-cell">Prochaine</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white">Statut</th>
                        <th class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-surface-card">
                    <tr v-for="recurring in recurringInvoices.data" :key="recurring.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 dark:text-white sm:pl-6">
                            {{ recurring.client?.name }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500 dark:text-slate-400">
                            {{ recurring.title || '-' }}
                        </td>
                        <td class="hidden whitespace-nowrap px-3 py-4 text-sm text-slate-500 dark:text-slate-400 md:table-cell">
                            {{ frequencyLabels[recurring.frequency] }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-right text-sm font-semibold text-slate-900 dark:text-white">
                            {{ formatAmount(recurring.items) }}
                        </td>
                        <td class="hidden whitespace-nowrap px-3 py-4 text-sm text-slate-500 dark:text-slate-400 lg:table-cell">
                            {{ formatDate(recurring.next_invoice_date) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <button
                                @click="toggleActive(recurring)"
                                :class="[
                                    'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium transition-colors',
                                    recurring.is_active
                                        ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400'
                                        : 'bg-slate-100 text-slate-500 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400'
                                ]"
                            >
                                <span :class="['w-1.5 h-1.5 rounded-full', recurring.is_active ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                                {{ recurring.is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            <div class="flex items-center justify-end gap-2">
                                <Link
                                    :href="route('recurring-invoices.edit', recurring.id)"
                                    class="text-slate-400 hover:text-primary-500 transition-colors"
                                    title="Modifier"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </Link>
                                <button
                                    @click="duplicateRecurring(recurring)"
                                    class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors"
                                    title="Dupliquer (créera une copie inactive)"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
                                    </svg>
                                </button>
                                <button
                                    @click="deleteRecurring(recurring)"
                                    class="text-slate-400 hover:text-red-500 transition-colors"
                                    title="Supprimer"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

        <div v-if="recurringInvoices.last_page > 1" class="mt-6 flex items-center justify-between">
            <Pagination :links="recurringInvoices.links" />
        </div>
        </div>
    </AppLayout>
</template>
