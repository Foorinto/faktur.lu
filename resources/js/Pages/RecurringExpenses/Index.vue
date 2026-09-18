<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import RowAction from '@/Components/RowAction.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

defineProps({
    charges: { type: Array, default: () => [] },
    monthlyTotal: { type: Number, default: 0 },
});

const formatCurrency = (v) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(v || 0);

const formatDate = (d) => (d ? new Date(d).toLocaleDateString('fr-FR') : '—');

const toggle = (charge) => {
    router.post(route('recurring-expenses.toggle', charge.id), {}, { preserveScroll: true });
};

const supprimer = (charge) => {
    if (!window.confirm(t('recurring_expenses.confirm_delete'))) return;

    router.delete(route('recurring-expenses.destroy', charge.id), { preserveScroll: true });
};
</script>

<template>
    <Head :title="t('recurring_expenses.title')" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">{{ t('recurring_expenses.title') }}</h1>
        </template>
        <template #header-actions>
            <Link
                :href="route('expenses.index')"
                class="mr-2 inline-flex items-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300 dark:hover:bg-gray-800"
            >
                <svg class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                {{ t('expenses') }}
            </Link>
            <Link :href="route('recurring-expenses.create')">
                <PrimaryButton>{{ t('recurring_expenses.create_submit') }}</PrimaryButton>
            </Link>
        </template>

        <div class="space-y-6">
            <div class="rounded-2xl bg-white p-4 shadow dark:bg-surface-card">
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ t('recurring_expenses.monthly_total') }}</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white">{{ formatCurrency(monthlyTotal) }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ t('recurring_expenses.monthly_total_hint') }}</p>
            </div>

            <div class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-surface-card">
                <table v-if="charges.length > 0" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-slate-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('recurring_expenses.label') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('recurring_expenses.frequency') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('recurring_expenses.next_date') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('amount') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr v-for="c in charges" :key="c.id" :class="{ 'opacity-60': !c.is_active }">
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">{{ c.label }}</span>
                                    <span
                                        v-if="!c.is_active"
                                        class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 dark:bg-gray-800 dark:text-slate-300"
                                    >
                                        {{ t('recurring_expenses.suspended') }}
                                    </span>
                                </div>
                                <div class="text-xs text-slate-400">{{ c.provider_name }} · {{ c.category_label }}</div>
                            </td>
                            <td class="px-6 py-3 text-sm text-slate-600 dark:text-slate-300">
                                {{ t(`recurring_expenses.frequencies.${c.frequency}`) }}
                            </td>
                            <td class="px-6 py-3 text-sm text-slate-600 dark:text-slate-300">
                                {{ formatDate(c.next_expense_date) }}
                                <div v-if="c.ends_at" class="text-xs text-slate-400">
                                    {{ t('recurring_expenses.until', { date: formatDate(c.ends_at) }) }}
                                </div>
                            </td>
                            <td class="px-6 py-3 text-right text-sm font-mono tabular-nums text-slate-900 dark:text-white">
                                {{ formatCurrency(c.amount_ht) }}
                                <span class="text-xs font-sans text-slate-400">{{ t('amount_ht') }}</span>
                                <div class="text-xs font-mono text-slate-500 dark:text-slate-400">
                                    {{ formatCurrency(c.amount_ttc) }}
                                    <span class="font-sans text-slate-400">{{ t('amount_ttc') }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-right text-sm">
                                <div class="flex items-center justify-end gap-1">
                                    <RowAction
                                        :icon="c.is_active ? 'suspend' : 'resume'"
                                        :label="c.is_active ? t('recurring_expenses.suspend') : t('recurring_expenses.resume')"
                                        @click="toggle(c)"
                                    />
                                    <RowAction
                                        icon="edit"
                                        tone="primary"
                                        :label="t('edit')"
                                        :href="route('recurring-expenses.edit', c.id)"
                                    />
                                    <RowAction
                                        icon="delete"
                                        tone="danger"
                                        :label="t('delete')"
                                        @click="supprimer(c)"
                                    />
                                </div>
                                <div v-if="c.expenses_generated > 0" class="mt-1 text-xs text-slate-400">
                                    {{ t('recurring_expenses.generated_count', { count: c.expenses_generated }) }}
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-else class="px-6 py-12 text-center">
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ t('recurring_expenses.empty') }}</p>
                    <Link
                        :href="route('recurring-expenses.create')"
                        class="mt-2 inline-block text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400"
                    >
                        {{ t('recurring_expenses.empty_cta') }}
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
