import { describe, expect, it } from 'vitest';
import { sanitizeArticle, sanitizeRichText } from './sanitizeHtml';

/**
 * Nettoyage du HTML.
 *
 * Ces tests ont deux objets, et le second n'est pas le moindre.
 *
 * Le premier est évident : vérifier que ce qui doit être retiré l'est. Une
 * balise `script` qui passe dans un pied de facture est une exécution de code
 * chez le destinataire.
 *
 * Le second est la contrepartie, et c'est elle qui a motivé ce fichier. Mettre
 * DOMPurify à jour, c'est accepter qu'il devienne plus strict. Un désinfectant
 * qui se met à retirer une balise qu'il laissait passer ne signale rien : la
 * facture s'affiche simplement amputée, et personne ne fait le lien avec une
 * mise à jour de dépendance. On fige donc aussi ce qui doit SURVIVRE.
 */

describe('champ riche saisi par l’utilisateur', () => {
    it('conserve la mise en forme de l’éditeur', () => {
        const propre = sanitizeRichText(
            '<p>Merci de régler sous <strong>30 jours</strong>.</p>'
            + '<ul><li>Virement</li><li>Carte</li></ul>'
        );

        expect(propre).toContain('<strong>30 jours</strong>');
        expect(propre).toContain('<li>Virement</li>');
    });

    it('conserve les titres, citations et code', () => {
        const propre = sanitizeRichText(
            '<h2>Conditions</h2><blockquote>Extrait</blockquote><pre><code>TVA</code></pre>'
        );

        for (const attendu of ['<h2>', '<blockquote>', '<pre>', '<code>']) {
            expect(propre).toContain(attendu);
        }
    });

    it('conserve un lien et ses attributs', () => {
        const propre = sanitizeRichText(
            '<a href="https://faktur.lu" target="_blank" rel="noopener" class="lien">Site</a>'
        );

        expect(propre).toContain('href="https://faktur.lu"');
        expect(propre).toContain('target="_blank"');
        expect(propre).toContain('rel="noopener"');
        expect(propre).toContain('class="lien"');
    });

    it.each([
        ['mailto:contact@faktur.lu'],
        ['tel:+352123456'],
        ['/fr/factures'],
    ])('accepte le lien %s', (href) => {
        expect(sanitizeRichText(`<a href="${href}">Lien</a>`)).toContain(href);
    });

    // --- Ce qui doit tomber ------------------------------------------------

    it('retire un script', () => {
        const propre = sanitizeRichText('<p>Bonjour</p><script>alert(1)</script>');

        expect(propre).toContain('Bonjour');
        expect(propre).not.toContain('script');
        expect(propre).not.toContain('alert');
    });

    it('retire un gestionnaire d’événement', () => {
        const propre = sanitizeRichText('<p onclick="voler()">Texte</p>');

        expect(propre).toContain('Texte');
        expect(propre).not.toContain('onclick');
    });

    /**
     * Un lien est un endroit où l'on exécute du code si on laisse faire :
     * c'est toute la raison d'être de la liste de schémas autorisés.
     */
    it('neutralise un lien javascript:', () => {
        const propre = sanitizeRichText('<a href="javascript:alert(1)">Cliquez</a>');

        expect(propre).not.toContain('javascript:');
    });

    it('retire une image, absente de la liste blanche', () => {
        const propre = sanitizeRichText('<p>Texte</p><img src="x" onerror="alert(1)">');

        expect(propre).toContain('Texte');
        expect(propre).not.toContain('<img');
        expect(propre).not.toContain('onerror');
    });

    it.each(['<iframe src="https://exemple.lu"></iframe>', '<style>body{display:none}</style>'])(
        'retire %s',
        (dangereux) => {
            const propre = sanitizeRichText(`<p>Texte</p>${dangereux}`);

            expect(propre).toContain('Texte');
            expect(propre).not.toMatch(/<(iframe|style)/);
        }
    );

    it('rend une chaîne vide sur une entrée vide', () => {
        expect(sanitizeRichText('')).toBe('');
        expect(sanitizeRichText(null)).toBe('');
        expect(sanitizeRichText(undefined)).toBe('');
    });
});

describe('article de blog', () => {
    /**
     * La règle est plus large — la source est de confiance — mais elle reste
     * une défense en profondeur, pas une absence de défense.
     */
    it('conserve tableaux, divs et classes', () => {
        const propre = sanitizeArticle(
            '<div class="encadre"><table><tr><td>17 %</td></tr></table></div>'
        );

        expect(propre).toContain('<div class="encadre">');
        expect(propre).toContain('<td>17 %</td>');
    });

    it('conserve les liens externes de nos articles', () => {
        const propre = sanitizeArticle(
            '<a href="https://docs.peppol.eu" target="_blank" rel="noopener">Liste EAS</a>'
        );

        expect(propre).toContain('href="https://docs.peppol.eu"');
        expect(propre).toContain('rel="noopener"');
    });

    it('retire tout de même script et iframe', () => {
        const propre = sanitizeArticle('<p>Article</p><script>alert(1)</script><iframe></iframe>');

        expect(propre).toContain('Article');
        expect(propre).not.toContain('script');
        expect(propre).not.toContain('iframe');
    });

    it('rend une chaîne vide sur une entrée vide', () => {
        expect(sanitizeArticle('')).toBe('');
        expect(sanitizeArticle(null)).toBe('');
    });
});
