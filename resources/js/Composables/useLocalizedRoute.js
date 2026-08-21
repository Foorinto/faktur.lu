import { usePage } from '@inertiajs/vue3';

/**
 * Routes that require the locale parameter.
 */
const localizedRoutes = [
    'home',
    'features.index',
    'features.show',
    'about',
    'why_faktur',
    'partners',
    'partners.contact',
    'for_freelances',
    'for_smes',
    'glossary',
    'tools',
    'tools.vat_calculator',
    'tools.vat_exemption',
    'tools.iban_validator',
    'tools.invoice_generator',
    'tools.templates',
    'contact',
    'contact.send',
    'pricing',
    'faia-validator',
    'faia-validator.validate',
    'legal.mentions',
    'legal.privacy',
    'legal.terms',
    'legal.cookies',
    'legal.dpa',
    'blog.index',
    'blog.show',
    'blog.category',
    'blog.tag',
];

/**
 * Localized route slugs for SEO-friendly URLs.
 * Maps route names to their localized slugs per language.
 */
/**
 * Pages sectorielles : chemin complet par langue, et slug du métier par langue.
 *
 * Ces deux tables dupliquent `SectorPageController::URL_PATTERNS` et sa table
 * de slugs. La duplication est assumée — le pied de page doit construire ces
 * liens sans aller-retour serveur — mais elle est surveillée : un test PHP
 * compare les deux, et échoue si l'une dérive de l'autre.
 *
 * ⚠️ Chaque slug tient en UN SEUL MOT, sans tiret, dans toutes les langues.
 * Le chemin est `…-{metier}-…` et Symfony compile le paramètre en `[^/]++`,
 * possessif : un tiret dans le slug avale le suffixe et la route ne
 * correspond plus.
 */
export const SECTOR_URL_PATTERNS = {
    fr: 'logiciel-facturation-{metier}-luxembourg',
    de: 'rechnungssoftware-{metier}-luxemburg',
    en: 'invoicing-software-{metier}-luxembourg',
    lb: 'rechnungssoftware-{metier}-letzebuerg',
    pt: 'software-faturacao-{metier}-luxemburgo',
};

export const SECTOR_SLUGS = {
    infirmier: { fr: 'infirmier', de: 'krankenpfleger', en: 'nurses', lb: 'fleegepersonal', pt: 'enfermeiro' },
    artisan: { fr: 'artisan', de: 'handwerker', en: 'tradesmen', lb: 'handwierker', pt: 'construcao' },
    immobilier: { fr: 'immobilier', de: 'immobilienmakler', en: 'realtors', lb: 'immobilien', pt: 'imobiliaria' },
    commerce: { fr: 'commerce', de: 'einzelhandel', en: 'retailers', lb: 'handel', pt: 'comercio' },
    restaurant: { fr: 'restaurant', de: 'gastronomie', en: 'restaurants', lb: 'restaurant', pt: 'restaurante' },
};

/** Chemin d'une page sectorielle, langue comprise. `null` si le métier est inconnu. */
export function sectorPagePath(metier, locale) {
    const slug = SECTOR_SLUGS[metier]?.[locale];
    const motif = SECTOR_URL_PATTERNS[locale];

    if (!slug || !motif) {
        return null;
    }

    return `/${locale}/${motif.replace('{metier}', slug)}`;
}

