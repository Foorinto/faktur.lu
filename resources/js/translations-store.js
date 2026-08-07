import { shallowRef } from 'vue';

/**
 * Dépôt des traductions, chargées une fois par langue.
 *
 * Elles voyageaient auparavant dans la prop Inertia `translations`, donc dans
 * le HTML de chaque page : 323 Ko bruts, plus de la moitié du document, alors
 * qu'une page n'en utilise qu'une poignée. Elles sont désormais servies dans un
 * fichier à part, mis en cache par le navigateur.
 *
 * ⚠️ Deux exigences, apprises l'une après l'autre :
 *
 * 1. le chargement initial se fait AVANT le montage de l'application. Le
 *    prerendering capture un snapshot dès qu'un `h1` contient du texte non
 *    vide, et une clé brute comme « landing.hero_title » EST du texte non
 *    vide : un chargement asynchrone ferait indexer les clés à la place des
 *    titres ;
 * 2. le dépôt est RÉACTIF. En navigation SPA, changer de langue ne recharge
 *    pas la page : sans réactivité, l'URL passait de /fr à /en et les textes
 *    restaient en français.
 */
const translations = shallowRef({});

/** URL actuellement chargée, pour ne pas retélécharger inutilement. */
let urlChargee = null;

export function getTranslations() {
    return translations.value;
}

export function setTranslations(payload) {
    translations.value = payload || {};
}

/**
 * Charge les traductions d'une URL, si elle diffère de celle déjà en mémoire.
 *
 * En cas d'échec réseau, une seconde tentative est faite : afficher toute
 * l'interface en clés brutes serait bien pire qu'un peu d'attente. Si les deux
 * tentatives échouent, on conserve les traductions précédentes plutôt que de
 * vider le dépôt.
 */
export async function loadTranslations(url) {
    if (!url || url === urlChargee) {
        return translations.value;
    }

    for (let essai = 0; essai < 2; essai++) {
        try {
            const reponse = await fetch(url, { credentials: 'same-origin' });

            if (reponse.ok) {
                setTranslations(await reponse.json());
                urlChargee = url;
                return translations.value;
            }
        } catch {
            // Réseau indisponible : on retente une fois avant d'abandonner.
        }
    }

    return translations.value;
}
