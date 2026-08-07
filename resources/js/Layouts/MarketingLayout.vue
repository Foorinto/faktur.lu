<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { Link, usePage, router, useForm } from "@inertiajs/vue3";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import HoneypotFields from "@/Components/HoneypotFields.vue";
import FlagIcon from "@/Components/FlagIcon.vue";
import { useLocalizedRoute } from "@/Composables/useLocalizedRoute";
import { useTranslations } from "@/Composables/useTranslations";

const { localizedRoute, currentLocale, availableLocales } = useLocalizedRoute();
const { t } = useTranslations();

const mobileMenuOpen = ref(false);
const featuresDropdownOpen = ref(false);
const solutionsDropdownOpen = ref(false);
const toolsDropdownOpen = ref(false);
const langMenuOpen = ref(false);
const langMenuRef = ref(null);
const page = usePage();

const canLogin = page.props.canLogin ?? true;
const canRegister = page.props.canRegister ?? true;

// Switch locale
const switchLocale = (newLocale) => {
    langMenuOpen.value = false;
    // Rechargement complet plutôt que navigation SPA. Les traductions sont
    // servies dans un fichier à part et chargées AVANT le montage : repartir
    // de zéro garantit que la nouvelle langue est en place au premier rendu,
    // là où une navigation SPA afficherait brièvement la langue précédente.
    // Changer de langue est rare, et recharger est ici le geste juste.
    window.location.href = route("locale.switch", { locale: newLocale });
};

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    if (langMenuRef.value && !langMenuRef.value.contains(event.target)) {
        langMenuOpen.value = false;
    }
};

const newsletterForm = useForm({
    email: "",
    source: "footer",
    homepage_url: "",
    form_loaded_at: "",
});

