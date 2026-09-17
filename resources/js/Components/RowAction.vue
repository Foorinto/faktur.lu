<script setup>
/**
 * L'action d'une ligne de liste : modifier, supprimer, suspendre, dupliquer…
 *
 * Les mêmes icônes, la même taille et le même survol partout. Elles vivaient
 * jusqu'ici recopiées écran par écran, et avaient fini par diverger : deux
 * crayons différents, deux corbeilles, des tailles qui ne se ressemblaient pas.
 * La référence retenue est celle de la liste des articles.
 *
 * Jamais muette : `title` pour la souris, `sr-only` pour les lecteurs d'écran
 * et la navigation au clavier.
 */
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    icon: { type: String, required: true },
    label: { type: String, required: true },
    href: { type: String, default: null },
    disabled: { type: Boolean, default: false },
    // `danger` pour ce qui détruit, `primary` pour ce qui mène quelque part.
    tone: { type: String, default: 'neutral' },
});

defineEmits(['click']);

const CHEMINS = {
    edit: 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z',
    delete: 'M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0',
    suspend: 'M15.75 5.25v13.5m-7.5-13.5v13.5',
    resume: 'M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z',
    duplicate: 'M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75',
    history: 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
};

const chemin = computed(() => CHEMINS[props.icon] ?? CHEMINS.edit);

const classes = computed(() => {
    const base = 'rounded-lg p-2 transition-colors disabled:cursor-not-allowed disabled:opacity-40';

    const tons = {
        danger: 'text-slate-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400',
        primary: 'text-slate-400 hover:bg-primary-50 hover:text-primary-600 dark:hover:bg-primary-900/20 dark:hover:text-primary-400',
        neutral: 'text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-gray-800 dark:hover:text-slate-200',
    };

    return `${base} ${tons[props.tone] ?? tons.neutral}`;
});
</script>

<template>
    <component
        :is="href ? Link : 'button'"
        :href="href || undefined"
        :type="href ? undefined : 'button'"
        :disabled="href ? undefined : disabled"
        :title="label"
        :class="classes"
        @click="href ? undefined : $emit('click')"
    >
        <span class="sr-only">{{ label }}</span>
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" :d="chemin" />
        </svg>
    </component>
</template>
