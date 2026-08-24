import { beforeEach, describe, expect, it } from 'vitest';
import { sourceDeLaVisite, sourceDetectee } from './sourceVisite';

/**
 * D'où vient la personne qui répond.
 *
 * Le point délicat n'est pas de lire un paramètre d'URL, c'est de le RETENIR :
 * quelqu'un arrive par un lien de campagne, lit deux pages, puis répond. Si la
 * source se recalculait au moment de l'envoi, elle vaudrait « faktur.lu » et
 * toute la mesure serait perdue au profit de nous-mêmes.
 */

function fenetreFactice({ url = 'https://faktur.lu/fr', referent = '', stockage = true } = {}) {
    const u = new URL(url);
    const donnees = new Map();

    return {
        location: { href: u.href, search: u.search, hostname: u.hostname },
        document: { referrer: referent },
        get sessionStorage() {
            if (!stockage) throw new Error('refusé');

            return {
                getItem: (k) => donnees.get(k) ?? null,
                setItem: (k, v) => donnees.set(k, v),
            };
        },
    };
}

describe('détection de la source', () => {
    it('lit utm_source', () => {
        expect(sourceDetectee(fenetreFactice({ url: 'https://faktur.lu/fr?utm_source=horesca' })))
            .toBe('horesca');
    });

    it('retombe sur l’hôte du référent', () => {
        expect(sourceDetectee(fenetreFactice({ referent: 'https://www.linkedin.com/feed/' })))
            .toBe('linkedin.com');
    });

    /**
     * Une navigation interne ne dit rien de l'origine : sans ce filtre, la
     * source vaudrait « faktur.lu » dès le deuxième clic.
     */
    it('ignore un référent interne', () => {
        expect(sourceDetectee(fenetreFactice({ referent: 'https://faktur.lu/fr/blog' })))
            .toBeNull();
    });

    it('ignore un utm_source difforme', () => {
        for (const sale of ['<script>', 'a'.repeat(80), 'avec espace', '../../etc']) {
            expect(sourceDetectee(fenetreFactice({
                url: `https://faktur.lu/fr?utm_source=${encodeURIComponent(sale)}`,
            }))).toBeNull();
        }
    });

    it('rend null quand il n’y a rien à dire', () => {
        expect(sourceDetectee(fenetreFactice())).toBeNull();
    });
});

describe('mémoire de la source', () => {
    it('retient la source du PREMIER contact', () => {
        const f = fenetreFactice({ url: 'https://faktur.lu/fr?utm_source=horesca' });

        expect(sourceDeLaVisite(f)).toBe('horesca');

        // La personne navigue : plus de paramètre, référent devenu interne.
        f.location.search = '';
        f.document.referrer = 'https://faktur.lu/fr';

        expect(sourceDeLaVisite(f)).toBe('horesca');
    });

    it('ne se laisse pas écraser par une campagne rencontrée plus tard', () => {
        const f = fenetreFactice({ url: 'https://faktur.lu/fr?utm_source=fediation' });
        sourceDeLaVisite(f);

        f.location.search = '?utm_source=autre';

        expect(sourceDeLaVisite(f)).toBe('fediation');
    });

    it('survit à un stockage refusé', () => {
        // Navigation privée stricte, réglages d'entreprise : le formulaire ne
        // doit pas cesser de fonctionner pour autant.
        const f = fenetreFactice({ url: 'https://faktur.lu/fr?utm_source=x', stockage: false });

        expect(() => sourceDeLaVisite(f)).not.toThrow();
        expect(sourceDeLaVisite(f)).toBe('x');
    });

    it('rend null hors navigateur', () => {
        expect(sourceDeLaVisite(null)).toBeNull();
    });
});
