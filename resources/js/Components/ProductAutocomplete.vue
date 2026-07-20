<script setup>
import { ref } from 'vue';
import debounce from 'lodash/debounce';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    modelValue: { type: String, default: '' },
    inputId: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    required: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'select']);

const results = ref([]);
const open = ref(false);
const browsing = ref(false);

const runQuery = async (term) => {
    try {
        const response = await fetch(route('products.search', term ? { q: term } : {}), {
            headers: { Accept: 'application/json' },
        });
        const data = await response.json();
        results.value = data.products || [];
        open.value = results.value.length > 0;
    } catch (e) {
        results.value = [];
        open.value = false;
    }
};

const fetchResults = debounce((term) => {
    if (!term || term.length < 2) {
        results.value = [];
        open.value = false;
        return;
    }
    runQuery(term);
}, 250);

const onInput = (event) => {
    emit('update:modelValue', event.target.value);
    fetchResults(event.target.value);
};

// Open the full catalogue list (no term) on button click.
const browseAll = async () => {
    if (open.value) {
        open.value = false;
        return;
    }
    browsing.value = true;
    await runQuery('');
    browsing.value = false;
};

const choose = (product) => {
    emit('update:modelValue', product.designation);
    emit('select', product);
    open.value = false;
    results.value = [];
};

// Delay closing so a click on a result registers first.
const onBlur = () => setTimeout(() => { open.value = false; }, 150);
</script>

<template>
    <div class="relative">
        <div class="mt-1 flex gap-2">
            <input
                :id="inputId"
                :value="modelValue"
                type="text"
                autocomplete="off"
                :placeholder="placeholder"
                :required="required"
                class="block w-full flex-1 rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                @input="onInput"
                @focus="results.length && (open = true)"
                @blur="onBlur"
            />
            <button
                type="button"
                :title="t('products.browse')"
                class="inline-flex shrink-0 items-center rounded-xl border border-gray-200 bg-gray-50 px-3 text-slate-500 hover:bg-gray-100 hover:text-slate-700 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-400 dark:hover:bg-gray-700"
                @mousedown.prevent="browseAll"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </button>
        </div>
        <ul
            v-if="open"
            class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
        >
            <li
                v-for="p in results"
                :key="p.id"
                class="cursor-pointer px-3 py-2 text-sm hover:bg-primary-50 dark:hover:bg-gray-700"
                @mousedown.prevent="choose(p)"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="font-medium text-slate-800 dark:text-white">{{ p.designation }}</span>
                    <span class="whitespace-nowrap text-xs tabular-nums text-slate-500">{{ Number(p.unit_price_ht).toFixed(2) }} € · {{ Number(p.vat_rate) }}%</span>
                </div>
                <div v-if="p.reference" class="text-xs text-slate-400">{{ p.reference }}</div>
            </li>
        </ul>
    </div>
</template>
