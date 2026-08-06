<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ClientForm from '@/Components/ClientForm.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    client: {
        type: Object,
        required: true,
    },
    clientTypes: {
        type: Array,
        required: true,
    },
    currencies: {
        type: Array,
        required: true,
    },
    countries: {
        type: Array,
        required: true,
    },
    peppolSchemes: {
        type: Array,
        default: () => [],
    },
    vatRates: {
        type: Array,
        default: () => [],
    },
    isVatExempt: {
        type: Boolean,
        default: true,
    },
    sellerVatRegime: {
        type: String,
        default: 'franchise',
    },
    clientStatuses: {
        type: Array,
        default: () => [],
    },
    clientSources: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: props.client.name,
    contact_name: props.client.contact_name ?? '',
    email: props.client.email,
    address: props.client.address ?? '',
    postal_code: props.client.postal_code ?? '',
    city: props.client.city ?? '',
    country_code: props.client.country_code,
    vat_number: props.client.vat_number ?? '',
    registration_number: props.client.registration_number ?? '',
    type: props.client.type,
    status: props.client.status ?? 'active',
    currency: props.client.currency,
    phone: props.client.phone ?? '',
    notes: props.client.notes ?? '',
    locale: props.client.locale ?? 'fr',
    peppol_endpoint_scheme: props.client.peppol_endpoint_scheme ?? '',
    peppol_endpoint_id: props.client.peppol_endpoint_id ?? '',
    default_vat_rate: props.client.default_vat_rate ?? null,
    default_hourly_rate: props.client.default_hourly_rate ?? null,
    default_discount_type: props.client.default_discount_type ?? 'percent',
    default_discount_value: props.client.default_discount_value > 0 ? Number(props.client.default_discount_value) : null,
    default_discount_label: props.client.default_discount_label ?? '',
    accounting_id: props.client.accounting_id ?? '',
    source: props.client.source ?? null,
    estimated_value: props.client.estimated_value ?? null,
});

const submit = () => {
    form.put(route('clients.update', props.client.id));
};
</script>

<template>
    <Head :title="`${t('edit')} ${client.name}`" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('edit') }} {{ client.name }}
            </h1>
        </template>

        <div class="mx-auto max-w-3xl">
            <ClientForm
                :form="form"
                :client-types="clientTypes"
                :currencies="currencies"
                :countries="countries"
                :peppol-schemes="peppolSchemes"
                :vat-rates="vatRates"
                :is-vat-exempt="isVatExempt"
                :seller-vat-regime="sellerVatRegime"
                :client-statuses="clientStatuses"
                :client-sources="clientSources"
                :submit-label="t('save')"
                cancel-route="clients.show"
                :cancel-route-params="client.id"
                @submit="submit"
            />
        </div>
    </AppLayout>
</template>
