<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';
import { computed, ref } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import RowAction from '@/Components/RowAction.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const { t } = useTranslations();

const props = defineProps({
    product: { type: Object, required: true },
    variants: { type: Array, default: () => [] },
    movements: { type: Array, default: () => [] },
    unvalued_count: { type: Number, default: 0 },
});

const today = new Date().toISOString().split('T')[0];
const inputClass = 'mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white';

// Corriger un mouvement saisi à la main (FEAT-128) : quantité, coût, date,
// note. La quantité d'un ajustement vient d'un comptage : elle ne se retouche
// pas ici, on refait un inventaire.
const editing = ref(null);
const editForm = useForm(() => ({ quantity: null, unit_cost: null, date: today, note: '' }));
const editingIsEntry = computed(() => editing.value?.type === 'entree');

const openEdit = (movement) => {
    editing.value = movement;
    editForm.clearErrors();
    // Le serveur renvoie la quantité en décimal à quatre chiffres (« 5.0000 ») :
    // on la remet en nombre pour que le champ affiche « 5 ».
    editForm.quantity = Number(movement.quantity);
    editForm.unit_cost = movement.unit_cost;
    editForm.date = movement.date;
    editForm.note = movement.note ?? '';
};

const submitEdit = () => {
    editForm.put(route('stock.movements.update', [editing.value.product_id ?? props.product.id, editing.value.id]), {
        preserveScroll: true,
        onSuccess: () => { editing.value = null; },
    });
};

// Valoriser d'un coup les entrées manuelles sans coût de cet article et de
// ses déclinaisons.
const valuing = ref(false);
const valueForm = useForm(() => ({ product_ids: [], unit_cost: null }));

const openValue = () => {
    valueForm.clearErrors();
    valueForm.unit_cost = null;
    // La fiche d'une famille couvre ses déclinaisons suivies ; celle d'une
    // déclinaison ne couvre qu'elle. Le serveur n'ajoute rien.
    valueForm.product_ids = [props.product.id, ...props.variants.map((v) => v.id)];
    valuing.value = true;
};

const submitValue = () => {
    valueForm.post(route('stock.value'), {
        preserveScroll: true,
        onSuccess: () => { valuing.value = false; },
    });
};

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
            <button
            v-if="unvalued_count > 0"
            type="button"
            class="mr-3 rounded-xl border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-800 hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200"
            @click="openValue"
        >
            {{ t('stock.value_entries') }} ({{ t('stock.unvalued_entries', { count: unvalued_count }) }})
        </button>
        <Link :href="route('stock.index')" class="text-slate-400 hover:text-slate-500 dark:text-slate-500">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                </svg>
            </Link>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('stock.record_of', { name: product.designation }) }}
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
                                icon="edit"
                                :label="t('stock.edit_movement')"
                                @click="openEdit(m)"
                            />
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

        <!-- Modale : corriger un mouvement (FEAT-128) -->
        <Modal :show="editing !== null" @close="editing = null">
            <div v-if="editing" class="p-6">
                <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('stock.edit_movement_title') }}</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ editing.product_name }} · {{ typeLabel(editing.type) }}</p>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div v-if="editingIsEntry">
                        <InputLabel for="edit_quantity" :value="t('stock.quantity')" />
                        <input id="edit_quantity" v-model="editForm.quantity" type="number" step="0.01" min="0.01" :class="inputClass" />
                        <InputError :message="editForm.errors.quantity" class="mt-1" />
                    </div>
                    <div v-if="editingIsEntry">
                        <InputLabel for="edit_unit_cost" :value="t('stock.unit_cost')" />
                        <input id="edit_unit_cost" v-model="editForm.unit_cost" type="number" step="0.01" min="0" :class="inputClass" />
                        <InputError :message="editForm.errors.unit_cost" class="mt-1" />
                    </div>
                    <p v-else class="text-xs text-slate-500 dark:text-slate-400 sm:col-span-2">
                        {{ t('stock.edit_adjustment_note') }}
                    </p>
                    <div>
                        <InputLabel for="edit_date" :value="t('date')" />
                        <input id="edit_date" v-model="editForm.date" type="date" :class="inputClass" />
                        <InputError :message="editForm.errors.date" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="edit_note" :value="t('stock.note')" />
                        <input id="edit_note" v-model="editForm.note" type="text" maxlength="255" :class="inputClass" />
                        <InputError :message="editForm.errors.note" class="mt-1" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="editing = null">{{ t('cancel') }}</SecondaryButton>
                    <PrimaryButton :disabled="editForm.processing" @click="submitEdit">{{ t('save') }}</PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Modale : valoriser les entrées sans coût (FEAT-128) -->
        <Modal :show="valuing" @close="valuing = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('stock.value_entries_title') }}</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ t('stock.value_entries_help', { count: 1 + variants.length }) }}</p>
                <div class="mt-4">
                    <InputLabel for="value_unit_cost" :value="t('stock.unit_cost')" />
                    <input id="value_unit_cost" v-model="valueForm.unit_cost" type="number" step="0.01" min="0" :class="inputClass" />
                    <InputError :message="valueForm.errors.unit_cost" class="mt-1" />
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="valuing = false">{{ t('cancel') }}</SecondaryButton>
                    <PrimaryButton :disabled="valueForm.processing" @click="submitValue">{{ t('stock.value_entries_submit') }}</PrimaryButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
