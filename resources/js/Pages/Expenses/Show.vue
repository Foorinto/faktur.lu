<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    expense: Object,
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const deleteExpense = () => {
    if (confirm(t('delete_expense').replace(':name', props.expense.provider_name))) {
        router.delete(route('expenses.destroy', props.expense.id));
    }
};
</script>

<template>
    <Head :title="expense.provider_name || t('expense')" />

    <AppLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('expenses.index')"
                    class="flex items-center justify-center rounded-lg p-1.5 text-slate-400 hover:bg-gray-100 hover:text-slate-600 dark:hover:bg-gray-800 dark:hover:text-slate-300 transition-colors"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </Link>
                <h1 class="text-xl font-semibold text-slate-900 dark:text-white truncate">
                    {{ expense.provider_name || t('expense') }}
                </h1>
            </div>
        </template>

        <template #header-actions>
            <Link
                :href="route('expenses.edit', expense.id)"
                class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300 dark:hover:bg-gray-700 transition-colors"
            >
                <svg class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                </svg>
                {{ t('edit') }}
            </Link>
            <button
                type="button"
                class="inline-flex items-center rounded-xl border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-600 shadow-sm hover:bg-red-50 dark:border-red-700 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-red-900/20 transition-colors"
                @click="deleteExpense"
            >
                <svg class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
                {{ t('delete') }}
            </button>
        </template>

        <div class="space-y-6">
            <!-- Main info card -->
            <div class="rounded-2xl bg-white shadow dark:bg-surface-card">
                <!-- Amount banner -->
                <div class="border-b border-gray-100 dark:border-gray-700 px-6 py-5">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="text-sm text-slate-500 dark:text-slate-400">{{ t('total_ttc') }}</p>
                            <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ formatCurrency(expense.amount_ttc) }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center rounded-lg bg-primary-50 px-3 py-1.5 text-sm font-medium text-primary-700 dark:bg-primary-900/30 dark:text-primary-400">
                                {{ expense.category_label }}
                            </span>
                            <span
                                v-if="expense.is_deductible"
                                class="inline-flex items-center rounded-lg bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"
                            >
                                {{ t('deductible') }}
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400"
                            >
                                {{ t('non_deductible') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Details grid -->
                <div class="grid grid-cols-1 gap-px bg-gray-100 dark:bg-gray-700 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Date -->
                    <div class="bg-white dark:bg-surface-card px-6 py-4">
                        <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('date') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ formatDate(expense.date) }}</dd>
                    </div>

                    <!-- Supplier -->
                    <div class="bg-white dark:bg-surface-card px-6 py-4">
                        <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('supplier') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 dark:text-white">
                            {{ expense.provider_name || '-' }}
                            <span v-if="expense.supplier_country_name" class="text-slate-500 dark:text-slate-400">
                                ({{ expense.supplier_country_name }})
                            </span>
                        </dd>
                    </div>

                    <!-- Régime de TVA -->
                    <div class="bg-white dark:bg-surface-card px-6 py-4">
                        <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('expense_vat_regime') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ expense.vat_regime_label || '-' }}</dd>
                    </div>

                    <!-- Payment method -->
                    <div class="bg-white dark:bg-surface-card px-6 py-4">
                        <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('payment_method') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ expense.payment_method_label || '-' }}</dd>
                    </div>

                    <!-- Amount HT -->
                    <div class="bg-white dark:bg-surface-card px-6 py-4">
                        <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('amount_ht') }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ formatCurrency(expense.amount_ht) }}</dd>
                    </div>

                    <!-- VAT -->
                    <div class="bg-white dark:bg-surface-card px-6 py-4">
                        <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('vat') }} ({{ expense.vat_rate }}%)</dt>
                        <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ formatCurrency(expense.amount_vat) }}</dd>
                        <!-- Rappelé sur la fiche, et pas seulement à la saisie :
                             c'est ici qu'on revient chercher pourquoi ce montant
                             ne figure pas dans la TVA déductible. -->
                        <dd
                            v-if="expense.vat_regime === 'foreign_vat'"
                            class="mt-1 text-xs text-amber-700 dark:text-amber-500"
                        >
                            {{ t('expense_foreign_vat_short') }}
                        </dd>
                        <!-- La TVA autoliquidée n'apparaît nulle part ailleurs
                             sur la fiche : elle ne fait partie ni du HT ni du
                             TTC, et c'est pourtant elle qui part dans la
                             déclaration. -->
                        <dd
                            v-if="expense.vat_regime === 'reverse_charge' && parseFloat(expense.reverse_charge_vat) > 0"
                            class="mt-1 text-xs text-sky-700 dark:text-sky-400"
                        >
                            {{ t('expense_reverse_charge_short', {
                                amount: formatCurrency(expense.reverse_charge_vat),
                                rate: parseFloat(expense.reverse_charge_vat_rate),
                            }) }}
                        </dd>
                    </div>

                    <!-- Reference -->
                    <div class="bg-white dark:bg-surface-card px-6 py-4">
                        <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('reference') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ expense.reference || '-' }}</dd>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div v-if="expense.description" class="rounded-2xl bg-white shadow dark:bg-surface-card px-6 py-5">
                <h3 class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">{{ t('description') }}</h3>
                <p class="text-sm text-slate-900 dark:text-white whitespace-pre-line">{{ expense.description }}</p>
            </div>

            <!-- Attachment -->
            <div v-if="expense.attachment_url" class="rounded-2xl bg-white shadow dark:bg-surface-card px-6 py-5">
                <h3 class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-3">{{ t('receipt') }}</h3>
                <a
                    :href="expense.attachment_url"
                    target="_blank"
                    class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-slate-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300 dark:hover:bg-gray-700 transition-colors"
                >
                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                    </svg>
                    {{ expense.attachment_filename }}
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg>
                </a>
            </div>
        </div>
    </AppLayout>
</template>
