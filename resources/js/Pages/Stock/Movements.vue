<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';
import { computed, ref } from 'vue';
import RowAction from '@/Components/RowAction.vue';

const { t } = useTranslations();

const props = defineProps({
    product: { type: Object, required: true },
    variants: { type: Array, default: () => [] },
    movements: { type: Array, default: () => [] },
});

// Sur une famille, on regarde souvent une seule nuance : le filtre évite de
// remonter quarante-cinq historiques mêlés.
const filtre = ref(null);

const mouvementsAffiches = computed(() =>
    filtre.value === null ? props.movements : props.movements.filter((m) => m.product_id === filtre.value)
);

// Ce qui reste sur la famille elle-même, non rattaché à une déclinaison.
const nonVentile = computed(() => props.product.current_stock);

const formatQty = (v) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 4, signDisplay: 'exceptZero' }).format(v || 0);
const formatCurrency = (v) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(v || 0);
// Les mouvements se lisent signés (+12, -3), un état de stock non.
const formatTotal = (v) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 4 }).format(v || 0);
const typeLabel = (type) => t('stock.types.' + type);

const deleteMovement = (movement) => {
    if (!confirm(t('stock.confirm_delete_movement'))) return;

    // La suppression vise l'article du mouvement, pas la famille affichée.
    router.delete(route('stock.movements.destroy', [movement.product_id ?? props.product.id, movement.id]), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="product.designation" />

    <AppLayout>
        <template #header>
            <Link :href="route('stock.index')" class="text-slate-400 hover:text-slate-500 dark:text-slate-500">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                </svg>
            </Link>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('stock.history_of', { name: product.designation }) }}
            </h1>
        </template>

        <!-- Le détail par déclinaison : où se trouve la marchandise avant de
             regarder par quels mouvements elle y est arrivée. -->
        <div v-if="variants.length > 0" class="mb-4 overflow-hidden rounded-2xl bg-white shadow dark:bg-surface-card">
            <div class="flex flex-wrap items-baseline gap-x-6 gap-y-1 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                <div>
                    <p class="text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('stock.current') }}</p>
                    <p class="mt-0.5 text-2xl font-semibold tabular-nums text-slate-900 dark:text-white">{{ formatTotal(product.total_stock) }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('stock.value') }}</p>
                    <p class="mt-0.5 text-2xl font-semibold tabular-nums text-slate-900 dark:text-white">{{ formatCurrency(product.total_value) }}</p>
                </div>
                <p v-if="nonVentile > 0" class="text-sm text-slate-500 dark:text-slate-400">
                    {{ t('stock.unallocated', { quantity: formatTotal(nonVentile) }) }}
                </p>
            </div>

            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-slate-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('products.variants_title') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('stock.current') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('stock.value') }}</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <tr
                        v-for="v in variants"
                        :key="v.id"
                        class="cursor-pointer transition hover:bg-slate-50 dark:hover:bg-gray-800/50"
                        :class="filtre === v.id ? 'bg-primary-50 dark:bg-primary-900/20' : ''"
                        @click="filtre = filtre === v.id ? null : v.id"
                    >
                        <td class="px-6 py-3">
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ v.designation }}</p>
                            <p v-if="v.reference" class="text-xs text-slate-400">{{ v.reference }}</p>
                        </td>
                        <td class="px-6 py-3 text-right text-sm font-mono tabular-nums"
                            :class="v.is_low ? 'font-semibold text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white'">
                            {{ formatTotal(v.current_stock) }}
                        </td>
                        <td class="px-6 py-3 text-right text-sm font-mono tabular-nums text-slate-900 dark:text-white">
                            {{ formatCurrency(v.stock_value) }}
                        </td>
                        <td class="px-6 py-3 text-right text-xs text-slate-400">
                            {{ filtre === v.id ? t('stock.show_all_movements') : t('stock.filter_movements') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-surface-card">
            <table v-if="mouvementsAffiches.length > 0" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-slate-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('date') }}</th>
                        <th v-if="variants.length > 0" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('stock.product') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('stock.movement_type') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('stock.quantity') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('stock.unit_cost') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('stock.note') }}</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <tr v-for="m in mouvementsAffiches" :key="m.id">
                        <td class="px-6 py-3 text-sm text-slate-900 dark:text-white">{{ m.date }}</td>
                        <td v-if="variants.length > 0" class="px-6 py-3 text-sm text-slate-600 dark:text-slate-300">{{ m.product_name }}</td>
                        <td class="px-6 py-3 text-sm text-slate-600 dark:text-slate-300">{{ typeLabel(m.type) }}</td>
                        <td class="px-6 py-3 text-right text-sm font-mono tabular-nums"
                            :class="Number(m.quantity) < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'">
                            {{ formatQty(m.quantity) }}
                        </td>
                        <td class="px-6 py-3 text-right text-sm font-mono tabular-nums text-slate-500 dark:text-slate-400">
                            {{ m.unit_cost !== null ? formatCurrency(m.unit_cost) : '—' }}
                        </td>
                        <td class="px-6 py-3 text-sm text-slate-500 dark:text-slate-400">{{ m.note || '—' }}</td>
                        <td class="px-6 py-3 text-right">
                            <RowAction
                                v-if="m.is_manual"
                                icon="delete"
                                tone="danger"
                                :label="t('stock.delete_movement')"
                                @click="deleteMovement(m)"
                            />
                            <span v-else class="text-xs text-slate-300 dark:text-slate-600" :title="t('stock.locked_movement')">&#128274;</span>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="px-6 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                {{ t('stock.no_movements') }}
            </div>
        </div>
    </AppLayout>
</template>
