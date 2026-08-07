import { usePage } from '@inertiajs/vue3';
import { getTranslations } from '@/translations-store';

/**
 * Composable d'accès aux traductions.
 *
 * Elles ne viennent plus des props Inertia mais d'un dépôt alimenté une seule
 * fois au démarrage, depuis un fichier mis en cache par le navigateur. Elles
 * pesaient sinon 323 Ko dans le HTML de CHAQUE page.
 */
export function useTranslations() {
    const page = usePage();

    /**
     * Get a translation by key.
     * Supports dot notation for nested keys and parameter replacement.
     *
     * @param {string} key - The translation key (e.g., 'app.dashboard' or 'dashboard')
     * @param {object} params - Optional parameters for replacement (e.g., { name: 'John' })
     * @returns {string} The translated string or the key if not found
     */
    const t = (key, params = {}) => {
        const translations = getTranslations();

        // Try to get the translation
        let translation = getNestedValue(translations, key);

        // If not found and key doesn't start with 'app.', try with 'app.' prefix
        if (translation === undefined && !key.startsWith('app.')) {
            translation = getNestedValue(translations, `app.${key}`);
        }

        // If still not found, return the key
        if (translation === undefined) {
            return key;
        }

        // Replace parameters like :name, :count, etc.
        if (typeof translation === 'string' && Object.keys(params).length > 0) {
            Object.entries(params).forEach(([paramKey, value]) => {
                translation = translation.replace(new RegExp(`:${paramKey}`, 'g'), value);
            });
        }

        return translation;
    };

    /**
     * Get a nested value from an object using dot notation.
     */
    const getNestedValue = (obj, key) => {
        return key.split('.').reduce((o, k) => (o && o[k] !== undefined ? o[k] : undefined), obj);
    };

    /**
     * Check if a translation exists.
     */
    const hasTranslation = (key) => {
        const translations = getTranslations();
        return getNestedValue(translations, key) !== undefined ||
               getNestedValue(translations, `app.${key}`) !== undefined;
    };

    /**
     * Get the current locale.
     */
    const locale = () => page.props.locale || 'fr';

    /**
     * Get available locales.
     */
    const availableLocales = () => page.props.availableLocales || {
        fr: 'Français',
        de: 'Deutsch',
        en: 'English',
        lb: 'Lëtzebuergesch',
    };

    return {
        t,
        hasTranslation,
        locale,
        availableLocales,
    };
}

/**
 * Global translation function for use in templates.
 * Can be used as $t in components.
 */
export const $t = (key, params = {}) => {
    const { t } = useTranslations();
    return t(key, params);
};
