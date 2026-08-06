<script setup>
/**
 * Saisie des moyens de paiement, sous forme d'étiquettes (FEAT-098).
 *
 * Champ libre assumé : les clés historiques (transfer, cash…) sont traduites,
 * tout le reste est affiché tel quel. Un commerçant qui encaisse par Wero ou
 * TWINT n'a pas à attendre une mise à jour du logiciel.
 *
 * Sert à deux endroits aux sémantiques différentes :
 * - dans les paramètres, la liste est le réglage lui-même ;
 * - sur une facture, une liste vide signifie « suivre le réglage », d'où
 *   `inheritedLabels` qui montre ce qui s'appliquera alors.
 */
import { computed, ref } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    /** Moyens appliqués si la liste reste vide. Vide = pas d'héritage à montrer. */
    inheritedLabels: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue']);

const draft = ref('');

// Clés d'avant le passage au texte libre : elles vivent encore en base.
const LEGACY_KEYS = ['transfer', 'payconiq', 'cash', 'card', 'check'];

const label = (method) => (LEGACY_KEYS.includes(method) ? t('payment_methods.' + method) : method);

const suggestions = computed(() => [
    t('payment_methods.transfer'),
    t('payment_methods.cash'),
    t('payment_methods.card'),
    t('payment_methods.check'),
    'Wero',
]);

const add = (value) => {
    const trimmed = (value ?? draft.value).trim();

    // Comparaison sur le libellé autant que sur la valeur brute : sans cela,
    // « Virement » s'ajouterait à côté de la clé « transfer » déjà présente.
    const known = props.modelValue.some((m) => m === trimmed || label(m) === trimmed);

    if (trimmed && !known) {
        emit('update:modelValue', [...props.modelValue, trimmed]);
    }

    draft.value = '';
};

const remove = (index) => {
    emit('update:modelValue', props.modelValue.filter((_, i) => i !== index));
};
</script>

<template>
    <div>
        <div v-if="modelValue.length" class="mb-2 flex flex-wrap gap-1.5">
            <span
                v-for="(method, index) in modelValue"
                :key="index"
                class="inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-2.5 py-1 text-xs text-primary-700 dark:bg-primary-900/30 dark:text-primary-300"
            >
                {{ label(method) }}
                <button
                    type="button"
                    class="text-primary-400 hover:text-primary-700 dark:hover:text-primary-200"
                    :aria-label="t('remove')"
                    @click="remove(index)"
                >✕</button>
            </span>
        </div>

        <p
            v-else-if="inheritedLabels.length"
            class="mb-2 text-xs text-slate-500 dark:text-slate-400"
        >
            {{ t('payment_methods_inherited', { methods: inheritedLabels.join(', ') }) }}
        </p>

        <input
            v-model="draft"
            type="text"
            :placeholder="t('payment_methods_add_placeholder')"
            class="block w-full rounded-xl border-gray-200 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            @keydown.enter.prevent="add()"
        />

        <div class="mt-1.5 flex flex-wrap gap-1">
            <button
                v-for="suggestion in suggestions"
                :key="suggestion"
                type="button"
                class="rounded-full border border-gray-200 px-2 py-0.5 text-xs text-slate-500 hover:bg-gray-100 dark:border-gray-700 dark:text-slate-400 dark:hover:bg-gray-700"
                @click="add(suggestion)"
            >+ {{ suggestion }}</button>
        </div>
    </div>
</template>
