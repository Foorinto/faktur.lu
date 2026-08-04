<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductForm from '@/Components/ProductForm.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    units: { type: Array, default: () => [] },
    vatRates: { type: Array, default: () => [] },
});

// Taux par défaut aligné sur les taux réellement proposés : en franchise de TVA
// seul le 0 % est disponible, il ne faut donc pas pré-remplir 17 %.
const defaultVatRate = props.vatRates.map(Number).includes(17)
    ? 17
    : Number(props.vatRates[0] ?? 0);

const form = useForm({
    designation: '',
    reference: '',
    type: null,
    description: '',
    unit_price_ht: null,
    vat_rate: defaultVatRate,
    unit: 'piece',
    is_active: true,
});

const submit = () => form.post(route('products.store'));
</script>

<template>
    <Head :title="t('products.new_title')" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('products.new_title') }}
            </h1>
        </template>

        <div class="mx-auto max-w-2xl">
            <ProductForm
                :form="form"
                :units="units"
                :vat-rates="vatRates"
                :submit-label="t('create')"
                @submit="submit"
            />
        </div>
    </AppLayout>
</template>
