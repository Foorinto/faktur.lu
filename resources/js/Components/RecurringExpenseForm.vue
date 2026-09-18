<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ExpenseVatFields from '@/Components/ExpenseVatFields.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    form: { type: Object, required: true },
    frequencies: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    vatRates: { type: Array, default: () => [] },
    vatRatesByCountry: { type: Object, default: () => ({}) },
    vatRegimes: { type: Array, default: () => [] },
    countries: { type: Array, default: () => [] },
    homeCountry: { type: String, default: 'LU' },
    homeStandardRate: { type: Number, default: 17 },
    paymentMethods: { type: Array, default: () => [] },
    providers: { type: Array, default: () => [] },
    submitLabel: { type: String, default: '' },
});

const emit = defineEmits(['submit']);

const isTtcMode = computed(() => props.form.amount_input_mode === 'ttc');

/**
 * L'autre montant, celui qu'on n'a pas saisi.
 *
 * Le HT parle au comptable, le TTC au compte en banque : afficher les deux
 * évite de convertir de tête à chaque relecture de la charge.
 */
const montantConverti = computed(() => {
    const montant = Number(props.form.amount);

    if (!Number.isFinite(montant) || montant <= 0) return null;

    const taux = Number(props.form.vat_rate) || 0;
    const valeur = isTtcMode.value ? montant / (1 + taux / 100) : montant * (1 + taux / 100);

    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(valeur);
});

/**
 * Le jour du mois que l'utilisateur vient de choisir. Affiché tel quel parce
 * que c'est lui qui sera tenu tous les mois, y compris en février.
 */
const jourAncre = computed(() => {
    if (!props.form.next_expense_date) return null;
    return Number(props.form.next_expense_date.slice(8, 10));
});
</script>

<template>
    <form class="space-y-6" @submit.prevent="emit('submit')">
        <!-- Ce qui se répète -->
        <div class="rounded-2xl bg-white p-6 shadow dark:bg-surface-card">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ t('recurring_expenses.section_rhythm') }}
            </h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <InputLabel for="label" :value="t('recurring_expenses.label')" />
                    <input
                        id="label"
                        v-model="form.label"
                        type="text"
                        maxlength="255"
                        :placeholder="t('recurring_expenses.label_placeholder')"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                    <InputError :message="form.errors.label" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="frequency" :value="t('recurring_expenses.frequency')" />
                    <select
                        id="frequency"
                        v-model="form.frequency"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option v-for="f in frequencies" :key="f" :value="f">
                            {{ t(`recurring_expenses.frequencies.${f}`) }}
                        </option>
                    </select>
                    <InputError :message="form.errors.frequency" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="next_expense_date" :value="t('recurring_expenses.next_date')" />
                    <input
                        id="next_expense_date"
                        v-model="form.next_expense_date"
                        type="date"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                    <p
                        v-if="jourAncre && jourAncre >= 29 && form.frequency !== 'weekly'"
                        class="mt-1 text-xs text-slate-500 dark:text-slate-400"
                    >
                        {{ t('recurring_expenses.anchor_hint', { day: jourAncre }) }}
                    </p>
                    <InputError :message="form.errors.next_expense_date" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="ends_at" :value="t('recurring_expenses.ends_at')" />
                    <input
                        id="ends_at"
                        v-model="form.ends_at"
                        type="date"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ t('recurring_expenses.ends_at_hint') }}</p>
                    <InputError :message="form.errors.ends_at" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <label class="flex items-center gap-3">
                        <input
                            type="checkbox"
                            v-model="form.is_active"
                            class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800"
                        />
                        <span class="text-sm text-slate-700 dark:text-slate-300">{{ t('recurring_expenses.is_active') }}</span>
                    </label>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ t('recurring_expenses.is_active_hint') }}</p>
                </div>
            </div>
        </div>

        <!-- La dépense qui naîtra -->
        <div class="rounded-2xl bg-white p-6 shadow dark:bg-surface-card">
            <h2 class="mb-1 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ t('recurring_expenses.section_expense') }}
            </h2>
            <p class="mb-4 text-xs text-slate-500 dark:text-slate-400">{{ t('recurring_expenses.section_expense_hint') }}</p>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel for="provider_name" :value="t('supplier')" />
                    <input
                        id="provider_name"
                        v-model="form.provider_name"
                        type="text"
                        maxlength="255"
                        list="fournisseurs-connus-charge"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                    <datalist id="fournisseurs-connus-charge">
                        <option v-for="f in providers" :key="f" :value="f" />
                    </datalist>
                    <InputError :message="form.errors.provider_name" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="category" :value="t('category')" />
                    <select
                        id="category"
                        v-model="form.category"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">{{ t('select_category') }}</option>
                        <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                    </select>
                    <InputError :message="form.errors.category" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                        <InputLabel for="amount" :value="t('amount')" />

                        <div
                            class="inline-flex rounded-lg border border-gray-300 p-0.5 dark:border-gray-700"
                            role="group"
                            :aria-label="t('expense_amount_input_mode')"
                        >
                            <button
                                type="button"
                                :class="[
                                    'rounded-md px-3 py-1 text-sm font-medium transition',
                                    !isTtcMode ? 'bg-primary-600 text-white' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-gray-800',
                                ]"
                                :aria-pressed="!isTtcMode"
                                @click="form.amount_input_mode = 'ht'"
                            >
                                {{ t('expense_enter_in_ht') }}
                            </button>
                            <button
                                type="button"
                                :class="[
                                    'rounded-md px-3 py-1 text-sm font-medium transition',
                                    isTtcMode ? 'bg-primary-600 text-white' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-gray-800',
                                ]"
                                :aria-pressed="isTtcMode"
                                @click="form.amount_input_mode = 'ttc'"
                            >
                                {{ t('expense_enter_in_ttc') }}
                            </button>
                        </div>
                    </div>

                    <input
                        id="amount"
                        v-model="form.amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:w-64"
                    />
                    <p v-if="montantConverti" class="mt-1 text-sm font-medium text-slate-600 dark:text-slate-300">
                        {{ isTtcMode ? t('amount_ht') : t('amount_ttc') }} : {{ montantConverti }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ t('recurring_expenses.amount_hint') }}</p>
                    <InputError :message="form.errors.amount" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="payment_method" :value="t('payment_method')" />
                    <select
                        id="payment_method"
                        v-model="form.payment_method"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">{{ t('payment_method_optional') }}</option>
                        <option v-for="m in paymentMethods" :key="m.value" :value="m.value">{{ m.label }}</option>
                    </select>
                    <InputError :message="form.errors.payment_method" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="description" :value="t('description')" />
                    <input
                        id="description"
                        v-model="form.description"
                        type="text"
                        maxlength="2000"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                    <InputError :message="form.errors.description" class="mt-2" />
                </div>
            </div>
        </div>

        <!-- TVA, régime, pays : exactement les règles d'une dépense ordinaire -->
        <ExpenseVatFields
            :form="form"
            :vat-rates="vatRates"
            :vat-rates-by-country="vatRatesByCountry"
            :vat-regimes="vatRegimes"
            :countries="countries"
            :home-country="homeCountry"
            :home-standard-rate="homeStandardRate"
            hide-amounts
        />

        <div class="flex items-center justify-end gap-3">
            <Link :href="route('recurring-expenses.index')" class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400">
                {{ t('cancel') }}
            </Link>
            <PrimaryButton :disabled="form.processing">{{ submitLabel || t('save') }}</PrimaryButton>
        </div>
    </form>
</template>
