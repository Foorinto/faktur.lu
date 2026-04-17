<script setup>
import { router } from '@inertiajs/vue3';
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
</script>

<template>
    <AppLayout>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
            <!-- Billing Nav -->
            <div class="mb-6">
                <BillingNav />
            </div>

            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Facturation récurrente</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Automatisez vos factures mensuelles, trimestrielles ou annuelles</p>
                </div>
                <a
                    :href="route('recurring-invoices.create')"
                    class="inline-flex items-center gap-2 bg-primary-500 hover:bg-primary-600 text-white font-medium px-4 py-2.5 rounded-xl transition-colors text-sm"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Nouvelle récurrence
                </a>
            </div>

            <!-- Empty state -->
            <div v-if="recurringInvoices.data.length === 0" class="bg-white dark:bg-surface-dark rounded-2xl border border-gray-200 dark:border-gray-700 p-12 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.678 48.678 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3l-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 003.7 3.7 48.656 48.656 0 007.324 0 4.006 4.006 0 003.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3l-3 3" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Aucune facturation récurrente</h3>
                <p class="text-slate-500 dark:text-slate-400 mb-6">Créez votre première récurrence pour automatiser vos factures.</p>
                <a
                    :href="route('recurring-invoices.create')"
                    class="inline-flex items-center gap-2 bg-primary-500 hover:bg-primary-600 text-white font-medium px-5 py-2.5 rounded-xl transition-colors text-sm"
                >
                    Créer une récurrence
                </a>
            </div>

            <!-- Table -->
            <div v-else class="bg-white dark:bg-surface-dark rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Titre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Fréquence</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Montant HT</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Prochaine</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Statut</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="recurring in recurringInvoices.data" :key="recurring.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-6 py-4 text-sm font-medium text-slate-900 dark:text-white">
                                {{ recurring.client?.name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">
                                {{ recurring.title || '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">
                                {{ frequencyLabels[recurring.frequency] }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-slate-900 dark:text-white">
                                {{ formatAmount(recurring.items) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">
                                {{ formatDate(recurring.next_invoice_date) }}
                            </td>
                            <td class="px-6 py-4">
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
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a
                                        :href="route('recurring-invoices.edit', recurring.id)"
                                        class="text-slate-400 hover:text-primary-500 transition-colors"
                                        title="Modifier"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
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

                <div v-if="recurringInvoices.last_page > 1" class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                    <Pagination :links="recurringInvoices.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
