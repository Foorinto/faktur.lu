<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { computed } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

/**
 * Éditeur de ventilation d'une dépense (FEAT-115).
 *
 * Chaque ligne porte sa catégorie, son montant HT et son taux de TVA. Le régime
 * de TVA reste au niveau de la dépense (composant ExpenseVatFields) : c'est une
 * propriété du fournisseur, pas de la nature de l'achat. Les lignes se saisissent
 * en HT ; le TTC est affiché en direct, à titre indicatif.
 */
const props = defineProps({
    form: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
});

const formatCurrency = (amount) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount || 0);

const lineTtc = (line) => {
    const ht = parseFloat(line.amount_ht) || 0;
    const rate = parseFloat(line.vat_rate) || 0;

    return ht * (1 + rate / 100);
};

const totalHt = computed(() =>
    props.form.lines.reduce((sum, line) => sum + (parseFloat(line.amount_ht) || 0), 0)
);

const totalTtc = computed(() =>
    props.form.lines.reduce((sum, line) => sum + lineTtc(line), 0)
);

const addLine = () => {
    props.form.lines.push({ category: '', description: '', amount_ht: '', vat_rate: 17 });
};

const removeLine = (index) => {
    props.form.lines.splice(index, 1);

    // On ne descend jamais sous une ligne : une ventilation vide n'a pas de sens.
    if (props.form.lines.length === 0) {
        addLine();
    }
};

// Erreur de validation d'un champ de ligne (ex. « lines.0.amount_ht »).
const lineError = (index, field) => props.form.errors?.[`lines.${index}.${field}`];
</script>

<template>
    <div class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-surface-card">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('expense_ventilation') }}</h2>
        </div>

        <div class="px-6 py-4">
            <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
                {{ t('expense_ventilation_help') }}
            </p>

            <div class="space-y-3">
                <div
                    v-for="(line, index) in form.lines"
                    :key="index"
                    class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 p-3 dark:border-gray-700 sm:grid-cols-12 sm:items-start"
                >
                    <div class="sm:col-span-5">
                        <InputLabel :for="`line_category_${index}`" :value="t('category')" class="sm:sr-only" />
                        <select
                            :id="`line_category_${index}`"
                            v-model="line.category"
                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            required
                        >
                            <option value="">{{ t('select_category') }}</option>
                            <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                                {{ cat.label }}
                            </option>
                        </select>
                        <InputError :message="lineError(index, 'category')" class="mt-1" />
                    </div>

                    <div class="sm:col-span-3">
                        <InputLabel :for="`line_ht_${index}`" :value="t('amount_ht')" class="sm:sr-only" />
                        <div class="relative mt-1">
                            <input
                                :id="`line_ht_${index}`"
                                v-model="line.amount_ht"
                                type="number"
                                step="0.01"
                                min="0.01"
                                class="block w-full rounded-xl border-gray-300 pr-12 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                placeholder="0.00"
                                required
                            />
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                <span class="text-slate-500 dark:text-slate-400">EUR</span>
                            </div>
                        </div>
                        <InputError :message="lineError(index, 'amount_ht')" class="mt-1" />
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel :for="`line_vat_${index}`" :value="t('vat_rate_label')" class="sm:sr-only" />
                        <div class="relative mt-1">
                            <input
                                :id="`line_vat_${index}`"
                                v-model.number="line.vat_rate"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                class="block w-full rounded-xl border-gray-300 pr-8 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                placeholder="17"
                                required
                            />
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                <span class="text-slate-500 dark:text-slate-400">%</span>
                            </div>
                        </div>
                        <InputError :message="lineError(index, 'vat_rate')" class="mt-1" />
                    </div>

                    <div class="flex items-center justify-between gap-2 sm:col-span-2 sm:justify-end sm:pt-2">
                        <span class="text-sm tabular-nums text-slate-500 dark:text-slate-400">
                            {{ formatCurrency(lineTtc(line)) }}
                        </span>
                        <button
                            type="button"
                            :title="t('remove')"
                            :aria-label="t('remove')"
                            class="rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20"
                            @click="removeLine(index)"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-xl border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300 dark:hover:bg-gray-700"
                    @click="addLine"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ t('expense_ventilation_add_line') }}
                </button>

                <div class="rounded-xl bg-slate-50 px-4 py-2 text-sm dark:bg-gray-800">
                    <span class="text-slate-500 dark:text-slate-400">{{ t('amount_ht') }} :</span>
                    <span class="ml-1 font-semibold text-slate-900 dark:text-white">{{ formatCurrency(totalHt) }}</span>
                    <span class="ml-3 text-slate-500 dark:text-slate-400">{{ t('ttc') }} :</span>
                    <span class="ml-1 font-semibold text-slate-900 dark:text-white">{{ formatCurrency(totalTtc) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
