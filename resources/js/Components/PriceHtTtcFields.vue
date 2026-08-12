<script setup>
import { computed, ref } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

/**
 * Prix unitaire d'une ligne, saisissable indifféremment en HT ou en TTC.
 *
 * Le TTC n'est pas stocké : il ne sert qu'à remplir le prix HT, seule valeur
 * que la facture connaisse. Tout le reste de la chaîne — totaux, PDF, export
 * comptable, FAIA, Peppol — continue de partir du HT, sans rien savoir de
 * cette saisie.
 */
const props = defineProps({
    modelValue: { type: [Number, String], default: '' },
    vatRate: { type: [Number, String], default: 0 },
    inputId: { type: String, required: true },
    required: { type: Boolean, default: false },
    /** Libellés discrets, pour les tableaux de lignes denses. */
    compact: { type: Boolean, default: false },
    /**
     * Les formulaires de vente n'habillent pas tous leurs champs de la même
     * façon. Plutôt que de dupliquer la logique de conversion pour la faire
     * ressembler à son voisin, le composant accepte l'habillage du formulaire
     * qui l'accueille.
     */
    inputClass: { type: String, default: '' },
    labelClass: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const multiplier = computed(() => 1 + (parseFloat(props.vatRate) || 0) / 100);

/**
 * Ce que l'utilisateur est en train de taper dans le champ TTC.
 *
 * Sans ce brouillon, chaque frappe repasserait par la conversion aller-retour
 * et réécrirait le champ sous les doigts : taper « 1 » pour « 19,90 »
 * afficherait aussitôt « 1,00 ».
 */
const ttcDraft = ref(null);

const ttcValue = computed(() => {
    if (ttcDraft.value !== null) return ttcDraft.value;

    const ht = parseFloat(props.modelValue);
    if (!Number.isFinite(ht)) return '';

    return Number((ht * multiplier.value).toFixed(2));
});

const onHtInput = (event) => {
    ttcDraft.value = null;

    const raw = event.target.value;
    emit('update:modelValue', raw === '' ? '' : Number(raw));
};

const onTtcInput = (event) => {
    const raw = event.target.value;
    ttcDraft.value = raw;

    if (raw === '') {
        emit('update:modelValue', '');
        return;
    }

    const ttc = parseFloat(raw);
    if (! Number.isFinite(ttc)) return;

    // Quatre décimales : la précision à laquelle le prix unitaire est stocké.
    // En conserver moins ferait dériver le total de la ligne dès que la
    // quantité dépasse quelques unités.
    emit('update:modelValue', Math.round((ttc / multiplier.value) * 10000) / 10000);
};

const resolvedInputClass = computed(
    () =>
        props.inputClass ||
        'block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white',
);

const resolvedLabelClass = computed(
    () => props.labelClass || 'mb-1 block text-xs text-slate-500 dark:text-slate-400',
);
</script>

<template>
    <div class="flex gap-2">
        <div class="flex-1">
            <InputLabel v-if="! compact" :for="inputId" :value="t('price_ht')" />
            <label v-else :for="inputId" :class="resolvedLabelClass">{{ t('price_ht') }}</label>
            <input
                :id="inputId"
                type="number"
                step="0.01"
                min="0"
                :value="modelValue"
                :required="required"
                :class="[resolvedInputClass, compact ? '' : 'mt-1']"
                @input="onHtInput"
            />
        </div>

        <div class="flex-1">
            <InputLabel v-if="! compact" :for="`${inputId}-ttc`" :value="t('price_ttc')" />
            <label v-else :for="`${inputId}-ttc`" :class="resolvedLabelClass">{{ t('price_ttc') }}</label>
            <input
                :id="`${inputId}-ttc`"
                type="number"
                step="0.01"
                min="0"
                :value="ttcValue"
                :title="t('price_ttc_hint')"
                :class="[resolvedInputClass, compact ? '' : 'mt-1']"
                @input="onTtcInput"
                @blur="ttcDraft = null"
            />
        </div>
    </div>
</template>