const localizedSlugs = {
    'features.index': {
        fr: 'fonctionnalites',
        de: 'funktionen',
        en: 'features',
        lb: 'funktiounen',
        pt: 'funcionalidades',
    },
    'features.show': {
        fr: 'fonctionnalites',
        de: 'funktionen',
        en: 'features',
        lb: 'funktiounen',
        pt: 'funcionalidades',
    },
    'about': {
        fr: 'a-propos',
        de: 'ueber-uns',
        en: 'about',
        lb: 'iwwer-eis',
        pt: 'sobre',
    },
    'why_faktur': {
        fr: 'pourquoi-faktur-lu',
        de: 'warum-faktur-lu',
        en: 'why-faktur-lu',
        lb: 'firwat-faktur-lu',
        pt: 'porque-faktur-lu',
    },
    'tools': {
        fr: 'outils',
        de: 'werkzeuge',
        en: 'tools',
        lb: 'handgeschir',
        pt: 'ferramentas',
    },
    'tools.vat_calculator': {
        fr: 'outils/calculateur-tva',
        de: 'werkzeuge/mwst-rechner',
        en: 'tools/vat-calculator',
        lb: 'handgeschir/tva-rechner',
        pt: 'ferramentas/calculadora-iva',
    },
    'tools.vat_exemption': {
        fr: 'outils/franchise-tva',
        de: 'werkzeuge/mwst-befreiung',
        en: 'tools/vat-exemption',
        lb: 'handgeschir/tva-befreiung',
        pt: 'ferramentas/isencao-iva',
    },
    'tools.iban_validator': {
        fr: 'outils/validateur-iban',
        de: 'werkzeuge/iban-pruefer',
        en: 'tools/iban-validator',
        lb: 'handgeschir/iban-validateur',
        pt: 'ferramentas/validador-iban',
    },
    'tools.invoice_generator': {
        fr: 'outils/generateur-facture',
        de: 'werkzeuge/rechnungsgenerator',
        en: 'tools/invoice-generator',
        lb: 'handgeschir/rechnungsgenerator',
        pt: 'ferramentas/gerador-fatura',
    },
    'tools.templates': {
        fr: 'outils/modeles-facture',
        de: 'werkzeuge/vorlagen',
        en: 'tools/templates',
        lb: 'handgeschir/modellen',
        pt: 'ferramentas/modelos',
    },
    'partners': {
        fr: 'partenaires',
        de: 'partner',
        en: 'partners',
        lb: 'partneren',
        pt: 'parceiros',
    },
    'partners.contact': {
        fr: 'partenaires/contact',
        de: 'partners/contact',
        en: 'partners/contact',
        lb: 'partners/contact',
        pt: 'parceiros/contacto',
    },
    'for_freelances': {
        fr: 'pour-freelances',
        de: 'fuer-freelancer',
        en: 'for-freelancers',
        lb: 'fir-freelancer',
        pt: 'para-freelancers',
    },
    'for_smes': {
        fr: 'pour-pme',
        de: 'fuer-kmu',
        en: 'for-smes',
        lb: 'fir-kmu',
        pt: 'para-pme',
    },
    'glossary': {
        fr: 'glossaire',
        de: 'glossar',
        en: 'glossary',
        lb: 'glossaire-lu',
        pt: 'glossario',
    },
    'contact': {
        fr: 'contact',
        de: 'contact',
        en: 'contact',
        lb: 'contact',
        pt: 'contacto',
    },
    'contact.send': {
        fr: 'contact',
        de: 'contact',
        en: 'contact',
        lb: 'contact',
        pt: 'contacto',
    },
    'pricing': {
        fr: 'tarifs',
        de: 'preise',
        en: 'pricing',
        lb: 'präisser',
        pt: 'precos',
    },
    'faia-validator': {
        fr: 'validateur-faia',
        de: 'faia-validator',
        en: 'faia-validator',
        lb: 'faia-validator',
        pt: 'validador-faia',
    },
    'legal.mentions': {
        fr: 'mentions-legales',
        de: 'impressum',
        en: 'legal-notice',
        lb: 'impressum',
        pt: 'aviso-legal',
    },
    'legal.privacy': {
        fr: 'confidentialite',
        de: 'datenschutz',
        en: 'privacy',
        lb: 'dateschutz',
        pt: 'privacidade',
    },
    'legal.terms': {
        fr: 'cgu',
        de: 'agb',
        en: 'terms',
        lb: 'agb',
        pt: 'termos',
    },
    'legal.cookies': {
        fr: 'cookies',
        de: 'cookies',
        en: 'cookies',
        lb: 'cookies',
        pt: 'cookies',
    },
    'legal.dpa': {
        fr: 'dpa',
        de: 'dpa',
        en: 'dpa',
        lb: 'dpa',
        pt: 'dpa',
    },
    'blog.index': {
        fr: 'blog',
        de: 'blog',
        en: 'blog',
        lb: 'blog',
        pt: 'blog',
    },
    'blog.category': {
        fr: 'blog/categorie',
        de: 'blog/kategorie',
        en: 'blog/category',
        lb: 'blog/kategorie',
        pt: 'blog/categoria',
    },
    'blog.tag': {
        fr: 'blog/tag',
        de: 'blog/tag',
        en: 'blog/tag',
        lb: 'blog/tag',
        pt: 'blog/tag',
    },
};

/**
 * Get the localized slug for a route and locale.
 */
function getLocalizedSlug(routeName, locale) {
    if (localizedSlugs[routeName] && localizedSlugs[routeName][locale]) {
        return localizedSlugs[routeName][locale];
    }
    return null;
}

/**
 * Check if a route requires locale parameter.
 */
