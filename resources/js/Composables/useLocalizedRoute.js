import { usePage } from '@inertiajs/vue3';

/**
 * Routes that require the locale parameter.
 */
const localizedRoutes = [
    'home',
    'pricing',
    'faia-validator',
    'faia-validator.validate',
    'legal.mentions',
    'legal.privacy',
    'legal.terms',
    'legal.cookies',
    'blog.index',
    'blog.show',
    'blog.category',
    'blog.tag',
];

/**
 * Localized route slugs for SEO-friendly URLs.
 * Maps route names to their localized slugs per language.
 */
const localizedSlugs = {
    'pricing': {
        fr: 'tarifs',
        de: 'preise',
        en: 'pricing',
        lb: 'präisser',
    },
    'faia-validator': {
        fr: 'validateur-faia',
        de: 'faia-validator',
        en: 'faia-validator',
        lb: 'faia-validator',
    },
    'legal.mentions': {
        fr: 'mentions-legales',
        de: 'impressum',
        en: 'legal-notice',
        lb: 'impressum',
    },
    'legal.privacy': {
        fr: 'confidentialite',
        de: 'datenschutz',
        en: 'privacy',
        lb: 'dateschutz',
    },
    'legal.terms': {
        fr: 'cgu',
        de: 'agb',
        en: 'terms',
        lb: 'agb',
    },
    'legal.cookies': {
        fr: 'cookies',
        de: 'cookies',
        en: 'cookies',
        lb: 'cookies',
    },
    'blog.index': {
        fr: 'blog',
        de: 'blog',
        en: 'blog',
        lb: 'blog',
    },
    'blog.category': {
        fr: 'blog/categorie',
        de: 'blog/kategorie',
        en: 'blog/category',
        lb: 'blog/kategorie',
    },
    'blog.tag': {
        fr: 'blog/tag',
        de: 'blog/tag',
        en: 'blog/tag',
        lb: 'blog/tag',
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
                if (name === 'blog.show' && params.post) {
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
