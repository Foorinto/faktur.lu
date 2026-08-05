<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    form: { type: Object, required: true },
    units: { type: Array, default: () => [] },
    vatRates: { type: Array, default: () => [] },
    submitLabel: { type: String, default: '' },
});

const emit = defineEmits(['submit']);

// Une prestation se mesure en temps ou en volume produit ; un bien se compte.
const SERVICE_UNITS = ['hour', 'day', 'month', 'word', 'page'];

const serviceUnits = computed(() => props.units.filter((u) => SERVICE_UNITS.includes(u.value)));
const productUnits = computed(() => props.units.filter((u) => !SERVICE_UNITS.includes(u.value)));

// Changer de famille propose l'unité naturelle, mais seulement tant que
// l'utilisateur n'a pas fait de choix délibéré : « pièce » et « heure » sont
// les deux valeurs par défaut, tout le reste est une décision qu'on respecte.
watch(
    () => props.form.type,
    (type) => {
        if (!['piece', 'hour'].includes(props.form.unit)) return;
        props.form.unit = type === 'service' ? 'hour' : 'piece';
    },
);

// VAT rate: standard rates via select, or "Autre" → manual number input.
const rateIsStandard = props.vatRates.map(Number).includes(Number(props.form.vat_rate));
const vatMode = ref(rateIsStandard ? String(Number(props.form.vat_rate)) : 'custom');

watch(vatMode, (mode) => {
    if (mode !== 'custom') {
        props.form.vat_rate = Number(mode);
    }
});
</script>

<template>
    <form @submit.prevent="emit('submit')" class="space-y-6 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <!-- Désignation -->
        <div>
            <InputLabel for="designation" :value="t('products.designation')" />
            <TextInput
                id="designation"
                v-model="form.designation"
                type="text"
                class="mt-1 block w-full"
                :placeholder="t('products.designation_placeholder')"
                required
                autofocus
            />
            <InputError :message="form.errors.designation" class="mt-2" />
        </div>

        <!-- Référence -->
        <div>
            <InputLabel for="reference" :value="t('products.reference')" />
            <TextInput
                id="reference"
                v-model="form.reference"
                type="text"
                class="mt-1 block w-full"
                :placeholder="t('products.reference_placeholder')"
            />
            <InputError :message="form.errors.reference" class="mt-2" />
        </div>

        <!-- Famille : produit ou prestation -->
        <div>
            <InputLabel for="type" :value="t('products.type')" />
            <select
                id="type"
                v-model="form.type"
                class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            >
                <!-- La valeur vide reste offerte : le champ est facultatif et
                     les articles créés avant son introduction sont non classés. -->
                <option :value="null">{{ t('products.type_unclassified') }}</option>
                <option value="product">{{ t('products.type_product') }}</option>
                <option value="service">{{ t('products.type_service') }}</option>
            </select>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ t('products.type_help') }}</p>
            <InputError :message="form.errors.type" class="mt-2" />
        </div>

        <!-- Description -->
        <div>
            <InputLabel for="description" :value="t('products.description')" />
            <textarea
                id="description"
                v-model="form.description"
                rows="2"
                class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            ></textarea>
            <InputError :message="form.errors.description" class="mt-2" />
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
            <!-- Prix HT -->
            <div>
                <InputLabel for="unit_price_ht" :value="t('products.unit_price_ht')" />
                <TextInput
                    id="unit_price_ht"
                    v-model="form.unit_price_ht"
                    type="number"
                    step="0.01"
                    min="0"
                    class="mt-1 block w-full"
                    required
                />
                <InputError :message="form.errors.unit_price_ht" class="mt-2" />
            </div>

            <!-- TVA -->
            <div>
                <InputLabel for="vat_rate" :value="t('products.vat_rate')" />
                <select
                    id="vat_rate"
                    v-model="vatMode"
                    class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
                    <option v-for="rate in vatRates" :key="rate" :value="String(rate)">{{ rate }}%</option>
                    <option value="custom">{{ t('products.vat_custom') }}</option>
                </select>
                <TextInput
                    v-if="vatMode === 'custom'"
                    v-model="form.vat_rate"
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    class="mt-2 block w-full"
                    :placeholder="t('products.vat_rate')"
                />
                <InputError :message="form.errors.vat_rate" class="mt-2" />
            </div>

            <!-- Unité -->
            <div>
                <InputLabel for="unit" :value="t('products.unit')" />
                <!-- Les unités sont regroupées par famille : on facture une
                     prestation à l'heure et un produit à la pièce. Toutes
                     restent proposées — un forfait mensuel de maintenance est
                     une prestation facturée au mois, la règle n'est pas
                     mécanique. -->
                <select
                    id="unit"
                    v-model="form.unit"
                    class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
                    <optgroup v-if="serviceUnits.length" :label="t('products.type_service')">
                        <option v-for="unit in serviceUnits" :key="unit.value" :value="unit.value">{{ unit.label }}</option>
                    </optgroup>
                    <optgroup v-if="productUnits.length" :label="t('products.type_product')">
                        <option v-for="unit in productUnits" :key="unit.value" :value="unit.value">{{ unit.label }}</option>
                    </optgroup>
                </select>
                <InputError :message="form.errors.unit" class="mt-2" />
            </div>
        </div>

        <!-- Actif -->
        <label class="flex items-center gap-3">
            <input
                type="checkbox"
                v-model="form.is_active"
                class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800"
            />
            <span class="text-sm text-slate-700 dark:text-slate-300">{{ t('products.is_active') }}</span>
        </label>

        <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4 dark:border-gray-800">
            <Link :href="route('products.index')" class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400">
                {{ t('cancel') }}
            </Link>
            <PrimaryButton :disabled="form.processing">
                {{ submitLabel || t('save') }}
            </PrimaryButton>
        </div>
    </form>
</template>
