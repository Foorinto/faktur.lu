<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductForm from '@/Components/ProductForm.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    product: { type: Object, required: true },
    units: { type: Array, default: () => [] },
    vatRates: { type: Array, default: () => [] },
});

const form = useForm({
    designation: props.product.designation ?? '',
    reference: props.product.reference ?? '',
    type: props.product.type ?? null,
    description: props.product.description ?? '',
    unit_price_ht: props.product.unit_price_ht ?? 0,
    vat_rate: Number(props.product.vat_rate ?? 17),
    unit: props.product.unit ?? 'piece',
    is_active: Boolean(props.product.is_active),
});

const submit = () => form.put(route('products.update', props.product.id));
</script>

<template>
    <Head :title="t('products.edit_title')" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('products.edit_title') }}
            </h1>
        </template>

        <div class="mx-auto max-w-2xl">
            <ProductForm
                :form="form"
                :units="units"
                :vat-rates="vatRates"
                :submit-label="t('save')"
                @submit="submit"
            />
        </div>
    </AppLayout>
</template>
