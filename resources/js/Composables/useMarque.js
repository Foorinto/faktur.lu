import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

/**
 * Identité de la marque, servie par le serveur.
 *
 * Un changement de dénomination est engagé. Le nom vient des propriétés
 * partagées par Inertia plutôt que de `import.meta.env.VITE_APP_NAME`, qui est
 * figé dans le bundle au moment de la compilation : ici, une variable
 * d'environnement suffit, sans recompiler.
 *
 * ⚠️ Ne couvre que les usages d'IDENTITÉ, là où le nom désigne le produit :
 * données structurées, adresses de repli, titres. Les phrases entières qui
 * citent le nom vivent dans les traductions et seront remplacées mécaniquement
 * le jour du changement, paramétrer un millier de phrases coûterait plus cher
 * que de les remplacer.
 */
export function useMarque() {
    const page = usePage();

    const marque = computed(() => page.props.marque ?? {});

    return {
        /** Nom affiché du produit, par exemple "faktur.lu". */
        nom: computed(() => marque.value.nom ?? "faktur.lu"),
        /** Domaine sans protocole, pour les adresses écrites en clair. */
        domaine: computed(() => marque.value.domaine ?? "faktur.lu"),
        /** URL publique, pour les liens absolus des données structurées. */
        url: computed(() => marque.value.url ?? "https://faktur.lu"),
    };
}
