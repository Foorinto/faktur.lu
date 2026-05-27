import { usePage } from '@inertiajs/vue3';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';
import { useTranslations } from '@/Composables/useTranslations';

/**
 * Helpers to generate JSON-LD schemas for /outils/* pages.
 * All helpers return plain objects ready to feed into <SchemaJsonLd>.
 */
export function useToolSchemas() {
    const page = usePage();
    const { t } = useTranslations();
    const { localizedRoute, currentLocale } = useLocalizedRoute();
    const appUrl = () => page.props.appUrl || 'https://faktur.lu';

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
     */
    const webApplication = ({ name, description, url, category = 'BusinessApplication' }) => ({
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
        provider: {
            '@type': 'Organization',
            name: 'faktur.lu',
            url: appUrl() + '/',
        },
        inLanguage: currentLocale(),
        isAccessibleForFree: true,
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

    return { breadcrumb, faqPage, webApplication, howTo };
}
