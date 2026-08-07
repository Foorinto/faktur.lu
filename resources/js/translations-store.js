/**
 * Dépôt des traductions, chargées une fois pour toutes.
 *
 * Elles voyageaient auparavant dans la prop Inertia `translations`, donc dans
 * le HTML de chaque page : 323 Ko bruts, plus de la moitié du document, alors
 * qu'une page n'en utilise qu'une poignée. Elles sont désormais servies dans un
 * fichier à part, mis en cache par le navigateur.
 *
 * ⚠️ Le chargement est fait AVANT le montage de l'application, jamais après.
 * Le prerendering capture un snapshot dès qu'un `h1` contient du texte non
 * vide, et une clé brute comme « landing.hero_title » EST du texte non vide :
 * un chargement asynchrone ferait indexer les clés à la place des titres.
 */
let translations = {};

export function setTranslations(payload) {
    translations = payload || {};
}

export function getTranslations() {
    return translations;
}

/**
 * Charge les traductions pour la langue courante.
 *
 * En cas d'échec réseau, une seconde tentative est faite : un rechargement
 * complet de l'interface en clés brutes serait bien pire qu'une demi-seconde
 * d'attente supplémentaire.
 */
export async function loadTranslations(url) {
    if (!url) {
        return {};
    }

    for (let essai = 0; essai < 2; essai++) {
        try {
            const reponse = await fetch(url, { credentials: 'same-origin' });

            if (reponse.ok) {
                const payload = await reponse.json();
                setTranslations(payload);
                return payload;
            }
        } catch {
            // Réseau indisponible : on retente une fois avant d'abandonner.
        }
    }

    return {};
}
