<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

defineProps({
    product: { type: Object, required: true },
    movements: { type: Array, default: () => [] },
});

const formatQty = (v) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 4, signDisplay: 'exceptZero' }).format(v || 0);
const typeLabel = (type) => t('stock.types.' + type);
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

        <div class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-surface-card">
            <table v-if="movements.length > 0" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-slate-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('date') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('stock.movement_type') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('stock.quantity') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('stock.note') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <tr v-for="m in movements" :key="m.id">
                        <td class="px-6 py-3 text-sm text-slate-900 dark:text-white">{{ m.date }}</td>
                        <td class="px-6 py-3 text-sm text-slate-600 dark:text-slate-300">{{ typeLabel(m.type) }}</td>
                        <td class="px-6 py-3 text-right text-sm font-mono tabular-nums"
                            :class="Number(m.quantity) < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'">
                            {{ formatQty(m.quantity) }}
                        </td>
                        <td class="px-6 py-3 text-sm text-slate-500 dark:text-slate-400">{{ m.note || '—' }}</td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="px-6 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                {{ t('stock.no_movements') }}
            </div>
        </div>
    </AppLayout>
</template>
