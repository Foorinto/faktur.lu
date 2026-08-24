/**
 * D'où vient le visiteur, retenu jusqu'à sa réponse.
 *
 * Matomo compte les VISITES par canal ; ceci retient le canal jusqu'à la
 * RÉPONSE au formulaire. Les deux ensemble donnent le taux de conversion par
 * canal, qui est la seule chose décidant s'il faut recommencer un canal ou
 * l'abandonner : « la fédération a envoyé 34 personnes et 6 ont répondu, le
 * groupe Facebook en a envoyé 210 et 1 a répondu ».
 *
 * `utm_source` plutôt qu'un paramètre maison : Matomo le lit nativement et le
 * range dans son rapport Campagnes. Un seul paramètre dans le lien nourrit les
 * deux mesures.
 */

const CLE = 'faktur:source-visite';

/** Ce qu'on accepte d'enregistrer : le reste vient d'un lien mal formé ou pire. */
const FORME_VALIDE = /^[a-zA-Z0-9._-]{1,60}$/;

/**
 * Hôte du référent, s'il est externe.
 *
 * Une navigation interne ne dit rien de l'origine de la visite : le référent
 * serait `faktur.lu` et écraserait le vrai canal.
 */
function hoteReferent(fenetre) {
    const referent = fenetre.document.referrer;

    if (!referent) return null;

    try {
        const hote = new URL(referent).hostname.replace(/^www\./, '');

        return hote === fenetre.location.hostname.replace(/^www\./, '') ? null : hote;
    } catch {
        return null;
    }
}

/**
 * Détermine la source de la visite courante, sans rien mémoriser.
 *
 * @returns {string|null}
 */
export function sourceDetectee(fenetre) {
    const parametres = new URLSearchParams(fenetre.location.search);
    const utm = (parametres.get('utm_source') || '').trim();

    if (utm && FORME_VALIDE.test(utm)) return utm;

    return hoteReferent(fenetre);
}

/**
 * Retient la source au PREMIER contact, et la rend.
 *
 * Premier contact et non dernier : c'est le canal qui a amené la personne qui
 * mérite le crédit, pas la page où elle se trouvait au moment de répondre. Une
 * navigation interne effacerait sinon l'origine à chaque clic.
 *
 * @returns {string|null} la source retenue, ou null si on n'en sait rien
 */
export function sourceDeLaVisite(fenetre = typeof window !== 'undefined' ? window : null) {
    if (!fenetre) return null;

    let memoire = null;

    // Le stockage de session peut être refusé — navigation privée stricte,
    // réglages d'entreprise. On dégrade sans casser le formulaire.
    try {
        memoire = fenetre.sessionStorage;
    } catch {
        memoire = null;
    }

    const dejaVu = memoire?.getItem(CLE);

    if (dejaVu) return dejaVu;

    const source = sourceDetectee(fenetre);

    if (source && memoire) {
        try {
            memoire.setItem(CLE, source);
        } catch {
            // Quota plein ou écriture refusée : tant pis, la source vaudra
            // pour cette page seulement.
        }
    }

    return source;
}
