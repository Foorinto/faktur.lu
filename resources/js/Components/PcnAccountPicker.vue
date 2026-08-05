<script setup>
import { ref, watch } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    modelValue: { type: String, default: '' },
    // Libellé de la catégorie : sert à proposer un compte sans rien chercher.
    suggestFrom: { type: String, default: '' },
    id: { type: String, default: 'pcn-account' },
});

const emit = defineEmits(['update:modelValue']);

const query = ref(props.modelValue ?? '');
const results = ref([]);
const open = ref(false);
const suggestion = ref(null);
let timer = null;

watch(
    () => props.modelValue,
    (value) => { query.value = value ?? ''; },
);

const fetchResults = async (term) => {
    const response = await fetch(route('settings.pcn-accounts', { q: term }), {
        headers: { Accept: 'application/json' },
    });
    if (!response.ok) return;
    results.value = (await response.json()).accounts ?? [];
};

const onInput = () => {
    emit('update:modelValue', query.value);
    open.value = true;

    // 250 ms : assez pour ne pas interroger à chaque frappe, assez peu pour que
    // la liste paraisse suivre la saisie.
    clearTimeout(timer);
    timer = setTimeout(() => fetchResults(query.value), 250);
};

// Fermer au blur, mais après le clic : un mousedown sur une option déclenche
// le blur de l'input avant que le clic n'aboutisse.
const closeSoon = () => {
    setTimeout(() => { open.value = false; }, 150);
};

const choose = (account) => {
    query.value = account.ref;
    emit('update:modelValue', account.ref);
    open.value = false;
};

/**
 * Propose un compte à partir du libellé de la catégorie.
 *
 * Ne s'exécute que si le champ est vide : une suggestion qui écraserait un
 * choix délibéré serait pire que pas de suggestion du tout.
 */
const suggest = async () => {
    if (query.value !== '' || !props.suggestFrom) return;

    const response = await fetch(route('settings.pcn-accounts', { suggest: props.suggestFrom }), {
        headers: { Accept: 'application/json' },
    });
    if (!response.ok) return;

    suggestion.value = (await response.json()).suggestion ?? null;
};

const acceptSuggestion = () => {
    if (!suggestion.value) return;
    choose(suggestion.value);
    suggestion.value = null;
};

defineExpose({ suggest });
</script>

<template>
    <div class="relative">
        <input
            :id="id"
            v-model="query"
            type="text"
            inputmode="numeric"
            autocomplete="off"
            placeholder="6111"
            @input="onInput"
            @focus="open = true; fetchResults(query)"
            @blur="closeSoon"
            class="block w-full rounded-xl border-gray-200 tabular-nums shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
        />

        <!-- Suggestion issue du libellé, proposée et non imposée -->
        <button
            v-if="suggestion && query === ''"
            type="button"
            @mousedown.prevent="acceptSuggestion"
            class="mt-1 block w-full rounded-lg bg-primary-50 px-2 py-1 text-left text-xs text-primary-700 hover:bg-primary-100 dark:bg-primary-900/20 dark:text-primary-300"
        >
            {{ t('purchase_categories.suggested') }}
            <span class="font-semibold tabular-nums">{{ suggestion.ref }}</span>,
            {{ suggestion.label }}
        </button>

        <ul
            v-if="open && results.length"
            class="absolute z-20 mt-1 max-h-64 w-full min-w-[22rem] overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
        >
            <li v-for="account in results" :key="account.ref">
                <button
                    type="button"
                    @mousedown.prevent="choose(account)"
                    class="block w-full px-3 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                    <span class="flex items-baseline gap-2">
                        <span class="font-semibold tabular-nums text-slate-900 dark:text-white">{{ account.ref }}</span>
                        <span class="text-sm text-slate-700 dark:text-slate-300">{{ account.label }}</span>
                    </span>
                    <!-- Le parent lève l'ambiguïté : « Constructions / Bâtiments »
                         seul ne dit pas qu'il s'agit d'un loyer. -->
                    <span v-if="account.parent" class="mt-0.5 block truncate text-xs text-slate-400">
                        {{ account.parent }}
                    </span>
                </button>
            </li>
        </ul>
    </div>
</template>
