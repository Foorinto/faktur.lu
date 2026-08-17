import DOMPurify from 'dompurify';

/**
 * Nettoyage du HTML avant rendu par `v-html`.
 *
 * Deux règles cohabitent dans l'application, et elles ne sont pas
 * interchangeables. Elles vivaient chacune dans un composant, écrites à des
 * moments différents : rien ne signalait qu'il y en avait deux, ni pourquoi.
 *
 * Elles sont réunies ici pour que la différence soit lisible — et vérifiable.
 * Une liste blanche est une décision de sécurité : elle mérite un nom et un
 * test, pas d'être enfouie dans un `computed`.
 */

/**
 * Balises produites par Tiptap, l'éditeur des champs riches.
 *
 * La liste est fermée : ce que l'utilisateur peut saisir dans un pied de
 * facture ou une note est connu d'avance, et tout le reste est du bruit — ou
 * une tentative.
 */
const BALISES_TIPTAP = [
    'p', 'br', 'strong', 'em', 'u', 's',
    'ul', 'ol', 'li',
    'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
    'blockquote', 'code', 'pre',
];

const ATTRIBUTS_TIPTAP = ['href', 'target', 'rel', 'class'];

/**
 * Schémas d'URL acceptés sur un lien.
 *
 * `javascript:` en est absent, et c'est tout l'objet de l'expression : un lien
 * est un endroit où l'on exécute du code si on laisse faire.
 */
const URI_AUTORISEES = /^(?:(?:https?|mailto|tel):|[^a-z]|[a-z+.\-]+(?:[^a-z+.\-:]|$))/i;

/**
 * Champ riche saisi par l'utilisateur — pied de facture, note, description.
 *
 * Liste blanche stricte : c'est du contenu que l'application affiche à des
 * tiers, et qui peut provenir d'un compte compromis.
 */
export function sanitizeRichText(html) {
    if (! html) {
        return '';
    }

    return DOMPurify.sanitize(html, {
        ALLOWED_TAGS: BALISES_TIPTAP,
        ALLOWED_ATTR: ATTRIBUTS_TIPTAP,
        ALLOWED_URI_REGEXP: URI_AUTORISEES,
    });
}

/**
 * Article de blog, rédigé par nous.
 *
 * Profil HTML complet : tableaux, citations, classes Tailwind et mises en page
 * y sont légitimes. Le nettoyage est ici une défense en profondeur — la source
 * est de confiance — mais il retire tout de même `script`, `iframe`, les
 * gestionnaires `on*` et les SVG.
 */
export function sanitizeArticle(html) {
    if (! html) {
        return '';
    }

    return DOMPurify.sanitize(html, { USE_PROFILES: { html: true } });
}
