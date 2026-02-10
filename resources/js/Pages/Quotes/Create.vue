<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    clients: Array,
    vatRates: Array,
    units: Array,
    defaultClientId: [String, Number],
    isVatExempt: Boolean,
});

const defaultVatRate = props.isVatExempt ? 0 : 17;

const form = useForm({
    client_id: props.defaultClientId || '',
    valid_until: '',
    notes: '',
    currency: 'EUR',
    items: [],
});

const addItem = () => {
    form.items.push({
        title: '',
        description: '',
        quantity: 1,
        unit: 'hour',
        unit_price: 0,
        vat_rate: defaultVatRate,
    });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const submit = () => {
    form.post(route('quotes.store'));
};

// Add first item by default
if (form.items.length === 0) {
    addItem();
}
</script>

<template>
    <Head :title="t('new_quote')" />

    <AppLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link
                    :href="route('quotes.index')"
                    class="text-slate-400 hover:text-slate-500 dark:text-slate-500 dark:hover:text-slate-400"
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                    </svg>
                </Link>
                <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ t('new_quote') }}
                </h1>
            </div>
        </template>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Client selection -->
            <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">Client</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="client_id" value="Client" />
                            <select
                                id="client_id"
                                v-model="form.client_id"
                                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                required
                            >
                                <option value="">{{ t('select_client') }}</option>
                                <option v-for="client in clients" :key="client.id" :value="client.id">
                                    {{ client.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.client_id" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="valid_until" :value="t('valid_until_optional')" />
                            <input
                                id="valid_until"
                                v-model="form.valid_until"
                                type="date"
                                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                            />
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                {{ t('default_30_days') }}
                            </p>
                            <InputError :message="form.errors.valid_until" class="mt-2" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quote items -->
            <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('quote_lines') }}</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="space-y-4">
                        <div
                            v-for="(item, index) in form.items"
                            :key="index"
                            class="p-4 rounded-2xl border border-slate-200 dark:border-slate-700"
                        >
                            <div class="space-y-3">
                                <div class="flex-1">
                                    <InputLabel :for="`item-${index}-title`" :value="t('title')" />
                                    <input
                                        :id="`item-${index}-title`"
                                        v-model="item.title"
                                        type="text"
                                        class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                        :placeholder="t('service_title_placeholder')"
                                        required
                                    />
                                </div>

                                <div class="flex-1">
                                    <InputLabel :for="`item-${index}-description`" :value="t('description_optional')" />
                                    <textarea
                                        :id="`item-${index}-description`"
                                        v-model="item.description"
                                        rows="2"
                                        class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                        :placeholder="t('detailed_description_placeholder')"
                                    ></textarea>
                                </div>

                                <div class="flex flex-wrap gap-4 items-end">
                                    <div class="w-24">
                                        <InputLabel :for="`item-${index}-quantity`" :value="t('qty')" />
                                        <input
                                            :id="`item-${index}-quantity`"
                                            v-model.number="item.quantity"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                            required
                                        />
                                    </div>

                                    <div class="w-32">
                                        <InputLabel :for="`item-${index}-unit`" :value="t('unit')" />
                                        <select
                                            :id="`item-${index}-unit`"
                                            v-model="item.unit"
                                            class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                        >
                                            <option value="">-</option>
                                            <option v-for="unit in units" :key="unit.value" :value="unit.value">
                                                {{ unit.label }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="w-32">
                                        <InputLabel :for="`item-${index}-unit_price`" :value="t('price_ht')" />
                                        <input
                                            :id="`item-${index}-unit_price`"
                                            v-model.number="item.unit_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                            required
                                        />
                                    </div>

                                    <div class="w-32">
                                        <InputLabel :for="`item-${index}-vat_rate`" :value="t('vat')" />
                                        <select
                                            v-if="!isVatExempt"
                                            :id="`item-${index}-vat_rate`"
                                            v-model.number="item.vat_rate"
                                            class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                            required
                                        >
                                            <option v-for="rate in vatRates" :key="rate.value" :value="rate.value">
                                                {{ rate.label }}
                                            </option>
                                        </select>
                                        <div
                                            v-else
                                            class="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-slate-500 dark:border-slate-600 dark:bg-slate-600 dark:text-slate-400"
                                        >
                                            {{ t('vat_rates.exempt') }}
                                        </div>
                                    </div>

                                    <button
                                        v-if="form.items.length > 1"
                                        type="button"
                                        @click="removeItem(index)"
                                        class="p-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                    >
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.519.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="addItem"
                            class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600"
                        >
                            <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                            </svg>
                            {{ t('add_line') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('notes_optional') }}</h2>
                </div>
                <div class="px-6 py-4">
                    <textarea
                        v-model="form.notes"
                        rows="3"
                        class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                        :placeholder="t('special_conditions')"
                    ></textarea>
                    <InputError :message="form.errors.notes" class="mt-2" />
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-3">
                <Link
                    :href="route('quotes.index')"
                    class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600"
                >
                    {{ t('cancel') }}
                </Link>
                <PrimaryButton :disabled="form.processing">
                    <span v-if="form.processing">{{ t('creating') }}</span>
                    <span v-else>{{ t('create_quote') }}</span>
                </PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
