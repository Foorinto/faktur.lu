<script setup>
import { onBeforeUnmount, onMounted, watch } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

/**
 * Barre d'enregistrement, apparaissant dès qu'un formulaire est modifié.
 *
 * Sur les écrans longs — les paramètres d'entreprise comptent trente-six
 * champs — le bouton d'enregistrement vit tout en bas. On modifie un champ en
 * haut, et l'action est hors de vue. Le risque n'est pas le clic, c'est
 * l'oubli : quitter la page en croyant avoir enregistré.
 *
 * La barre ne s'affiche donc QUE si quelque chose a changé. Un bouton
 * perpétuellement flottant occuperait de la place sur une page qu'on vient
 * souvent seulement consulter, et signalerait l'action sans jamais signaler
 * l'état — or c'est l'état qui manquait.
 */
const props = defineProps({
    /** Formulaire Inertia : la barre lit `isDirty` et `processing`, et sait le réinitialiser. */
    form: { type: Object, required: true },
    /** Libellé du bouton principal, si « Enregistrer » ne convient pas. */
    label: { type: String, default: '' },
});

const emit = defineEmits(['submit']);

/**
 * Filet de sécurité du filet de sécurité : si la barre n'a pas été vue, le
 * navigateur demande confirmation avant de perdre la saisie. Les navigateurs
 * imposent leur propre texte depuis longtemps — seule compte la présence de
 * l'avertissement, pas son contenu.
 */
const avertirAvantDeQuitter = (evenement) => {
    if (! props.form.isDirty) return;

    evenement.preventDefault();
    evenement.returnValue = '';
};

onMounted(() => window.addEventListener('beforeunload', avertirAvantDeQuitter));
onBeforeUnmount(() => window.removeEventListener('beforeunload', avertirAvantDeQuitter));

// Une navigation interne réussie remet le compteur à zéro : sans cela, la barre
// resterait affichée après un enregistrement suivi d'un retour en arrière.
watch(() => props.form.recentlySuccessful, (succes) => {
    if (succes) props.form.defaults();
});
</script>

<template>
    <!-- Réserve la hauteur de la barre : sans cela, elle masquerait le dernier
         champ du formulaire, précisément quand on cherche à l'atteindre. -->
    <div v-if="form.isDirty" class="h-20" aria-hidden="true" />

    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
    >
        <div
            v-if="form.isDirty"
            class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white/95 backdrop-blur dark:border-gray-700 dark:bg-surface-card/95"
            role="status"
        >
            <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
                <p class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                    <span class="h-2 w-2 shrink-0 rounded-full bg-amber-500" aria-hidden="true" />
                    {{ t('unsaved_changes') }}
                </p>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        :disabled="form.processing"
                        class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300 dark:hover:bg-gray-700"
                        @click="form.reset()"
                    >
                        {{ t('discard_changes') }}
                    </button>

                    <button
                        type="button"
                        :disabled="form.processing"
                        class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 disabled:opacity-50"
                        @click="emit('submit')"
                    >
                        {{ form.processing ? t('saving') : (label || t('save')) }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
