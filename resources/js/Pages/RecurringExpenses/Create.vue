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
    // Prérempli quand on arrive depuis une dépense existante.
    modele: { type: Object, default: null },
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
            :submit-label="t('recurring_expenses.create_submit')"
            @submit="submit"
        />
    </AppLayout>
</template>
