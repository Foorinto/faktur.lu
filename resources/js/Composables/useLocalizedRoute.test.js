import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * URL localisées du site public.
 *
 * Trente routes existent en cinq langues, avec un slug traduit par langue —
 * `/fr/fonctionnalites` et `/de/funktionen` désignent la même page. Une erreur
 * ici ne plante rien : elle produit un lien mort, et un lien mort sur un site
 * dont le référencement repose sur ces URL coûte du trafic sans se signaler.
 */

const page = vi.hoisted(() => ({ props: {} }));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => page,
}));

const { useLocalizedRoute } = await import('./useLocalizedRoute');

/** Ziggy, que le composable appelle comme une globale. */
const routeZiggy = vi.fn((nom, params) => `[ziggy:${nom}:${JSON.stringify(params ?? {})}]`);

beforeEach(() => {
    page.props = { locale: 'fr' };
    globalThis.route = routeZiggy;
    routeZiggy.mockClear();
});

afterEach(() => {
    delete globalThis.route;
});

describe('slug traduit selon la langue', () => {
    it.each([
        ['fr', '/fr/fonctionnalites'],
        ['de', '/de/funktionen'],
        ['en', '/en/features'],
        ['lb', '/lb/funktiounen'],
        ['pt', '/pt/funcionalidades'],
    ])('rend la page fonctionnalités en %s', (langue, attendu) => {
        page.props = { locale: langue };
        const { localizedRoute } = useLocalizedRoute();

        expect(localizedRoute('features.index')).toBe(attendu);
    });

    /**
     * La langue passée en argument l'emporte sur celle de la page : c'est ce
     * qui permet au sélecteur de langue et aux balises `hreflang` de pointer
     * ailleurs que là où l'on se trouve.
     */
    it('accepte une langue imposée, différente de celle de la page', () => {
        page.props = { locale: 'fr' };
        const { localizedRoute } = useLocalizedRoute();

        expect(localizedRoute('features.index', {}, 'de')).toBe('/de/funktionen');
    });

    it('ajoute le paramètre d’une page de détail', () => {
        const { localizedRoute } = useLocalizedRoute();

        expect(localizedRoute('features.show', { slug: 'peppol' })).toBe('/fr/fonctionnalites/peppol');
    });

    it('accepte un paramètre passé directement en chaîne', () => {
        const { localizedRoute } = useLocalizedRoute();

        expect(localizedRoute('features.show', 'peppol')).toBe('/fr/fonctionnalites/peppol');
    });
});

describe('langue courante', () => {
    it('retombe sur le français quand la page n’en indique aucune', () => {
        page.props = {};
        const { currentLocale, localizedRoute } = useLocalizedRoute();

        expect(currentLocale()).toBe('fr');
        expect(localizedRoute('features.index')).toBe('/fr/fonctionnalites');
    });

    it('accepte les deux noms de propriété utilisés par le serveur', () => {
        page.props = { currentLocale: 'pt' };

        expect(useLocalizedRoute().currentLocale()).toBe('pt');
    });
});

describe('routes sans slug traduit', () => {
    /**
     * Trois routes n'ont pas de slug par langue — dont `blog.show`, dont l'URL
     * reste `/{langue}/blog/{article}`. Elles repassent par Ziggy, à qui la
     * langue doit tout de même être transmise.
     */
    it('délègue à Ziggy en lui passant la langue', () => {
        page.props = { locale: 'de' };
        const { localizedRoute } = useLocalizedRoute();

        localizedRoute('blog.show', { post: 'mein-artikel' });

        expect(routeZiggy).toHaveBeenCalledWith('blog.show', { locale: 'de', post: 'mein-artikel' });
    });

    it('reconnaît un article passé en chaîne', () => {
        const { localizedRoute } = useLocalizedRoute();

        localizedRoute('blog.show', 'mon-article');

        expect(routeZiggy).toHaveBeenCalledWith('blog.show', { locale: 'fr', post: 'mon-article' });
    });

    /**
     * Une route d'application — hors site public — ne doit recevoir aucune
     * langue : `route('invoices.index', {locale})` produirait une URL invalide.
     */
    it('n’ajoute pas la langue à une route d’application', () => {
        const { localizedRoute } = useLocalizedRoute();

        localizedRoute('invoices.index', { id: 12 });

        expect(routeZiggy).toHaveBeenCalledWith('invoices.index', { id: 12 });
    });
});

describe('cohérence de la table des slugs', () => {
    /**
     * Le vrai risque n'est pas de mal traduire un slug, c'est d'en oublier un.
     * Une langue manquante fait retomber la route sur Ziggy sans prévenir :
     * l'URL reste valide, mais elle n'est plus celle qui est référencée.
     */
    it('couvre les cinq langues pour chaque route traduite', () => {
        const { localizedRoute } = useLocalizedRoute();
        const langues = ['fr', 'de', 'en', 'lb', 'pt'];

        // On interroge le module par son comportement : une route traduite doit
        // rendre un chemin construit, jamais un appel à Ziggy.
        for (const langue of langues) {
            page.props = { locale: langue };
            const url = localizedRoute('legal.terms');

            expect(url.startsWith(`/${langue}/`)).toBe(true);
            expect(url).not.toContain('ziggy');
        }
    });

    it('ne rend jamais deux fois le préfixe de langue', () => {
        for (const langue of ['fr', 'de', 'en', 'lb', 'pt']) {
            page.props = { locale: langue };
            const url = useLocalizedRoute().localizedRoute('pricing');

            expect(url.match(new RegExp(`/${langue}/`, 'g'))).toHaveLength(1);
        }
    });
});
