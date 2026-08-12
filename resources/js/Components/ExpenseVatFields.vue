<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useExpenseAmounts } from '@/Composables/useExpenseAmounts';

const { t } = useTranslations();

/**
 * Bloc « Montants et TVA » d'une dépense.
 *
 * Regroupé en un composant parce que la création et la modification en
 * partagent la totalité, champs comme règles : c'est le seul endroit où le
 * traitement fiscal d'un achat se décide.
 */
const props = defineProps({
    form: { type: Object, required: true },
    vatRates: { type: Array, default: () => [] },
    vatRatesByCountry: { type: Object, default: () => ({}) },
    vatRegimes: { type: Array, default: () => [] },
    countries: { type: Array, default: () => [] },
    homeCountry: { type: String, default: 'LU' },
    homeStandardRate: { type: Number, default: 17 },
});

const {
    vatMode,
    ratesForCountry,
    hasRateGrid,
    vatRateLocked,
    showForeignVatNotice,
    isReverseCharge,
    reverseChargeVat,
    isTtcMode,
    amountHtDisplay,
    amountTtcDisplay,
    calculatedVat,
} = useExpenseAmounts(props.form, props);

const formatCurrency = (amount) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount || 0);

/**
 * La marche à suivre officielle, sur Guichet.lu : conditions, accès au portail
 * VAT Refund, date limite du 30 septembre et montants minimums. Renvoyer à la
 * source évite d'avoir à maintenir ces seuils dans l'interface.
 */
const foreignVatGuideUrl =
    'https://guichet.public.lu/fr/entreprises/fiscalite/impots-benefices/tva/declarations/operations-intracommunautaires.html';

/**
 * Deux pages, parce qu'aucune ne suffit seule.
 *
 * La première explique le mécanisme — « la liquidation de la TVA incombe à
 * l'acquéreur en raison de la suppression des frontières fiscales » — mais ne
 * traite que les biens. La seconde est moins détaillée et couvre les deux :
 * les acquisitions de biens comme les services dont le preneur est débiteur de
 * la taxe. Un achat de prestation à l'étranger s'autoliquide aussi.
 */
const reverseChargeGuideUrl =
    'https://guichet.public.lu/fr/entreprises/fiscalite/impots-benefices/tva/notions/livraisons-biens.html';

const reverseChargeScopeUrl =
    'https://guichet.public.lu/fr/entreprises/fiscalite/impots-benefices/tva/notions/tva.html';
</script>