const submitNewsletter = () => {
    newsletterForm.post(route("newsletter.subscribe"), {
        preserveScroll: true,
        onSuccess: () => newsletterForm.reset(),
    });
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 overflow-x-hidden">
        <!-- Header -->
        <header
            class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-lg border-b border-gray-200"
        >
            <nav class="mx-auto max-w-6xl px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <!-- Logo -->
                    <Link
                        :href="localizedRoute('home')"
                        class="flex items-center"
                    >
                        <ApplicationLogo size="sm" />
                    </Link>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center space-x-6">
                        <div
                            class="relative"
                            @mouseenter="featuresDropdownOpen = true"
                            @mouseleave="featuresDropdownOpen = false"
                        >
                            <Link
                                :href="localizedRoute('features.index')"
                                class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors inline-flex items-center gap-1"
                            >
                                {{ t("landing.nav.features") }}
                                <svg
                                    class="w-3.5 h-3.5 transition-transform"
                                    :class="
                                        featuresDropdownOpen ? 'rotate-180' : ''
                                    "
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </Link>
                            <Transition
                                enter-active-class="transition duration-150 ease-out"
                                enter-from-class="opacity-0 translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition duration-100 ease-in"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 translate-y-1"
                            >
                                <div
                                    v-if="featuresDropdownOpen"
                                    class="absolute left-1/2 -translate-x-1/2 top-full pt-2 z-50"
                                >
                                    <div
                                        class="bg-white rounded-xl shadow-lg border border-gray-200 py-2 w-64"
                                    >
                                        <Link
                                            :href="
                                                localizedRoute(
                                                    'features.show',
                                                    { slug: 'facturation' },
                                                )
                                            "
                                            class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors"
                                        >
                                            <div
                                                class="w-8 h-8 rounded-lg bg-[#9b5de5]/10 flex items-center justify-center flex-shrink-0"
                                            >
                                                <svg
                                                    class="w-4 h-4 text-[#9b5de5]"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                                    />
                                                </svg>
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-medium text-slate-900"
                                                >
                                                    {{
                                                        t(
                                                            "features.invoicing.title",
                                                        )
                                                    }}
                                                </p>
                                                <p
                                                    class="text-xs text-slate-500"
                                                >
                                                    {{
                                                        t(
                                                            "features.invoicing.short_description",
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                        </Link>
                                        <Link
                                            :href="
                                                localizedRoute(
                                                    'features.show',
                                                    { slug: 'faia' },
                                                )
                                            "
                                            class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors"
                                        >
                                            <div
                                                class="w-8 h-8 rounded-lg bg-[#00f5d4]/10 flex items-center justify-center flex-shrink-0"
                                            >
                                                <svg
                                                    class="w-4 h-4 text-[#00f5d4]"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                                    />
                                                </svg>
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-medium text-slate-900"
                                                >
                                                    {{
                                                        t("features.faia.title")
                                                    }}
                                                </p>
                                                <p
                                                    class="text-xs text-slate-500"
                                                >
                                                    {{
                                                        t(
                                                            "features.faia.short_description",
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                        </Link>
                                        <Link
                                            :href="
                                                localizedRoute(
                                                    'features.show',
                                                    { slug: 'peppol' },
                                                )
                                            "
                                            class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors"
                                        >
                                            <div
                                                class="w-8 h-8 rounded-lg bg-[#00bbf9]/10 flex items-center justify-center flex-shrink-0"
                                            >
                                                <svg
                                                    class="w-4 h-4 text-[#00bbf9]"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"
                                                    />
                                                </svg>
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-medium text-slate-900"
                                                >
                                                    {{
                                                        t(
                                                            "features.peppol.title",
                                                        )
                                                    }}
                                                </p>
                                                <p
                                                    class="text-xs text-slate-500"
                                                >
                                                    {{
                                                        t(
                                                            "features.peppol.short_description",
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                        </Link>
                                        <Link
                                            :href="
                                                localizedRoute(
                                                    'features.show',
                                                    { slug: 'gestion-projets' },
                                                )
                                            "
                                            class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors"
                                        >
                                            <div
                                                class="w-8 h-8 rounded-lg bg-[#f15bb5]/10 flex items-center justify-center flex-shrink-0"
                                            >
                                                <svg
                                                    class="w-4 h-4 text-[#f15bb5]"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"
                                                    />
                                                </svg>
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-medium text-slate-900"
                                                >
                                                    {{
                                                        t(
                                                            "features.projects.title",
                                                        )
                                                    }}
                                                </p>
                                                <p
                                                    class="text-xs text-slate-500"
                                                >
                                                    {{
                                                        t(
                                                            "features.projects.short_description",
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                        </Link>
                                        <Link
                                            :href="
                                                localizedRoute(
                                                    'features.show',
                                                    { slug: 'suivi-temps' },
                                                )
                                            "
                                            class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors"
                                        >
                                            <div
                                                class="w-8 h-8 rounded-lg bg-[#fee440]/10 flex items-center justify-center flex-shrink-0"
                                            >
                                                <svg
                                                    class="w-4 h-4 text-[#fee440]"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                                    />
                                                </svg>
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-medium text-slate-900"
                                                >
                                                    {{
                                                        t(
                                                            "features.time-tracking.title",
                                                        )
                                                    }}
                                                </p>
                                                <p
                                                    class="text-xs text-slate-500"
                                                >
                                                    {{
                                                        t(
                                                            "features.time-tracking.short_description",
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                        </Link>
                                        <div
                                            class="border-t border-gray-100 mt-1 pt-1"
                                        >
                                            <Link
                                                :href="
                                                    localizedRoute(
                                                        'features.index',
                                                    )
                                                "
                                                class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors"
                                            >
                                                <div
                                                    class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0"
                                                >
                                                    <svg
                                                        class="w-4 h-4 text-slate-500"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M4 6h16M4 10h16M4 14h16M4 18h16"
                                                        />
                                                    </svg>
                                                </div>
                                                <p
                                                    class="text-sm font-medium text-primary-500"
                                                >
                                                    {{
                                                        t(
                                                            "features.index.learn_more",
                                                        )
                                                    }}
                                                </p>
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                        <!-- Solutions dropdown -->
                        <div
                            class="relative"
                            @mouseenter="solutionsDropdownOpen = true"
                            @mouseleave="solutionsDropdownOpen = false"
                        >
                            <button
                                type="button"
                                class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors inline-flex items-center gap-1"
                            >
                                {{ t("landing.nav.solutions") }}
                                <svg
                                    class="w-3.5 h-3.5 transition-transform"
                                    :class="solutionsDropdownOpen ? 'rotate-180' : ''"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <Transition
                                enter-active-class="transition duration-150 ease-out"
                                enter-from-class="opacity-0 translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition duration-100 ease-in"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 translate-y-1"
                            >
                                <div
                                    v-if="solutionsDropdownOpen"
                                    class="absolute left-1/2 -translate-x-1/2 top-full pt-2 z-50"
                                >
                                    <div class="bg-white rounded-xl shadow-lg border border-gray-200 py-2 w-72">
                                        <Link
                                            :href="localizedRoute('for_freelances')"
                                            class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors"
                                        >
                                            <div class="w-8 h-8 rounded-lg bg-[#00f5d4]/10 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-[#00a896]" fill="currentColor" viewBox="0 0 20 20"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" /></svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-slate-900">{{ t('landing.nav.solutions_freelances') }}</p>
                                                <p class="text-xs text-slate-500">{{ t('landing.nav.solutions_freelances_desc') }}</p>
                                            </div>
                                        </Link>
                                        <Link
                                            :href="localizedRoute('for_smes')"
                                            class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors"
                                        >
                                            <div class="w-8 h-8 rounded-lg bg-[#00bbf9]/10 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-[#00bbf9]" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" /></svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-slate-900">{{ t('landing.nav.solutions_smes') }}</p>
                                                <p class="text-xs text-slate-500">{{ t('landing.nav.solutions_smes_desc') }}</p>
                                            </div>

                                        </Link>
                                        <Link
                                            :href="localizedRoute('partners')"
                                            class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors"
                                        >
                                            <div class="w-8 h-8 rounded-lg bg-[#9b5de5]/10 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-[#9b5de5]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-slate-900">{{ t('landing.nav.solutions_fiduciaries') }}</p>
                                                <p class="text-xs text-slate-500">{{ t('landing.nav.solutions_fiduciaries_desc') }}</p>
                                            </div>
                                        </Link>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                        <Link
                            :href="localizedRoute('pricing')"
                            class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors"
                        >
                            {{ t("landing.nav.pricing") }}
                        </Link>
                        <!-- Outils dropdown -->
                        <div
                            class="relative"
                            @mouseenter="toolsDropdownOpen = true"
                            @mouseleave="toolsDropdownOpen = false"
                        >
                            <Link
                                :href="localizedRoute('tools')"
                                class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors inline-flex items-center gap-1"
                            >
                                {{ t("landing.nav.tools") }}
                                <svg
                                    class="w-3.5 h-3.5 transition-transform"
                                    :class="
                                        toolsDropdownOpen ? 'rotate-180' : ''
                                    "
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </Link>
                            <Transition
                                enter-active-class="transition duration-150 ease-out"
                                enter-from-class="opacity-0 translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition duration-100 ease-in"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 translate-y-1"
                            >
                                <div
                                    v-if="toolsDropdownOpen"
                                    class="absolute left-1/2 -translate-x-1/2 top-full pt-2 z-50"
                                >
                                    <div
                                        class="bg-white rounded-xl shadow-lg border border-gray-200 py-2 w-72"
                                    >
                                        <Link
                                            :href="
                                                localizedRoute(
                                                    'tools.vat_calculator',
                                                )
                                            "
                                            class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors"
                                        >
                                            <div
                                                class="w-8 h-8 rounded-lg bg-[#9b5de5]/10 flex items-center justify-center flex-shrink-0"
                                            >
                                                <svg
                                                    class="w-4 h-4 text-[#9b5de5]"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                                                    />
                                                </svg>
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-medium text-slate-900"
                                                >
                                                    {{
                                                        t(
                                                            "tools.index.vat_calculator.title",
                                                        )
                                                    }}
                                                </p>
                                                <p
                                                    class="text-xs text-slate-500"
                                                >
                                                    {{
                                                        t(
                                                            "tools.index.vat_calculator.description",
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                        </Link>
                                        <Link
                                            :href="
                                                localizedRoute(
                                                    'tools.vat_exemption',
                                                )
                                            "
                                            class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors"
                                        >
                                            <div
                                                class="w-8 h-8 rounded-lg bg-[#00f5d4]/10 flex items-center justify-center flex-shrink-0"
                                            >
                                                <svg
                                                    class="w-4 h-4 text-[#00a896]"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M9 14l6-6m-5.5.5h.01m4.99 5h.01"
                                                    />
                                                </svg>
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-medium text-slate-900"
                                                >
                                                    {{
                                                        t(
                                                            "tools.index.vat_exemption.title",
                                                        )
                                                    }}
                                                </p>
                                                <p
                                                    class="text-xs text-slate-500"
                                                >
                                                    {{
                                                        t(
                                                            "tools.index.vat_exemption.description",
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                        </Link>
                                        <Link
                                            :href="
                                                localizedRoute(
                                                    'tools.iban_validator',
                                                )
                                            "
                                            class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors"
                                        >
                                            <div
                                                class="w-8 h-8 rounded-lg bg-[#00bbf9]/10 flex items-center justify-center flex-shrink-0"
                                            >
                                                <svg
                                                    class="w-4 h-4 text-[#00bbf9]"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                                    />
                                                </svg>
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-medium text-slate-900"
                                                >
                                                    {{
                                                        t(
                                                            "tools.index.iban_validator.title",
                                                        )
                                                    }}
                                                </p>
                                                <p
                                                    class="text-xs text-slate-500"
                                                >
                                                    {{
                                                        t(
                                                            "tools.index.iban_validator.description",
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                        </Link>
                                        <Link
                                            :href="
                                                localizedRoute(
                                                    'tools.invoice_generator',
                                                )
                                            "
                                            class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors"
                                        >
                                            <div
                                                class="w-8 h-8 rounded-lg bg-[#fee440]/30 flex items-center justify-center flex-shrink-0"
                                            >
                                                <svg
                                                    class="w-4 h-4 text-[#d4a500]"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                                    />
                                                </svg>
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-medium text-slate-900"
                                                >
                                                    {{
                                                        t(
                                                            "tools.index.invoice_generator.title",
                                                        )
                                                    }}
                                                </p>
                                                <p
                                                    class="text-xs text-slate-500"
                                                >
                                                    {{
                                                        t(
                                                            "tools.index.invoice_generator.description",
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                        </Link>
                                        <Link
                                            :href="
                                                localizedRoute(
                                                    'tools.templates',
                                                )
                                            "
                                            class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors"
                                        >
                                            <div
                                                class="w-8 h-8 rounded-lg bg-orange-500/10 flex items-center justify-center flex-shrink-0"
                                            >
                                                <svg
                                                    class="w-4 h-4 text-orange-600"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                                    />
                                                </svg>
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-medium text-slate-900"
                                                >
                                                    {{
                                                        t(
                                                            "tools.index.templates.title",
                                                        )
                                                    }}
                                                </p>
                                                <p
                                                    class="text-xs text-slate-500"
                                                >
                                                    {{
                                                        t(
                                                            "tools.index.templates.description",
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                        </Link>
                                        <Link
                                            :href="
                                                localizedRoute('faia-validator')
                                            "
                                            class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors"
                                        >
                                            <div
                                                class="w-8 h-8 rounded-lg bg-[#f15bb5]/10 flex items-center justify-center flex-shrink-0"
                                            >
                                                <svg
                                                    class="w-4 h-4 text-[#f15bb5]"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M9 12l2 2 4-4M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                                                    />
                                                </svg>
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-medium text-slate-900"
                                                >
                                                    {{
                                                        t(
                                                            "tools.index.faia_validator.title",
                                                        )
                                                    }}
                                                </p>
                                                <p
                                                    class="text-xs text-slate-500"
                                                >
                                                    {{
                                                        t(
                                                            "tools.index.faia_validator.description",
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                        </Link>
                                        <div
                                            class="border-t border-gray-100 mt-1 pt-1"
                                        >
                                            <Link
                                                :href="localizedRoute('tools')"
                                                class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors"
                                            >
                                                <div
                                                    class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0"
                                                >
                                                    <svg
                                                        class="w-4 h-4 text-slate-500"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M4 6h16M4 10h16M4 14h16M4 18h16"
                                                        />
                                                    </svg>
                                                </div>
                                                <p
                                                    class="text-sm font-medium text-primary-500"
                                                >
                                                    {{
                                                        t("tools.index.see_all")
                                                    }}
                                                </p>
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                        <Link
                            :href="localizedRoute('blog.index')"
                            class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors"
                        >
                            {{ t("landing.nav.blog") }}
                        </Link>
                    </div>

                    <!-- Desktop CTA -->
                    <div class="hidden md:flex items-center space-x-4">
                        <!-- Language Selector -->
                        <div ref="langMenuRef" class="relative">
                            <button
                                @click.stop="langMenuOpen = !langMenuOpen"
                                class="flex items-center gap-1.5 px-2.5 py-1.5 text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-gray-50 rounded-lg transition-colors"
                            >
                                <FlagIcon
                                    :code="currentLocale()"
                                    class="w-5 h-3.5"
                                />
                                <span class="uppercase text-xs">{{
                                    currentLocale()
                                }}</span>
                                <svg
                                    class="w-4 h-4 transition-transform"
                                    :class="{ 'rotate-180': langMenuOpen }"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </button>

                            <!-- Dropdown -->
                            <Transition
                                enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95"
                            >
                                <div
                                    v-if="langMenuOpen"
                                    class="absolute right-0 mt-2 w-45 bg-white rounded-xl shadow-lg border border-gray-200 py-1 z-50"
                                >
                                    <button
                                        v-for="(
                                            name, code
                                        ) in availableLocales()"
                                        :key="code"
                                        @click="switchLocale(code)"
                                        :class="[
                                            'w-full flex items-center gap-2.5 px-3 py-2 text-sm transition-colors',
                                            currentLocale() === code
                                                ? 'bg-primary-500/10 text-primary-500 font-medium'
                                                : 'text-slate-700 hover:bg-gray-50',
                                        ]"
                                    >
                                        <FlagIcon
                                            :code="code"
                                            class="w-5 h-3.5"
                                        />
                                        <span>{{ name }}</span>
                                        <svg
                                            v-if="currentLocale() === code"
                                            class="w-4 h-4 ml-auto"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </Transition>
                        </div>

                        <Link
                            v-if="$page.props.auth?.user"
                            :href="route('dashboard')"
                            class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors"
                        >
                            {{ t("landing.nav.dashboard") }}
                        </Link>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors"
                            >
                                {{ t("landing.nav.login") }}
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="bg-accent-rose hover:bg-pink-500 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors"
                            >
                                {{ t("landing.nav.free_trial") }}
                            </Link>
                        </template>
                    </div>

                    <!-- Mobile menu button -->
                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        :aria-label="
                            mobileMenuOpen
                                ? t('landing.nav.close_menu')
                                : t('landing.nav.open_menu')
                        "
                        :aria-expanded="mobileMenuOpen"
                        class="md:hidden p-2 text-slate-600 hover:text-slate-900 rounded-lg hover:bg-gray-50"
                    >
                        <svg
                            class="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                v-if="!mobileMenuOpen"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                            <path
                                v-else
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Mobile menu -->
                <div
                    v-if="mobileMenuOpen"
                    class="md:hidden py-4 border-t border-gray-200 mt-4 max-h-[calc(100vh-5rem)] overflow-y-auto overscroll-contain"
                >
                    <div class="flex flex-col space-y-3 pb-4">
                        <Link
                            :href="localizedRoute('features.index')"
                            @click="mobileMenuOpen = false"
                            class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2"
                            >{{ t("landing.nav.features") }}</Link
                        >
                        <div class="pl-4 flex flex-col space-y-1">
                            <Link
                                :href="
                                    localizedRoute('features.show', {
                                        slug: 'facturation',
                                    })
                                "
                                @click="mobileMenuOpen = false"
                                class="text-xs text-slate-500 hover:text-slate-900 py-1"
                                >{{ t("features.invoicing.title") }}</Link
                            >
                            <Link
                                :href="
                                    localizedRoute('features.show', {
                                        slug: 'faia',
                                    })
                                "
                                @click="mobileMenuOpen = false"
                                class="text-xs text-slate-500 hover:text-slate-900 py-1"
                                >{{ t("features.faia.title") }}</Link
                            >
                            <Link
                                :href="
                                    localizedRoute('features.show', {
                                        slug: 'peppol',
                                    })
                                "
                                @click="mobileMenuOpen = false"
                                class="text-xs text-slate-500 hover:text-slate-900 py-1"
                                >{{ t("features.peppol.title") }}</Link
                            >
                            <Link
                                :href="
                                    localizedRoute('features.show', {
                                        slug: 'gestion-projets',
                                    })
                                "
                                @click="mobileMenuOpen = false"
                                class="text-xs text-slate-500 hover:text-slate-900 py-1"
                                >{{ t("features.projects.title") }}</Link
                            >
                            <Link
                                :href="
                                    localizedRoute('features.show', {
                                        slug: 'suivi-temps',
                                    })
                                "
                                @click="mobileMenuOpen = false"
                                class="text-xs text-slate-500 hover:text-slate-900 py-1"
                                >{{ t("features.time-tracking.title") }}</Link
                            >
                        </div>
                        <div class="text-sm font-medium text-slate-600 py-2">{{ t("landing.nav.solutions") }}</div>
                        <div class="pl-4 flex flex-col space-y-1">
                            <Link
                                :href="localizedRoute('for_freelances')"
                                @click="mobileMenuOpen = false"
                                class="text-sm text-slate-500 hover:text-slate-900 py-1"
                                >{{ t("landing.nav.solutions_freelances") }}</Link
                            >
                            <Link
                                :href="localizedRoute('for_smes')"
                                @click="mobileMenuOpen = false"
                                class="text-sm text-slate-500 hover:text-slate-900 py-1"
                                >{{ t("landing.nav.solutions_smes") }}</Link
                            >
                            <Link
                                :href="localizedRoute('partners')"
                                @click="mobileMenuOpen = false"
                                class="text-sm text-slate-500 hover:text-slate-900 py-1"
                                >{{ t("landing.nav.solutions_fiduciaries") }}</Link
                            >
                        </div>
                        <Link
                            :href="localizedRoute('pricing')"
                            @click="mobileMenuOpen = false"
                            class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2"
                            >{{ t("landing.nav.pricing") }}</Link
                        >
                        <Link
                            :href="localizedRoute('tools')"
                            @click="mobileMenuOpen = false"
                            class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2"
                            >{{ t("landing.nav.tools") }}</Link
                        >
                        <div class="pl-4 flex flex-col space-y-1">
                            <Link
                                :href="localizedRoute('tools.vat_calculator')"
                                @click="mobileMenuOpen = false"
                                class="text-xs text-slate-500 hover:text-slate-900 py-1"
                                >{{
                                    t("tools.index.vat_calculator.title")
                                }}</Link
                            >
                            <Link
                                :href="localizedRoute('tools.vat_exemption')"
                                @click="mobileMenuOpen = false"
                                class="text-xs text-slate-500 hover:text-slate-900 py-1"
                                >{{
                                    t("tools.index.vat_exemption.title")
                                }}</Link
                            >
                            <Link
                                :href="localizedRoute('tools.iban_validator')"
                                @click="mobileMenuOpen = false"
                                class="text-xs text-slate-500 hover:text-slate-900 py-1"
                                >{{
                                    t("tools.index.iban_validator.title")
                                }}</Link
                            >
                            <Link
                                :href="
                                    localizedRoute('tools.invoice_generator')
                                "
                                @click="mobileMenuOpen = false"
                                class="text-xs text-slate-500 hover:text-slate-900 py-1"
                                >{{
                                    t("tools.index.invoice_generator.title")
                                }}</Link
                            >
                            <Link
                                :href="localizedRoute('tools.templates')"
                                @click="mobileMenuOpen = false"
                                class="text-xs text-slate-500 hover:text-slate-900 py-1"
                                >{{ t("tools.index.templates.title") }}</Link
                            >
                            <Link
                                :href="localizedRoute('faia-validator')"
                                @click="mobileMenuOpen = false"
                                class="text-xs text-slate-500 hover:text-slate-900 py-1"
                                >{{
                                    t("tools.index.faia_validator.title")
                                }}</Link
                            >
                        </div>
                        <Link
                            :href="localizedRoute('blog.index')"
                            @click="mobileMenuOpen = false"
                            class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2"
                            >{{ t("landing.nav.blog") }}</Link
                        >

                        <!-- Mobile Language Selector -->
                        <div class="pt-3 border-t border-gray-200">
                            <p
                                class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-2"
                            >
                                {{ t("landing.nav.language") }}
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="(name, code) in availableLocales()"
                                    :key="code"
                                    @click="switchLocale(code)"
                                    :class="[
                                        'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                                        currentLocale() === code
                                            ? 'bg-primary-500 text-white'
                                            : 'bg-slate-100 text-slate-700 hover:bg-slate-200',
                                    ]"
                                >
                                    <FlagIcon :code="code" class="w-5 h-3.5" />
                                    <span class="uppercase text-xs">{{
                                        code
                                    }}</span>
                                </button>
                            </div>
                        </div>

                        <template v-if="!$page.props.auth?.user">
                            <Link
                                :href="route('login')"
                                class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2"
                                >{{ t("landing.nav.login") }}</Link
                            >
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="bg-primary-500 text-white text-sm font-semibold px-5 py-3 rounded-xl text-center"
                                >{{ t("landing.nav.free_trial") }}</Link
                            >
                        </template>
                    </div>
                </div>
            </nav>
        </header>

        <!-- Main content with padding for fixed header -->
        <main class="pt-20">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200 py-12 bg-white">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">
                <div class="grid md:grid-cols-5 gap-8 mb-8">
                    <div class="md:col-span-2">
                        <Link
                            :href="localizedRoute('home')"
                            class="flex items-center mb-4"
                        >
                            <ApplicationLogo size="sm" />
                        </Link>
                        <p class="text-slate-600 text-sm max-w-xs mb-5">
                            {{ t("landing.footer.tagline") }}
                        </p>

                        <!-- Newsletter -->
                        <div
                            v-if="page.props.flash?.success === 'newsletter'"
                            class="flex items-center gap-2 text-sm text-emerald-600 bg-emerald-50 rounded-lg px-3 py-2 max-w-xs"
                        >
                            <svg
                                class="w-4 h-4 flex-shrink-0"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            {{ t("landing.footer.newsletter_success") }}
                        </div>
                        <form
                            v-else
                            @submit.prevent="submitNewsletter"
                            class="max-w-xs"
                        >
                            <HoneypotFields
                                v-model:honeypot="newsletterForm.homepage_url"
                                v-model:loadedAt="newsletterForm.form_loaded_at"
                            />
                            <p class="text-sm font-medium text-slate-700 mb-2">
                                {{ t("landing.footer.newsletter_title") }}
                            </p>
                            <div class="flex gap-2">
                                <input
                                    v-model="newsletterForm.email"
                                    type="email"
                                    required
                                    :placeholder="
                                        t(
                                            'landing.footer.newsletter_placeholder',
                                        )
                                    "
                                    class="flex-1 min-w-0 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                />
                                <button
                                    type="submit"
                                    :disabled="newsletterForm.processing"
                                    class="bg-primary-500 hover:bg-primary-600 disabled:bg-slate-400 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors flex-shrink-0"
                                >
                                    {{ t("landing.footer.newsletter_button") }}
                                </button>
                            </div>
                            <p
                                v-if="newsletterForm.errors.email"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ newsletterForm.errors.email }}
                            </p>
                        </form>
                    </div>
                    <div>
                        <h4 class="font-semibold text-slate-900 mb-4">
                            {{ t("landing.footer.product") }}
                        </h4>
                        <ul class="space-y-2 text-sm">
                            <li>
                                <Link
                                    :href="localizedRoute('features.index')"
                                    class="text-slate-600 hover:text-slate-900"
                                    >{{ t("landing.nav.features") }}</Link
                                >
                            </li>
                            <li>
                                <Link
                                    :href="localizedRoute('pricing')"
                                    class="text-slate-600 hover:text-slate-900"
                                    >{{ t("landing.nav.pricing") }}</Link
                                >
                            </li>
                            <li>
                                <Link
                                    :href="localizedRoute('for_freelances')"
                                    class="text-slate-600 hover:text-slate-900"
                                    >{{ t("landing.nav.solutions_freelances") }}</Link
                                >
                            </li>
                            <li>
                                <Link
                                    :href="localizedRoute('for_smes')"
                                    class="text-slate-600 hover:text-slate-900"
                                    >{{ t("landing.nav.solutions_smes") }}</Link
                                >
                            </li>
                            <li>
                                <Link
                                    :href="localizedRoute('faia-validator')"
                                    class="text-slate-600 hover:text-slate-900"
                                    >{{ t("landing.nav.faia_validator") }}</Link
                                >
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-slate-900 mb-4">
                            {{ t("landing.footer.resources") }}
                        </h4>
                        <ul class="space-y-2 text-sm">
                            <li>
                                <Link
                                    :href="localizedRoute('blog.index')"
                                    class="text-slate-600 hover:text-slate-900"
                                    >{{ t("landing.nav.blog") }}</Link
                                >
                            </li>
                            <li>
                                <Link
                                    :href="localizedRoute('glossary')"
                                    class="text-slate-600 hover:text-slate-900"
                                    >{{ t("glossary.breadcrumb") }}</Link
                                >
                            </li>
                            <li>
                                <Link
                                    :href="localizedRoute('partners')"
                                    class="text-slate-600 hover:text-slate-900"
                                    >{{ t("partners.breadcrumb") }}</Link
                                >
                            </li>
                            <li>
                                <Link
                                    :href="localizedRoute('about')"
                                    class="text-slate-600 hover:text-slate-900"
                                    >{{ t("landing.nav.about") }}</Link
                                >
                            </li>
                            <li>
                                <Link
                                    :href="localizedRoute('contact')"
                                    class="text-slate-600 hover:text-slate-900"
                                    >{{ t("landing.nav.contact") }}</Link
                                >
                            </li>
                            <li>
                                <Link
                                    :href="localizedRoute('legal.privacy')"
                                    class="text-slate-600 hover:text-slate-900"
                                    >{{ t("landing.footer.gdpr") }}</Link
                                >
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-slate-900 mb-4">
                            {{ t("landing.footer.legal") }}
                        </h4>
                        <ul class="space-y-2 text-sm">
                            <li>
                                <Link
                                    :href="localizedRoute('legal.mentions')"
                                    class="text-slate-600 hover:text-slate-900"
                                    >{{
                                        t("landing.footer.legal_notice")
                                    }}</Link
                                >
                            </li>
                            <li>
                                <Link
                                    :href="localizedRoute('legal.privacy')"
                                    class="text-slate-600 hover:text-slate-900"
                                    >{{ t("landing.footer.privacy") }}</Link
                                >
                            </li>
                            <li>
                                <Link
                                    :href="localizedRoute('legal.terms')"
                                    class="text-slate-600 hover:text-slate-900"
                                    >{{ t("landing.footer.terms") }}</Link
                                >
                            </li>
                            <li>
                                <Link
                                    :href="localizedRoute('legal.cookies')"
                                    class="text-slate-600 hover:text-slate-900"
                                    >{{ t("landing.footer.cookies") }}</Link
                                >
                            </li>
                            <li>
                                <Link
                                    :href="localizedRoute('legal.dpa')"
                                    class="text-slate-600 hover:text-slate-900"
                                    >{{ t("landing.footer.dpa") }}</Link
                                >
                            </li>
                        </ul>
                    </div>
                </div>
                <div
                    class="pt-8 border-t border-gray-200 flex flex-col md:flex-row items-center justify-between gap-4"
                >
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#00f5d4]/10 text-[#00a896] text-xs font-medium"
                        >
                            <svg
                                class="w-3 h-3"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            FAIA
                        </span>
                        {{ t("landing.footer.aed_compliant") }}
                    </div>

                    <!-- Footer Language Selector -->
                    <div class="flex items-center gap-1">
                        <button
                            v-for="(name, code) in availableLocales()"
                            :key="code"
                            @click="switchLocale(code)"
                            :class="[
                                'px-2 py-1 text-sm rounded transition-colors',
                                currentLocale() === code
                                    ? 'text-primary-500 font-medium'
                                    : 'text-slate-400 hover:text-slate-600',
                            ]"
                            :title="name"
                        >
                            <FlagIcon :code="code" class="w-5 h-3.5" />
                        </button>
                    </div>

                    <p class="text-sm text-slate-500">
                        &copy; {{ new Date().getFullYear() }} faktur.lu -
                        {{ t("landing.footer.all_rights") }}
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
