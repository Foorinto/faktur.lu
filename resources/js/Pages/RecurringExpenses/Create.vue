<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import RecurringExpenseForm from '@/Components/RecurringExpenseForm.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
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
    // Prérempli quand on arrive depuis une dépense existante.
    modele: { type: Object, default: null },
    // Nombre de dépenses déjà saisies qui ressemblent à cette charge.
    occurrencesPassees: { type: Number, default: 0 },
});

const moisProchain = () => {
    const d = new Date();
    d.setMonth(d.getMonth() + 1);
    return d.toISOString().split('T')[0];
};

const form = useForm({
    label: props.modele?.label ?? '',
    frequency: props.modele?.frequency ?? 'monthly',
    next_expense_date: props.modele?.next_expense_date ?? moisProchain(),
    ends_at: '',
    is_active: true,
    provider_name: props.modele?.provider_name ?? '',
    supplier_country: props.modele?.supplier_country ?? props.homeCountry ?? 'LU',
    category: props.modele?.category ?? '',
    amount_input_mode: props.modele?.amount_input_mode ?? 'ht',
    amount: props.modele?.amount ?? '',
    vat_rate: props.modele?.vat_rate ?? 17,
    vat_regime: props.modele?.vat_regime ?? 'national',
    reverse_charge_vat_rate: props.modele?.reverse_charge_vat_rate ?? null,
    is_deductible: props.modele?.is_deductible ?? true,
    payment_method: props.modele?.payment_method ?? '',
    description: props.modele?.description ?? '',
    // Proposé coché quand on arrive d'une dépense qui a déjà des jumelles :
    // c'est le cas de figure où le double compte guette.
    attach_past: props.occurrencesPassees > 0,
});

const submit = () => form.post(route('recurring-expenses.store'));
</script>

<template>
    <Head :title="t('recurring_expenses.create_title')" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('recurring_expenses.create_title') }}
            </h1>
        </template>

        <p v-if="modele" class="mb-4 rounded-xl bg-primary-50 px-4 py-3 text-sm text-primary-800 dark:bg-primary-900/30 dark:text-primary-200">
            {{ t('recurring_expenses.prefilled_from_expense') }}
        </p>

        <!--
            Une charge déclarée après coup a souvent déjà été saisie à la main.
            Sans rattachement, ces dépenses resteraient dans la moyenne pendant
            que la charge est projetée à sa date, et le loyer pèserait deux fois.
            Rien ne se fait dans le dos de l'utilisateur : il coche.
        -->
        <div v-if="occurrencesPassees > 0" class="mb-4 rounded-2xl bg-white p-4 shadow dark:bg-surface-card">
            <label class="flex items-start gap-3">
                <input
                    type="checkbox"
                    v-model="form.attach_past"
                    class="mt-0.5 rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800"
                />
                <span>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        {{ t('recurring_expenses.attach_past_label') }}
                        <span class="font-normal text-slate-500 dark:text-slate-400">
                            ({{ t('recurring_expenses.attach_past_found', { count: occurrencesPassees }) }})
                        </span>
                    </span>
                    <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">
                        {{ t('recurring_expenses.attach_past_hint') }}
                    </span>
                </span>
            </label>
        </div>

        <RecurringExpenseForm
            :form="form"
            :frequencies="frequencies"
            :categories="categories"
            :vat-rates="vatRates"
            :vat-rates-by-country="vatRatesByCountry"
            :vat-regimes="vatRegimes"
            :countries="countries"
            :home-country="homeCountry"
            :home-standard-rate="homeStandardRate"
            :payment-methods="paymentMethods"
            :providers="providers"
            :submit-label="t('recurring_expenses.create_submit')"
            @submit="submit"
        />
    </AppLayout>
</template>
