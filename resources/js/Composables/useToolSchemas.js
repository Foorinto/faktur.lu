import { usePage } from '@inertiajs/vue3';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';
import { useTranslations } from '@/Composables/useTranslations';

/**
 * Helpers to generate JSON-LD schemas for /outils/* pages.
 * All helpers return plain objects ready to feed into <SchemaJsonLd>.
 *
 * LLM/AI authority signals included:
 * - provider.sameAs → Wikidata Q139674760 (faktur.lu)
 * - about → Wikidata entities (Luxembourg Q32, VAT Q210601, IBAN Q193739)
 * - dateModified → current page render time (freshness signal for ChatGPT/Perplexity/Gemini)
 */
export function useToolSchemas() {
    const page = usePage();
    const { t } = useTranslations();
    const { localizedRoute, currentLocale } = useLocalizedRoute();
    const appUrl = () => page.props.appUrl || 'https://faktur.lu';

    // Today (YYYY-MM-DD) — used as dateModified on schemas for freshness signal.
    const today = () => new Date().toISOString().slice(0, 10);

    // The canonical Organization payload (with Wikidata sameAs) reused across schemas.
    const organization = () => ({
        '@type': 'Organization',
        '@id': appUrl() + '/#organization',
        name: 'faktur.lu',
        url: appUrl() + '/',
        sameAs: [
            'https://www.wikidata.org/wiki/Q139674760',
        ],
    });

    // Wikidata entities most-likely to be referenced from /outils/* pages.
    const wikidata = {
        luxembourg: { '@type': 'Country', name: 'Luxembourg', sameAs: 'https://www.wikidata.org/wiki/Q32' },
        vat: { '@type': 'Thing', name: 'Value-added tax', sameAs: 'https://www.wikidata.org/wiki/Q210601' },
        iban: { '@type': 'Thing', name: 'International Bank Account Number', sameAs: 'https://www.wikidata.org/wiki/Q193739' },
        invoice: { '@type': 'Thing', name: 'Invoice', sameAs: 'https://www.wikidata.org/wiki/Q330362' },
    };

    /**
     * BreadcrumbList: faktur.lu > Outils > {currentTool}.
     */
    const breadcrumb = (currentToolName) => ({
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: [
            {
                '@type': 'ListItem',
                position: 1,
                name: 'faktur.lu',
                item: appUrl() + '/' + currentLocale() + '/',
            },
            {
                '@type': 'ListItem',
                position: 2,
                name: t('tools.index.breadcrumb'),
                item: appUrl() + localizedRoute('tools'),
            },
            {
                '@type': 'ListItem',
                position: 3,
                name: currentToolName,
            },
        ],
    });

    /**
     * FAQPage from an array of {q, a} objects.
     */
    const faqPage = (qaArray) => ({
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        mainEntity: (qaArray || []).map((item) => ({
            '@type': 'Question',
            name: item.q,
            acceptedAnswer: {
                '@type': 'Answer',
                text: item.a,
            },
        })),
    });

    /**
     * WebApplication for interactive tools (calculators, validators, generators).
     * Includes LLM authority signals: provider.sameAs (Wikidata), about, dateModified.
     */
    const webApplication = ({ name, description, url, category = 'BusinessApplication', about = [] }) => ({
        '@context': 'https://schema.org',
        '@type': 'WebApplication',
        name,
        description,
        url: appUrl() + url,
        applicationCategory: category,
        operatingSystem: 'Any',
        offers: {
            '@type': 'Offer',
            price: '0',
            priceCurrency: 'EUR',
        },
        provider: organization(),
        inLanguage: currentLocale(),
        isAccessibleForFree: true,
        dateModified: today(),
        ...(about.length > 0 ? { about } : {}),
    });

    /**
     * HowTo for step-by-step tool usage.
     * steps = [{name, text}, ...]
     */
    const howTo = ({ name, description, steps, totalTimeMin = 1 }) => ({
        '@context': 'https://schema.org',
        '@type': 'HowTo',
        name,
        description,
        totalTime: `PT${totalTimeMin}M`,
        step: (steps || []).map((s, i) => ({
            '@type': 'HowToStep',
            position: i + 1,
            name: s.name,
            text: s.text,
        })),
    });

    return { breadcrumb, faqPage, webApplication, howTo, organization, wikidata };
}
