<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import RecurringExpenseForm from '@/Components/RecurringExpenseForm.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    charge: { type: Object, required: true },
    frequencies: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    vatRates: { type: Array, default: () => [] },
    vatRatesByCountry: { type: Object, default: () => ({}) },
    vatRegimes: { type: Array, default: () => [] },
    countries: { type: Array, default: () => [] },
    homeCountry: { type: String, default: 'LU' },
    homeStandardRate: { type: Number, default: 17 },
    paymentMethods: { type: Array, default: () => [] },
});

const form = useForm({
    label: props.charge.label ?? '',
    frequency: props.charge.frequency,
    next_expense_date: props.charge.next_expense_date,
    ends_at: props.charge.ends_at ?? '',
    is_active: props.charge.is_active,
    provider_name: props.charge.provider_name,
    supplier_country: props.charge.supplier_country ?? 'LU',
    category: props.charge.category,
    amount_input_mode: props.charge.amount_input_mode,
    amount: props.charge.amount,
    vat_rate: props.charge.vat_rate,
    vat_regime: props.charge.vat_regime ?? 'national',
    reverse_charge_vat_rate: props.charge.reverse_charge_vat_rate,
    is_deductible: props.charge.is_deductible,
    payment_method: props.charge.payment_method ?? '',
    description: props.charge.description ?? '',
});

const submit = () => form.put(route('recurring-expenses.update', props.charge.id));
</script>

<template>
    <Head :title="t('recurring_expenses.edit_title')" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('recurring_expenses.edit_title') }}
            </h1>
        </template>

        <p v-if="charge.expenses_generated > 0" class="mb-4 text-sm text-slate-500 dark:text-slate-400">
            {{ t('recurring_expenses.already_generated', { count: charge.expenses_generated }) }}
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
            @submit="submit"
        />
    </AppLayout>
</template>
