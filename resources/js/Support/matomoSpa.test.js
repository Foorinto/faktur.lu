import { beforeEach, describe, expect, it, vi } from 'vitest';
import { installerSuiviMatomo } from './matomoSpa';

/**
 * Le suivi des pages en navigation Inertia.
 *
 * Ce qui se teste ici n'est pas « une page vue part » — une ligne de code —
 * mais les trois pièges qui ont réellement mordu : la page comptée deux fois,
 * le titre décalé d'un cran, et la page vue envoyée alors que Matomo est
 * désactivé.
 */

/** Une fenêtre factice : DOM minimal, `_paq` observable, trames contrôlées. */
function fenetreFactice(url, titre) {
    const trames = [];

    return {
        _paq: [],
        location: { href: url },
        document: { title: titre },
        requestAnimationFrame: (fn) => trames.push(fn),
        // Fait s'écouler N trames, comme le ferait le navigateur.
        avancer(n = 1) {
            for (let i = 0; i < n; i += 1) {
                const suite = trames.shift();
                if (!suite) return;
                suite();
            }
        },
        trames,
    };
}

/** Un routeur factice qui retient l'écouteur et sait le déclencher. */
function routeurFactice() {
    let ecouteur = null;

    return {
        on: (evenement, fn) => {
            if (evenement === 'navigate') ecouteur = fn;
        },
        naviguer: () => ecouteur?.(),
    };
}

function pagesVues(f) {
    return f._paq.filter((e) => e[0] === 'trackPageView').length;
}

function derniere(f, cle) {
    const trouvees = f._paq.filter((e) => e[0] === cle);

    return trouvees.length ? trouvees[trouvees.length - 1][1] : null;
}

describe('suivi Matomo en navigation Inertia', () => {
    let f;
    let routeur;

    beforeEach(() => {
        f = fenetreFactice('https://faktur.lu/fr', 'Accueil');
        routeur = routeurFactice();
        installerSuiviMatomo(routeur, f);
    });

    it("ne compte pas la page d'arrivée deux fois", () => {
        // Le gabarit a déjà déclaré la page initiale. Une navigation qui
        // n'aboutit pas ailleurs ne doit rien ajouter.
        routeur.naviguer();
        f.avancer(25);

        expect(pagesVues(f)).toBe(0);
    });

    it('déclare la page après un changement d’adresse', () => {
        f.location.href = 'https://faktur.lu/fr/tarifs';
        routeur.naviguer();
        f.document.title = 'Tarifs';
        f.avancer(3);

        expect(pagesVues(f)).toBe(1);
        expect(derniere(f, 'setCustomUrl')).toBe('https://faktur.lu/fr/tarifs');
        expect(derniere(f, 'setReferrerUrl')).toBe('https://faktur.lu/fr');
    });

    /**
     * Le piège qui a mordu : sans attente, la page partait sous le titre de la
     * page précédente. L'URL était juste, le rapport « Titres de pages » faux.
     */
    it('attend le vrai titre plutôt que d’envoyer celui d’avant', () => {
        f.location.href = 'https://faktur.lu/fr/tarifs';
        routeur.naviguer();

        // Le composant Head n'a pas encore posé le nouveau titre.
        f.avancer(2);
        expect(pagesVues(f)).toBe(0);

        f.document.title = 'Tarifs - faktur.lu';
        f.avancer(1);

        expect(pagesVues(f)).toBe(1);
        expect(derniere(f, 'setDocumentTitle')).toBe('Tarifs - faktur.lu');
    });

    it('envoie quand même si le titre ne change jamais', () => {
        // Deux pages peuvent porter le même titre : mieux vaut un titre
        // approximatif qu'une page vue perdue.
        f.location.href = 'https://faktur.lu/fr/autre';
        routeur.naviguer();
        f.avancer(30);

        expect(pagesVues(f)).toBe(1);
        expect(derniere(f, 'setDocumentTitle')).toBe('Accueil');
    });

    it('ignore un rendu qui ne change pas d’adresse', () => {
        // Une soumission de formulaire en erreur revient sur la même URL.
        f.location.href = 'https://faktur.lu/fr/contact';
        routeur.naviguer();
        f.document.title = 'Contact';
        f.avancer(3);

        routeur.naviguer();
        f.avancer(30);

        expect(pagesVues(f)).toBe(1);
    });

    it('réarme le suivi des liens sortants sur le nouveau DOM', () => {
        f.location.href = 'https://faktur.lu/fr/blog';
        routeur.naviguer();
        f.document.title = 'Blog';
        f.avancer(3);

        expect(f._paq.filter((e) => e[0] === 'enableLinkTracking')).toHaveLength(1);
    });

    it('ne fait rien si Matomo est désactivé', () => {
        const sansMatomo = fenetreFactice('https://faktur.lu/fr', 'Accueil');
        delete sansMatomo._paq;

        const r = routeurFactice();
        installerSuiviMatomo(r, sansMatomo);

        sansMatomo.location.href = 'https://faktur.lu/fr/tarifs';

        expect(() => {
            r.naviguer();
            sansMatomo.avancer(30);
        }).not.toThrow();
    });

    it('ne s’installe pas hors navigateur', () => {
        const r = routeurFactice();
        const espion = vi.spyOn(r, 'on');

        installerSuiviMatomo(r, null);

        expect(espion).not.toHaveBeenCalled();
    });
});
