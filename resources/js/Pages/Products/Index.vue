<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    products: { type: Object, required: true },
    canCreate: { type: Boolean, default: true },
    quota: { type: Object, default: () => ({ limit: null, used: 0 }) },
    units: { type: Array, default: () => [] },
});

const unitLabel = (value) => props.units.find((u) => u.value === value)?.label ?? value;

const quotaLabel = computed(() => {
    if (props.quota.limit === null || props.quota.limit === undefined) {
        return t('products.quota_unlimited');
    }
    return t('products.quota_used', { used: props.quota.used, limit: props.quota.limit });
});

const formatPrice = (value) =>
    new Intl.NumberFormat('fr-LU', { style: 'currency', currency: 'EUR' }).format(Number(value || 0));

const destroy = (product) => {
    if (window.confirm(t('products.confirm_delete', { name: product.designation }))) {
        router.delete(route('products.destroy', product.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head :title="t('products.title')" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900 dark:text-white">{{ t('products.title') }}</h1>
                    <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ quotaLabel }}</p>
                </div>

                <Link
                    v-if="canCreate"
                    :href="route('products.create')"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700"
                >
                    + {{ t('products.new') }}
                </Link>
                <Link
                    v-else
                    :href="route('subscription.index')"
                    class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-600"
                    :title="t('products.limit_reached')"
                >
                    ⚡ {{ t('products.upgrade') }}
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-5xl">
            <!-- Empty -->
            <div
                v-if="products.data.length === 0"
                class="rounded-2xl border border-dashed border-gray-200 bg-white p-12 text-center dark:border-gray-800 dark:bg-gray-900"
            >
                <p class="text-lg font-medium text-slate-900 dark:text-white">{{ t('products.empty_title') }}</p>
                <p class="mx-auto mt-1 max-w-md text-sm text-slate-500 dark:text-slate-400">{{ t('products.empty_desc') }}</p>
                <Link
                    v-if="canCreate"
                    :href="route('products.create')"
                    class="mt-5 inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700"
                >
                    + {{ t('products.new') }}
                </Link>
            </div>

            <!-- List -->
            <div v-else class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-gray-800/50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                <th class="px-4 py-3">{{ t('products.designation') }}</th>
                                <th class="px-4 py-3">{{ t('products.reference') }}</th>
                                <th class="px-4 py-3 text-right">{{ t('products.unit_price_ht') }}</th>
                                <th class="px-4 py-3 text-right">{{ t('products.vat_rate') }}</th>
                                <th class="px-4 py-3">{{ t('products.unit') }}</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800/60">
                            <tr v-for="product in products.data" :key="product.id" class="text-sm text-slate-700 dark:text-slate-300">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2 font-medium text-slate-900 dark:text-white">
                                        {{ product.designation }}
                                        <span v-if="!product.is_active" class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-normal text-slate-500 dark:bg-gray-800">
                                            {{ t('products.inactive') }}
                                        </span>
                                    </div>
                                    <p v-if="product.description" class="mt-0.5 line-clamp-1 text-xs text-slate-400">{{ product.description }}</p>
                                </td>
                                <td class="px-4 py-3 text-slate-500">{{ product.reference || '—' }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ formatPrice(product.unit_price_ht) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ Number(product.vat_rate) }}%</td>
                                <td class="px-4 py-3 text-slate-500">{{ unitLabel(product.unit) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-3">
                                        <Link :href="route('products.edit', product.id)" class="text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                            {{ t('edit') }}
                                        </Link>
                                        <button type="button" class="text-red-500 hover:text-red-600" @click="destroy(product)">
                                            {{ t('delete') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <Pagination v-if="products.data.length" :links="products.links" class="mt-6" />
        </div>
    </AppLayout>
</template>