<template>
    <div class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-surface-card">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h2 class="text-lg font-medium text-slate-900 dark:text-white">
                {{ t('expense_amounts_and_vat') }}
            </h2>
        </div>

        <div class="space-y-4 px-6 py-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel for="supplier_country" :value="t('expense_supplier_country')" />
                    <select
                        id="supplier_country"
                        v-model="form.supplier_country"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option v-for="country in countries" :key="country.code" :value="country.code">
                            {{ country.name }}
                        </option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ t('expense_supplier_country_hint') }}
                    </p>
                    <InputError :message="form.errors.supplier_country" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="vat_regime" :value="t('expense_vat_regime')" />
                    <select
                        id="vat_regime"
                        v-model="form.vat_regime"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option v-for="regime in vatRegimes" :key="regime.value" :value="regime.value">
                            {{ regime.label }}
                        </option>
                    </select>
                    <!-- Qualifier un achat, c'est une décision fiscale : elle
                         conditionne la déductibilité et ce qui partira dans la
                         déclaration. Faktur.lu propose, il ne tranche pas. -->
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ t('notice_vat_regime_choice') }}
                    </p>
                    <InputError :message="form.errors.vat_regime" class="mt-2" />
                </div>
            </div>

            <!-- La TVA d'un autre État membre ne se déduit pas ici : elle se
                 récupère par une demande distincte auprès de l'AED. Le dire au
                 moment de la saisie évite d'avoir à défaire le chiffre plus
                 tard, au moment de la déclaration. -->
            <div
                v-if="showForeignVatNotice"
                class="flex gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-900/50 dark:bg-amber-950/30"
            >
                <svg
                    class="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-500"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    aria-hidden="true"
                >
                    <path
                        fill-rule="evenodd"
                        d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                        clip-rule="evenodd"
                    />
                </svg>
                <p class="text-sm text-amber-800 dark:text-amber-200">
                    {{ t('expense_foreign_vat_notice') }}
                    <a
                        :href="foreignVatGuideUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-medium underline underline-offset-2 hover:no-underline"
                    >
                        {{ t('expense_foreign_vat_link') }}
                    </a>
                </p>
            </div>

            <!-- Autoliquidation : la TVA que l'acheteur se facture lui-même.
                 Elle ne figure pas sur la facture du fournisseur, mais doit
                 apparaître deux fois dans la déclaration. Le montant s'affiche
                 pour que l'utilisateur retrouve ce qu'il verra passer dans son
                 récapitulatif TVA. -->
            <div
                v-if="isReverseCharge"
                class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 dark:border-sky-900/50 dark:bg-sky-950/30"
            >
                <div class="flex flex-wrap items-end gap-4">
                    <div class="w-40">
                        <InputLabel for="reverse_charge_vat_rate" :value="t('expense_reverse_charge_rate')" />
                        <input
                            id="reverse_charge_vat_rate"
                            v-model.number="form.reverse_charge_vat_rate"
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                    </div>
                    <div class="flex-1 text-sm text-sky-900 dark:text-sky-200">
                        <p class="font-medium">
                            {{ t('expense_reverse_charge_amount', { amount: formatCurrency(reverseChargeVat) }) }}
                        </p>
                        <p class="mt-1 text-xs">
                            {{ t('expense_reverse_charge_notice') }}
                        </p>
                        <p class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs">
                            <a
                                :href="reverseChargeGuideUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="font-medium underline underline-offset-2 hover:no-underline"
                            >
                                {{ t('expense_reverse_charge_link') }}
                            </a>
                            <a
                                :href="reverseChargeScopeUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="font-medium underline underline-offset-2 hover:no-underline"
                            >
                                {{ t('expense_reverse_charge_scope_link') }}
                            </a>
                        </p>
                    </div>
                </div>
                <p v-if="! form.is_deductible" class="mt-2 text-xs font-medium text-amber-700 dark:text-amber-400">
                    {{ t('expense_reverse_charge_not_deductible') }}
                </p>
            </div>

            <!-- Montants -->
            <div>
                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                    <InputLabel :value="t('amount')" />

                    <!-- Une facture en ligne n'affiche souvent que le total
                         payé : le laisser saisir tel quel évite une division à
                         la main, et fait tomber le total au centime près sur le
                         montant débité. -->
                    <div
                        class="inline-flex rounded-lg border border-gray-300 p-0.5 dark:border-gray-700"
                        role="group"
                        :aria-label="t('expense_amount_input_mode')"
                    >
                        <button
                            type="button"
                            :class="[
                                'rounded-md px-3 py-1 text-sm font-medium transition',
                                !isTtcMode
                                    ? 'bg-primary-600 text-white'
                                    : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-gray-800',
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
                                isTtcMode
                                    ? 'bg-primary-600 text-white'
                                    : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-gray-800',
                            ]"
                            :aria-pressed="isTtcMode"
                            @click="form.amount_input_mode = 'ttc'"
                        >
                            {{ t('expense_enter_in_ttc') }}
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div data-tour="expense-form-amount">
                        <InputLabel for="amount_ht" :value="t('amount_ht')" />
                        <div class="relative mt-1">
                            <input
                                v-if="!isTtcMode"
                                id="amount_ht"
                                v-model="form.amount_ht"
                                type="number"
                                step="0.01"
                                min="0.01"
                                class="block w-full rounded-xl border-gray-300 pr-12 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                placeholder="0.00"
                                required
                            />
                            <input
                                v-else
                                :value="amountHtDisplay"
                                type="text"
                                readonly
                                tabindex="-1"
                                :aria-label="t('amount_ht')"
                                class="block w-full cursor-not-allowed rounded-xl border-gray-200 bg-slate-50 pr-12 text-slate-500 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-slate-400"
                            />
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                <span class="text-slate-500 dark:text-slate-400">EUR</span>
                            </div>
                        </div>
                        <InputError :message="form.errors.amount_ht" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="amount_ttc" :value="t('amount_ttc')" />
                        <div class="relative mt-1">
                            <input
                                v-if="isTtcMode"
                                id="amount_ttc"
                                v-model="form.amount_ttc"
                                type="number"
                                step="0.01"
                                min="0.01"
                                class="block w-full rounded-xl border-gray-300 pr-12 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                placeholder="0.00"
                                required
                            />
                            <input
                                v-else
                                :value="amountTtcDisplay"
                                type="text"
                                readonly
                                tabindex="-1"
                                :aria-label="t('amount_ttc')"
                                class="block w-full cursor-not-allowed rounded-xl border-gray-200 bg-slate-50 pr-12 text-slate-500 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-slate-400"
                            />
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                <span class="text-slate-500 dark:text-slate-400">EUR</span>
                            </div>
                        </div>
                        <InputError :message="form.errors.amount_ttc" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="vat_rate" :value="t('vat_rate_label')" />
                        <select
                            v-if="hasRateGrid && !vatRateLocked"
                            id="vat_rate"
                            v-model="vatMode"
                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            required
                        >
                            <option v-for="rate in ratesForCountry" :key="rate.value" :value="String(rate.value)">
                                {{ rate.label }}
                            </option>
                            <option value="custom">{{ t('vat_rate_custom') }}</option>
                        </select>

                        <!-- Aucune grille pour ce pays : plutôt que d'imposer
                             d'arrondir sur un taux voisin, on laisse recopier
                             celui qui figure sur la facture. -->
                        <input
                            v-if="!hasRateGrid || vatMode === 'custom' || vatRateLocked"
                            id="vat_rate_custom"
                            v-model.number="form.vat_rate"
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            :disabled="vatRateLocked"
                            :aria-label="t('vat_rate_label')"
                            :class="[
                                'block w-full rounded-xl shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:text-white',
                                hasRateGrid && !vatRateLocked ? 'mt-2' : 'mt-1',
                                vatRateLocked
                                    ? 'cursor-not-allowed border-gray-200 bg-slate-50 text-slate-500 dark:border-gray-700 dark:bg-gray-900'
                                    : 'border-gray-300 dark:border-gray-700',
                            ]"
                            :placeholder="t('vat_rate_custom_placeholder')"
                        />
                        <p v-if="vatRateLocked" class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            {{ t('expense_vat_rate_locked_hint') }}
                        </p>
                        <InputError :message="form.errors.vat_rate" class="mt-2" />
                    </div>
                </div>

                <div class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-sm dark:bg-gray-800">
                    <div class="flex justify-between">
                        <span class="text-slate-500 dark:text-slate-400">{{ t('amount_ht') }} :</span>
                        <span class="font-medium text-slate-900 dark:text-white">{{ formatCurrency(amountHtDisplay) }}</span>
                    </div>
                    <div class="mt-1 flex justify-between">
                        <span class="text-slate-500 dark:text-slate-400">{{ t('vat') }} :</span>
                        <span class="font-medium text-slate-900 dark:text-white">{{ formatCurrency(calculatedVat) }}</span>
                    </div>
                    <div class="mt-1 flex justify-between border-t border-gray-200 pt-1 dark:border-gray-700">
                        <span class="text-slate-500 dark:text-slate-400">{{ t('ttc') }} :</span>
                        <span class="font-semibold text-slate-900 dark:text-white">{{ formatCurrency(amountTtcDisplay) }}</span>
                    </div>
                </div>
            </div>

            <!-- La déductibilité se décide avec le régime et le taux, pas au
                 milieu des références et des notes : c'est le même sujet, elle
                 se lit ici. -->
            <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                <div class="flex items-start">
                    <input
                        id="is_deductible"
                        v-model="form.is_deductible"
                        type="checkbox"
                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800"
                    />
                    <div class="ml-2">
                        <label for="is_deductible" class="block text-sm text-slate-900 dark:text-white">
                            {{ t('expense_deductible') }}
                        </label>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                            {{ t('expense_deductible_hint') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
