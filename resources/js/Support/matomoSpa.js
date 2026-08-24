/**
 * Suivi des pages Matomo sur une navigation Inertia.
 *
 * Le script Matomo posé dans le gabarit n'appelle `trackPageView` qu'une fois,
 * au chargement initial. En navigation Inertia la page n'est jamais rechargée :
 * tout ce que le visiteur atteint EN CLIQUANT était donc invisible depuis le
 * premier jour du suivi, le 8 avril 2026.
 *
 * Ce que ça faussait, au-delà des pages manquantes : les taux de rebond, qu'un
 * lecteur enchaînant trois articles gonflait quand même, et le nombre de pages
 * par visite — 1,66 mesuré là où un site de contenu correctement suivi tourne
 * entre 3 et 5.
 */

/** Nombre de trames d'attente avant d'envoyer malgré un titre inchangé. */
const TENTATIVES_MAX = 20; // ~320 ms

/**
 * Attend que le titre du document ait été remplacé, puis déclare la page.
 *
 * Le composant `Head` d'Inertia pose le titre pendant son propre cycle de
 * rendu, APRÈS l'événement de navigation. Déclarer la page tout de suite
 * l'envoyait sous le titre de la page précédente : l'URL juste, le titre
 * décalé d'un cran, et le rapport « Titres de pages » faux sans que rien ne le
 * signale.
 *
 * Le plafond de tentatives existe parce que deux pages peuvent légitimement
 * porter le même titre : mieux vaut un titre approximatif qu'une page vue
 * perdue.
 */
function declarerPageVue(fenetre, url, urlPrecedente, titreQuitte, tentative = 0) {
    const doc = fenetre.document;

    if (doc.title === titreQuitte && tentative < TENTATIVES_MAX) {
        fenetre.requestAnimationFrame(() =>
            declarerPageVue(fenetre, url, urlPrecedente, titreQuitte, tentative + 1),
        );

        return;
    }

    fenetre._paq.push(['setReferrerUrl', urlPrecedente]);
    fenetre._paq.push(['setCustomUrl', url]);
    fenetre._paq.push(['setDocumentTitle', doc.title]);
    fenetre._paq.push(['trackPageView']);
    // Réarme le suivi des liens sortants sur le DOM qui vient d'apparaître.
    fenetre._paq.push(['enableLinkTracking']);
}

/**
 * Branche le suivi sur les navigations du routeur Inertia.
 *
 * @param {{on: Function}} router - le routeur Inertia
 * @param {Window} fenetre - injectable pour les tests
 */
export function installerSuiviMatomo(router, fenetre = typeof window !== 'undefined' ? window : null) {
    if (!fenetre) return;

    // L'URL de départ, pour ne pas compter deux fois la page d'arrivée : une
    // fois par le gabarit, une fois ici.
    let derniereUrlSuivie = fenetre.location.href;

    router.on('navigate', () => {
        // Matomo peut être désactivé par configuration : on ne suppose rien.
        if (!Array.isArray(fenetre._paq)) return;

        const url = fenetre.location.href;

        // Même adresse : un rendu qui ne change pas de page — une soumission de
        // formulaire en erreur, par exemple — n'est pas une page vue.
        if (url === derniereUrlSuivie) return;

        const precedente = derniereUrlSuivie;
        derniereUrlSuivie = url;

        // Le titre est relevé MAINTENANT, au départ de la navigation, et non
        // conservé d'un appel à l'autre : ce module s'évalue avant que la
        // balise `<title>` existe, si bien qu'une sentinelle amorcée au
        // chargement vaut la chaîne vide et laisse passer le premier titre venu.
        declarerPageVue(fenetre, url, precedente, fenetre.document.title);
    });
}