function isLocalizedRoute(name) {
    return localizedRoutes.includes(name);
}

/**
 * Composable for generating localized routes.
 *
 * Usage:
 * const { localizedRoute, currentLocale } = useLocalizedRoute();
 * localizedRoute('blog.index') // Returns /fr/blog if locale is fr
 * localizedRoute('blog.show', { post: 'my-article' }) // Returns /fr/blog/my-article
 */
export function useLocalizedRoute() {
    const page = usePage();

    /**
     * Get the current locale from the page props or default to 'fr'.
     */
    const currentLocale = () => {
        return page.props.locale || page.props.currentLocale || 'fr';
    };

    /**
     * Generate a localized route.
     *
     * @param {string} name - Route name
     * @param {object|string} params - Route parameters (can be a slug string for simple routes)
     * @param {string|null} locale - Override locale (optional)
     * @returns {string} - The URL
     */
    const localizedRoute = (name, params = {}, locale = null) => {
        const targetLocale = locale || currentLocale();

        // Les pages sectorielles ne suivent pas la forme `/{langue}/{slug}` :
        // le métier est enchâssé au milieu du chemin. Traité ici plutôt qu'en
        // cas particulier plus bas, pour que `getAlternateUrls` en profite et
        // que les balises hreflang soient justes sans code supplémentaire.
        if (name === 'sectors.show') {
            const metier = typeof params === 'string' ? params : params?.metier;

            return sectorPagePath(metier, targetLocale) ?? `/${targetLocale}`;
        }

        // Check if this route has localized slugs
        const localizedSlug = getLocalizedSlug(name, targetLocale);

        if (localizedSlug) {
            // Build URL manually with localized slug
            let url = `/${targetLocale}/${localizedSlug}`;

            // Handle additional route parameters
            if (typeof params === 'string') {
                // Simple slug parameter
                url += `/${params}`;
            } else if (typeof params === 'object') {
                // Handle specific route parameters
                if (name === 'features.show' && params.slug) {
                    url += `/${params.slug}`;
                } else if (name === 'blog.show' && params.post) {
                    url = `/${targetLocale}/blog/${params.post}`;
                } else if (name === 'blog.category' && params.category) {
                    url += `/${params.category}`;
                } else if (name === 'blog.tag' && params.tag) {
                    url += `/${params.tag}`;
                }
            }

            return url;
        }

        // Fallback to standard route generation for routes without localized slugs
        if (isLocalizedRoute(name)) {
            // Handle simple slug parameters (e.g., route('blog.show', 'my-slug'))
            if (typeof params === 'string') {
                // Determine the param name based on route
                if (name === 'blog.show') {
                    params = { locale: targetLocale, post: params };
                } else if (name === 'blog.category') {
                    params = { locale: targetLocale, category: params };
                } else if (name === 'blog.tag') {
                    params = { locale: targetLocale, tag: params };
                } else {
                    params = { locale: targetLocale };
                }
            } else {
                params = { locale: targetLocale, ...params };
            }
        }

        return route(name, params);
    };

    /**
     * Generate a URL to switch to a different locale.
     * Keeps the current path but changes the locale prefix.
     */
    const switchLocaleUrl = (newLocale) => {
        return route('locale.switch', { locale: newLocale });
    };

    /**
     * Get all available locales with their names.
     */
    const availableLocales = () => {
        return page.props.availableLocales || {
            fr: 'Français',
            de: 'Deutsch',
            en: 'English',
            lb: 'Lëtzebuergesch',
            pt: 'Português',
        };
    };

    /**
     * Get all alternate URLs for hreflang tags.
     * Useful for SEO in SeoHead component.
     *
     * @param {string} routeName - The current route name
     * @param {object} params - Route parameters (without locale)
     * @returns {object} - Object with locale as key and URL as value
     */
    const getAlternateUrls = (routeName, params = {}) => {
        const locales = Object.keys(availableLocales());
        const baseUrl = page.props.appUrl || window.location.origin;
        const alternates = {};

        locales.forEach(locale => {
            alternates[locale] = baseUrl + localizedRoute(routeName, params, locale);
        });

        return alternates;
    };

    /**
     * Get localized slugs configuration.
     * Useful for components that need to know all available slugs.
     */
    const getLocalizedSlugs = () => localizedSlugs;

    return {
        localizedRoute,
        currentLocale,
        switchLocaleUrl,
        availableLocales,
        isLocalizedRoute,
        getAlternateUrls,
        getLocalizedSlugs,
    };
}
