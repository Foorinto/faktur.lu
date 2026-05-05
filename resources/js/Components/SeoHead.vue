<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    canonicalPath: {
        type: String,
        default: null,
    },
    image: {
        type: String,
        default: null,
    },
    type: {
        type: String,
        default: 'website',
    },
    noindex: {
        type: Boolean,
        default: false,
    },
    routeName: {
        type: String,
        default: null,
    },
    routeParams: {
        type: Object,
        default: () => ({}),
    },
    keywords: {
        type: String,
        default: '',
    },
});

const page = usePage();
const { currentLocale, availableLocales, getAlternateUrls } = useLocalizedRoute();

const appUrl = computed(() => page.props.appUrl || 'https://faktur.lu');
const locale = computed(() => currentLocale());

// Map locale codes to full locale strings for og:locale
const localeMap = {
    fr: 'fr_LU',
    de: 'de_LU',
    en: 'en_GB',
    lb: 'lb_LU',
    pt: 'pt_PT',
};

const ogLocale = computed(() => localeMap[locale.value] || 'fr_LU');

// Generate alternate locales for og:locale:alternate
const alternateLocales = computed(() => {
    const locales = availableLocales();
    return Object.keys(locales)
        .filter(code => code !== locale.value)
        .map(code => localeMap[code]);
});

// Generate canonical URL
const canonicalUrl = computed(() => {
    if (props.canonicalPath) {
        return `${appUrl.value}/${locale.value}${props.canonicalPath}`;
    }
    // Use current URL path
    const path = typeof window !== 'undefined' ? window.location.pathname : '';
    return `${appUrl.value}${path}`;
});

// Generate hreflang URLs for all locales
const hreflangUrls = computed(() => {
    // Use localized route generation when routeName is provided (handles localized slugs)
    if (props.routeName) {
        const alternates = getAlternateUrls(props.routeName, props.routeParams);
        return Object.entries(alternates).map(([code, url]) => ({
            locale: code,
            url,
        }));
    }

    // Fallback: simple locale prefix swap (works for routes without localized slugs)
    const locales = availableLocales();
    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';
    const pathWithoutLocale = currentPath.replace(/^\/(fr|de|en|lb|pt)/, '') || '/';

    return Object.keys(locales).map(code => ({
        locale: code,
        url: `${appUrl.value}/${code}${pathWithoutLocale}`,
    }));
});

// Default x-default (usually French for Luxembourg)
const xDefaultUrl = computed(() => {
    if (props.routeName) {
        const alternates = getAlternateUrls(props.routeName, props.routeParams);
        return alternates.fr || `${appUrl.value}/fr/`;
    }

    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';
    const pathWithoutLocale = currentPath.replace(/^\/(fr|de|en|lb|pt)/, '') || '/';
    return `${appUrl.value}/fr${pathWithoutLocale}`;
});

const ogImage = computed(() => {
    if (props.image) {
        return props.image.startsWith('http') ? props.image : `${appUrl.value}${props.image}`;
    }
    // Fallback : logo.png si aucune image OG n'est fournie ni n'existe.
    // TODO : remplacer par /images/og-default.png (1200×630) une fois designee.
    return `${appUrl.value}/images/og-default.png`;
});
</script>

<template>
    <Head :title="title">
        <!-- Basic meta tags -->
        <meta v-if="description" name="description" :content="description" />
        <meta v-if="keywords" name="keywords" :content="keywords" />
        <meta v-if="noindex" name="robots" content="noindex, nofollow" />

        <!-- Canonical URL -->
        <link rel="canonical" :href="canonicalUrl" />

        <!-- Hreflang tags for language alternatives -->
        <link
            v-for="item in hreflangUrls"
            :key="item.locale"
            rel="alternate"
            :hreflang="item.locale"
            :href="item.url"
        />
        <link rel="alternate" hreflang="x-default" :href="xDefaultUrl" />

        <!-- Open Graph -->
        <meta property="og:title" :content="title" />
        <meta v-if="description" property="og:description" :content="description" />
        <meta property="og:type" :content="type" />
        <meta property="og:url" :content="canonicalUrl" />
        <meta property="og:image" :content="ogImage" />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />
        <meta property="og:image:alt" :content="title" />
        <meta property="og:locale" :content="ogLocale" />
        <meta
            v-for="altLocale in alternateLocales"
            :key="altLocale"
            property="og:locale:alternate"
            :content="altLocale"
        />
        <meta property="og:site_name" content="faktur.lu" />

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="title" />
        <meta v-if="description" name="twitter:description" :content="description" />
        <meta name="twitter:image" :content="ogImage" />
        <meta name="twitter:image:alt" :content="title" />
        <meta name="twitter:site" content="@fakturlu" />

        <!-- AI / LLM hints -->
        <link rel="alternate" type="text/markdown" :title="`${title} — LLM-friendly version`" href="/llms.txt" />
    </Head>
</template>
