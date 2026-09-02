<script setup>
import { useMarque } from "@/Composables/useMarque";

const { nom: marqueNom } = useMarque();

/**
 * Rappel que ${marqueNom.value} produit des chiffres, sans les attester.
 *
 * Posé aux endroits où l'utilisateur s'apprête à s'appuyer sur une sortie de
 * l'outil pour une démarche fiscale — un export remis à sa fiduciaire, un
 * fichier présenté lors d'un contrôle. C'est là qu'il vaut quelque chose : un
 * avertissement enterré dans les conditions générales ne protège personne et
 * n'aide personne.
 *
 * Volontairement neutre, et non ambre : la couleur d'alerte est réservée aux
 * situations où quelque chose ne va pas. Multiplier les bandeaux jaunes finit
 * par les rendre tous invisibles, y compris ceux qui signalent un vrai
 * problème.
 */
defineProps({
    text: { type: String, required: true },
});
</script>

<template>
    <div
        class="flex gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/50"
        role="note"
    >
        <svg
            class="mt-0.5 h-5 w-5 shrink-0 text-slate-400 dark:text-slate-500"
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
        >
            <path
                fill-rule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z"
                clip-rule="evenodd"
            />
        </svg>
        <p class="text-sm text-slate-600 dark:text-slate-400">{{ text }}</p>
    </div>
</template>
