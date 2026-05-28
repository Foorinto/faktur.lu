<script setup>
import { Link } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import SeoHead from "@/Components/SeoHead.vue";
import MarketingLayout from "@/Layouts/MarketingLayout.vue";
import { useLocalizedRoute } from "@/Composables/useLocalizedRoute";
import { useTranslations } from "@/Composables/useTranslations";

const { localizedRoute } = useLocalizedRoute();
const { t } = useTranslations();

const props = defineProps({
    plans: Array,
});

const billingPeriod = ref("monthly");

const freePlan = computed(() => props.plans.find((p) => p.name === "free"));
const essentielPlan = computed(() =>
    props.plans.find((p) => p.name === "essentiel"),
);
const proPlan = computed(() => props.plans.find((p) => p.name === "pro"));

const formatPrice = (price) => {
    return new Intl.NumberFormat("fr-FR", {
        style: "currency",
        currency: "EUR",
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(price);
};

const freeFeatures = computed(() => {
    const items = t('landing.pricing.plans.free.features');
    return typeof items === 'object' ? Object.values(items) : [];
});

const freeLimitations = computed(() => {
    const items = t('landing.pricing.plans.free.limitations');
    return typeof items === 'object' ? Object.values(items) : [];
});

const essentielFeatures = computed(() => {
    const items = t('landing.pricing.plans.essentiel.features');
    return typeof items === 'object' ? Object.values(items) : [];
});

const proFeatures = computed(() => {
    const items = t('landing.pricing.plans.pro.features');
    return typeof items === 'object' ? Object.values(items) : [];
});
</script>

<template>
    <SeoHead
        :title="t('landing.pricing.page_title')"
        :description="t('landing.pricing.page_description')"
        canonical-path="/tarifs"
        route-name="pricing"
    />

    <MarketingLayout>
        <!-- Hero -->
        <div class="py-16 sm:py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1
                    class="text-4xl sm:text-5xl font-bold text-slate-900 dark:text-white"
                >
                    {{ t('landing.pricing.heading') }}
                </h1>
                <p
                    class="mt-4 text-xl text-slate-600 dark:text-slate-400 max-w-2xl mx-auto"
                >
                    {{ t('landing.pricing.subtitle') }}
                </p>

                <!-- Trial badge -->
                <div
                    class="mt-6 inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 dark:bg-emerald-900/30 rounded-full"
                >
                    <svg
                        class="w-5 h-5 text-emerald-600 dark:text-emerald-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                        />
                    </svg>
                    <span
                        class="text-emerald-700 dark:text-emerald-300 font-medium"
                        >{{ t('landing.pricing.no_credit_card') }}</span
                    >
                </div>

                <!-- Billing toggle -->
                <div class="mt-10 flex justify-center items-center space-x-4">
                    <span
                        :class="[
                            'text-sm font-medium',
                            billingPeriod === 'monthly'
                                ? 'text-slate-900 dark:text-white'
                                : 'text-slate-500',
                        ]"
                    >
                        {{ t('landing.pricing.monthly') }}
                    </span>
                    <button
                        @click="
                            billingPeriod =
                                billingPeriod === 'monthly'
                                    ? 'yearly'
                                    : 'monthly'
                        "
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                        :class="
                            billingPeriod === 'yearly'
                                ? 'bg-primary-500'
                                : 'bg-slate-300'
                        "
                    >
                        <span
                            class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                            :class="
                                billingPeriod === 'yearly'
                                    ? 'translate-x-6'
                                    : 'translate-x-1'
                            "
                        />
                    </button>
                    <span
                        :class="[
                            'text-sm font-medium',
                            billingPeriod === 'yearly'
                                ? 'text-slate-900 dark:text-white'
                                : 'text-slate-500',
                        ]"
                    >
                        {{ t('landing.pricing.yearly') }}
                    </span>
                    <span
                        v-if="billingPeriod === 'yearly'"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300"
                    >
                        {{ t('landing.pricing.two_months_free') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Pricing cards -->
        <div class="pb-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto">
                    <!-- FREE Plan -->
                    <div
                        class="bg-white dark:bg-surface-card rounded-3xl shadow-xl border border-gray-200 dark:border-gray-700 p-8 flex flex-col"
                    >
                        <div>
                            <h3
                                class="text-2xl font-bold text-slate-900 dark:text-white"
                            >
                                {{ t('landing.pricing.plans.free.name') }}
                            </h3>
                            <p class="text-slate-500 dark:text-slate-400">
                                {{ t('landing.pricing.plans.free.description') }}
                            </p>
                        </div>

                        <div class="mt-6">
                            <div class="flex items-baseline">
                                <span
                                    class="text-5xl font-bold text-slate-900 dark:text-white"
                                    >0 €</span
                                >
                                <span class="ml-2 text-slate-500">{{ t('landing.pricing.per_month') }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ t('landing.pricing.forever') }}
                            </p>
                        </div>

                        <div class="mt-8">
                            <Link
                                :href="route('register')"
                                class="block w-full text-center px-6 py-3 border-2 border-gray-300 dark:border-gray-600 text-slate-700 dark:text-slate-300 font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                            >
                                {{ t('landing.pricing.create_account') }}
                            </Link>
                        </div>

                        <div class="mt-8 space-y-4 flex-1">
                            <p
                                class="text-sm font-semibold text-slate-900 dark:text-white"
                            >
                                {{ t('landing.pricing.included') }}
                            </p>
                            <ul class="space-y-3">
                                <li
                                    v-for="feature in freeFeatures"
                                    :key="feature"
                                    class="flex items-start text-sm text-slate-600 dark:text-slate-400"
                                >
                                    <svg
                                        class="h-5 w-5 text-emerald-500 mr-2 flex-shrink-0"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    {{ feature }}
                                </li>
                            </ul>
                            <ul class="space-y-2 pt-2">
                                <li
                                    v-for="limit in freeLimitations"
                                    :key="limit"
                                    class="flex items-start text-sm text-slate-400 dark:text-slate-500"
                                >
                                    <svg
                                        class="h-5 w-5 text-slate-300 dark:text-slate-600 mr-2 flex-shrink-0"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M6 18 18 6M6 6l12 12"
                                        />
                                    </svg>
                                    {{ limit }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Essentiel Plan -->
                    <div
                        class="bg-white dark:bg-surface-card rounded-3xl shadow-xl border border-gray-200 dark:border-gray-700 p-8 flex flex-col"
                    >
                        <div>
                            <h3
                                class="text-2xl font-bold text-slate-900 dark:text-white"
                            >
                                {{ t('landing.pricing.plans.essentiel.name') }}
                            </h3>
                            <p class="text-slate-500 dark:text-slate-400">
                                {{ t('landing.pricing.plans.essentiel.description') }}
                            </p>
                        </div>

                        <div class="mt-6">
                            <div class="flex items-baseline">
                                <span
                                    class="text-5xl font-bold text-slate-900 dark:text-white"
                                >
                                    {{
                                        billingPeriod === "yearly"
                                            ? formatPrice(
                                                  essentielPlan?.monthly_price_when_yearly ||
                                                      4.17,
                                              )
                                            : formatPrice(
                                                  essentielPlan?.price_monthly ||
                                                      5,
                                              )
                                    }}
                                </span>
                                <span class="ml-2 text-slate-500">{{ t('landing.pricing.per_month') }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">
                                <template v-if="billingPeriod === 'yearly'">
                                    {{
                                        formatPrice(
                                            essentielPlan?.price_yearly || 50,
                                        )
                                    }}
                                    {{ t('landing.pricing.billed_yearly') }}
                                </template>
                                <template v-else>
                                    {{ t('landing.pricing.billed_monthly') }}
                                </template>
                            </p>
                        </div>

                        <div class="mt-8">
                            <Link
                                :href="route('register')"
                                class="block w-full text-center px-6 py-3 border-2 border-accent-rose text-accent-rose dark:text-pink-400 font-semibold rounded-xl hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors"
                            >
                                {{ t('landing.pricing.try_free') }}
                            </Link>
                        </div>

                        <div class="mt-8 space-y-4 flex-1">
                            <p
                                class="text-sm font-semibold text-slate-900 dark:text-white"
                            >
                                {{ t('landing.pricing.included') }}
                            </p>
                            <ul class="space-y-3">
                                <li
                                    v-for="feature in essentielFeatures"
                                    :key="feature"
                                    class="flex items-start text-sm text-slate-600 dark:text-slate-400"
                                >
                                    <svg
                                        class="h-5 w-5 text-emerald-500 mr-2 flex-shrink-0"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    {{ feature }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Pro Plan -->
                    <div
                        class="bg-white dark:bg-surface-card rounded-3xl shadow-xl border-2 border-primary-500 p-8 relative flex flex-col"
                    >
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                            <span
                                class="inline-flex items-center px-4 py-1 rounded-full text-sm font-semibold bg-primary-500 text-white"
                            >
                                {{ t('landing.pricing.popular') }}
                            </span>
                        </div>

                        <div>
                            <h3
                                class="text-2xl font-bold text-slate-900 dark:text-white"
                            >
                                {{ t('landing.pricing.plans.pro.name') }}
                            </h3>
                            <p class="text-slate-500 dark:text-slate-400">
                                {{ t('landing.pricing.plans.pro.description') }}
                            </p>
                        </div>

                        <div class="mt-6">
                            <div class="flex items-baseline">
                                <span
                                    class="text-5xl font-bold text-slate-900 dark:text-white"
                                >
                                    {{
                                        billingPeriod === "yearly"
                                            ? formatPrice(
                                                  proPlan?.monthly_price_when_yearly ||
                                                      12.5,
                                              )
                                            : formatPrice(
                                                  proPlan?.price_monthly || 15,
                                              )
                                    }}
                                </span>
                                <span class="ml-2 text-slate-500">{{ t('landing.pricing.per_month') }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">
                                <template v-if="billingPeriod === 'yearly'">
                                    {{
                                        formatPrice(
                                            proPlan?.price_yearly || 150,
                                        )
                                    }}
                                    {{ t('landing.pricing.billed_yearly') }}
                                </template>
                                <template v-else>
                                    {{ t('landing.pricing.billed_monthly') }}
                                </template>
                            </p>
                        </div>

                        <div class="mt-8">
                            <Link
                                :href="route('register')"
                                class="block w-full text-center px-6 py-3 bg-accent-rose hover:bg-pink-500 text-white font-semibold rounded-xl transition-colors"
                            >
                                {{ t('landing.pricing.try_free') }}
                            </Link>
                        </div>

                        <div class="mt-8 space-y-4 flex-1">
                            <p
                                class="text-sm font-semibold text-slate-900 dark:text-white"
                            >
                                {{ t('landing.pricing.everything_in_essentiel') }}
                            </p>
                            <ul class="space-y-3">
                                <li
                                    v-for="feature in proFeatures"
                                    :key="feature"
                                    class="flex items-start text-sm text-slate-600 dark:text-slate-400"
                                >
                                    <svg
                                        class="h-5 w-5 text-emerald-500 mr-2 flex-shrink-0"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    {{ feature }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ -->
        <div class="bg-white dark:bg-surface-card py-24">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-center text-slate-900 dark:text-white mb-12">
                    {{ t('landing.pricing.faq_heading') }}
                </h2>
                <div class="space-y-8">
                    <div v-for="(faq, key) in t('landing.pricing.faq')" :key="key">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                            {{ faq.question }}
                        </h3>
                        <p class="mt-2 text-slate-600 dark:text-slate-400">
                            {{ faq.answer }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feature Comparison Table -->
        <div class="py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-5xl mx-auto">
                    <h2
                        class="text-2xl font-bold text-slate-900 text-center mb-8"
                    >
                        {{ t('landing.pricing.feature_comparison') }}
                    </h2>
                    <div
                        class="bg-white rounded-2xl border border-gray-200 overflow-x-auto"
                    >
                        <table class="w-full min-w-[640px]">
                            <thead>
                                <tr
                                    class="bg-slate-50 border-b border-gray-200"
                                >
                                    <th
                                        class="text-left py-4 px-6 text-sm font-semibold text-slate-900"
                                    >
                                        {{ t('landing.pricing.comparison.columns.feature') }}
                                    </th>
                                    <th
                                        class="text-center py-4 px-4 text-sm font-semibold text-slate-500 w-24"
                                    >
                                        {{ t('landing.pricing.comparison.columns.free') }}
                                    </th>
                                    <th
                                        class="text-center py-4 px-4 text-sm font-semibold text-slate-900 w-24"
                                    >
                                        {{ t('landing.pricing.comparison.columns.essentiel') }}
                                    </th>
                                    <th
                                        class="text-center py-4 px-4 text-sm font-semibold text-primary-500 w-24"
                                    >
                                        {{ t('landing.pricing.comparison.columns.pro') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <!-- Limites -->
                                <tr class="bg-slate-50/50">
                                    <td
                                        colspan="4"
                                        class="py-3 px-6 text-xs font-semibold text-slate-500 uppercase tracking-wider"
                                    >
                                        {{ t('landing.pricing.comparison.sections.limits') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        {{ t('landing.pricing.comparison.rows.clients') }}
                                    </td>
                                    <td
                                        class="py-3 px-4 text-center text-sm text-slate-600"
                                    >
                                        10
                                    </td>
                                    <td
                                        class="py-3 px-4 text-center text-sm text-slate-600"
                                    >
                                        100
                                    </td>
                                    <td
                                        class="py-3 px-4 text-center text-sm font-medium text-primary-500"
                                    >
                                        {{ t('landing.pricing.unlimited') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        {{ t('landing.pricing.comparison.rows.invoices_per_month') }}
                                    </td>
                                    <td
                                        class="py-3 px-4 text-center text-sm text-slate-600"
                                    >
                                        5
                                    </td>
                                    <td
                                        class="py-3 px-4 text-center text-sm text-slate-600"
                                    >
                                        50
                                    </td>
                                    <td
                                        class="py-3 px-4 text-center text-sm font-medium text-primary-500"
                                    >
                                        {{ t('landing.pricing.unlimited') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        {{ t('landing.pricing.comparison.rows.quotes_per_month') }}
                                    </td>
                                    <td
                                        class="py-3 px-4 text-center text-sm text-slate-600"
                                    >
                                        5
                                    </td>
                                    <td
                                        class="py-3 px-4 text-center text-sm text-slate-600"
                                    >
                                        20
                                    </td>
                                    <td
                                        class="py-3 px-4 text-center text-sm font-medium text-primary-500"
                                    >
                                        {{ t('landing.pricing.unlimited') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        {{ t('landing.pricing.comparison.rows.emails_per_month') }}
                                    </td>
                                    <td
                                        class="py-3 px-4 text-center text-sm text-slate-600"
                                    >
                                        10
                                    </td>
                                    <td
                                        class="py-3 px-4 text-center text-sm text-slate-600"
                                    >
                                        100
                                    </td>
                                    <td
                                        class="py-3 px-4 text-center text-sm font-medium text-primary-500"
                                    >
                                        {{ t('landing.pricing.unlimited') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        {{ t('landing.pricing.comparison.rows.expenses_per_month') }}
                                    </td>
                                    <td
                                        class="py-3 px-4 text-center text-sm text-slate-600"
                                    >
                                        10
                                    </td>
                                    <td
                                        class="py-3 px-4 text-center text-sm text-slate-600"
                                    >
                                        30
                                    </td>
                                    <td
                                        class="py-3 px-4 text-center text-sm font-medium text-primary-500"
                                    >
                                        {{ t('landing.pricing.unlimited') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        {{ t('landing.pricing.comparison.rows.active_projects') }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-slate-300 mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </td>
                                    <td
                                        class="py-3 px-4 text-center text-sm text-slate-600"
                                    >
                                        10
                                    </td>
                                    <td
                                        class="py-3 px-4 text-center text-sm font-medium text-primary-500"
                                    >
                                        {{ t('landing.pricing.unlimited') }}
                                    </td>
                                </tr>

                                <!-- Fonctionnalités de base -->
                                <tr class="bg-slate-50/50">
                                    <td
                                        colspan="4"
                                        class="py-3 px-6 text-xs font-semibold text-slate-500 uppercase tracking-wider"
                                    >
                                        {{ t('landing.pricing.comparison.sections.base') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        <Link
                                            :href="
                                                localizedRoute(
                                                    'features.show',
                                                    { slug: 'facturation' },
                                                )
                                            "
                                            class="text-slate-700 hover:text-primary-500 underline decoration-dotted underline-offset-2"
                                            >{{ t('landing.pricing.comparison.rows.compliant_invoices') }}</Link
                                        >
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        <Link
                                            :href="
                                                localizedRoute(
                                                    'features.show',
                                                    { slug: 'devis' },
                                                )
                                            "
                                            class="text-slate-700 hover:text-primary-500 underline decoration-dotted underline-offset-2"
                                            >{{ t('landing.pricing.comparison.rows.professional_quotes') }}</Link
                                        >
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        <Link
                                            :href="
                                                localizedRoute(
                                                    'features.show',
                                                    { slug: 'clients' },
                                                )
                                            "
                                            class="text-slate-700 hover:text-primary-500 underline decoration-dotted underline-offset-2"
                                            >{{ t('landing.pricing.comparison.rows.clients_management') }}</Link
                                        >
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        {{ t('landing.pricing.comparison.rows.credit_notes') }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        <Link
                                            :href="
                                                localizedRoute(
                                                    'features.show',
                                                    { slug: 'depenses' },
                                                )
                                            "
                                            class="text-slate-700 hover:text-primary-500 underline decoration-dotted underline-offset-2"
                                            >{{ t('landing.pricing.comparison.rows.expense_tracking') }}</Link
                                        >
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        {{ t('landing.pricing.comparison.rows.two_fa') }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-6 text-sm text-slate-700">
                                        <Link :href="localizedRoute('features.show', { slug: 'numerotation-personnalisable' })" class="text-slate-700 hover:text-primary-500 underline decoration-dotted underline-offset-2">
                                            {{ t('landing.pricing.comparison.rows.custom_numbering') }}
                                        </Link>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg class="w-5 h-5 text-[#00f5d4] mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg class="w-5 h-5 text-[#00f5d4] mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg class="w-5 h-5 text-[#00f5d4] mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    </td>
                                </tr>

                                <!-- Fonctionnalités Essentiel+ -->
                                <tr class="bg-slate-50/50">
                                    <td
                                        colspan="4"
                                        class="py-3 px-6 text-xs font-semibold text-slate-500 uppercase tracking-wider"
                                    >
                                        {{ t('landing.pricing.comparison.sections.essentiel_plus') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        <Link
                                            :href="
                                                localizedRoute(
                                                    'features.show',
                                                    { slug: 'suivi-temps' },
                                                )
                                            "
                                            class="text-slate-700 hover:text-primary-500 underline decoration-dotted underline-offset-2"
                                            >{{ t('landing.pricing.comparison.rows.time_tracking') }}</Link
                                        >
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-slate-300 mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        <Link
                                            :href="
                                                localizedRoute(
                                                    'features.show',
                                                    { slug: 'gestion-projets' },
                                                )
                                            "
                                            class="text-slate-700 hover:text-primary-500 underline decoration-dotted underline-offset-2"
                                            >{{ t('landing.pricing.comparison.rows.project_management') }}</Link
                                        >
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-slate-300 mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        <Link
                                            :href="
                                                localizedRoute(
                                                    'features.show',
                                                    {
                                                        slug: 'exports-comptables',
                                                    },
                                                )
                                            "
                                            class="text-slate-700 hover:text-primary-500 underline decoration-dotted underline-offset-2"
                                            >{{ t('landing.pricing.comparison.rows.accountant_portal') }}</Link
                                        >
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        <Link
                                            :href="
                                                localizedRoute(
                                                    'features.show',
                                                    {
                                                        slug: 'exports-comptables',
                                                    },
                                                )
                                            "
                                            class="text-slate-700 hover:text-primary-500 underline decoration-dotted underline-offset-2"
                                            >{{ t('landing.pricing.comparison.rows.accounting_exports') }}</Link
                                        >
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-slate-300 mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        <Link
                                            :href="
                                                localizedRoute(
                                                    'features.show',
                                                    { slug: 'peppol' },
                                                )
                                            "
                                            class="text-slate-700 hover:text-primary-500 underline decoration-dotted underline-offset-2"
                                            >{{ t('landing.pricing.comparison.rows.peppol_export') }}</Link
                                        >
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-slate-300 mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </td>
                                    <td
                                        class="py-3 px-4 text-center text-sm text-slate-600"
                                    >
                                        {{ t('landing.pricing.comparison.rows.peppol_per_month') }}
                                    </td>
                                    <td
                                        class="py-3 px-4 text-center text-sm font-medium text-primary-500"
                                    >
                                        {{ t('landing.pricing.unlimited') }}
                                    </td>
                                </tr>

                                <!-- Fonctionnalités Pro -->
                                <tr class="bg-slate-50/50">
                                    <td
                                        colspan="4"
                                        class="py-3 px-6 text-xs font-semibold text-slate-500 uppercase tracking-wider"
                                    >
                                        {{ t('landing.pricing.comparison.sections.pro_plus') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        <Link
                                            :href="
                                                localizedRoute(
                                                    'features.show',
                                                    { slug: 'module-rh' },
                                                )
                                            "
                                            class="text-slate-700 hover:text-primary-500 underline decoration-dotted underline-offset-2"
                                            >{{ t('landing.pricing.comparison.rows.hr_module') }}</Link
                                        >
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-slate-300 mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-slate-300 mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        <Link
                                            :href="
                                                localizedRoute(
                                                    'features.show',
                                                    { slug: 'crm' },
                                                )
                                            "
                                            class="text-slate-700 hover:text-primary-500 underline decoration-dotted underline-offset-2"
                                            >CRM (interactions, rappels,
                                            tags)</Link
                                        >
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-slate-300 mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-slate-300 mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        <Link
                                            :href="
                                                localizedRoute(
                                                    'features.show',
                                                    { slug: 'faia' },
                                                )
                                            "
                                            class="text-slate-700 hover:text-primary-500 underline decoration-dotted underline-offset-2"
                                            >{{ t('landing.pricing.comparison.rows.faia_export') }}</Link
                                        >
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        <Link
                                            :href="
                                                localizedRoute(
                                                    'features.show',
                                                    { slug: 'factur-x' },
                                                )
                                            "
                                            class="text-slate-700 hover:text-primary-500 underline decoration-dotted underline-offset-2"
                                            >{{ t('landing.pricing.comparison.rows.factur_x') }}</Link
                                        >
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-slate-300 mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-slate-300 mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        <Link
                                            :href="
                                                localizedRoute(
                                                    'features.show',
                                                    { slug: 'peppol' },
                                                )
                                            "
                                            class="text-slate-700 hover:text-primary-500 underline decoration-dotted underline-offset-2"
                                            >{{ t('landing.pricing.comparison.rows.peppol_transmission') }}</Link
                                        >
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-slate-300 mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-slate-300 mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        {{ t('landing.pricing.comparison.rows.pdfa_archive') }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-slate-300 mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-slate-300 mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        {{ t('landing.pricing.comparison.rows.auto_reminders') }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-slate-300 mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-slate-300 mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        {{ t('landing.pricing.comparison.rows.no_branding') }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-slate-300 mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-slate-300 mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        class="py-3 px-6 text-sm text-slate-700"
                                    >
                                        {{ t('landing.pricing.comparison.rows.priority_support') }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-slate-300 mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-slate-300 mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <svg
                                            class="w-5 h-5 text-[#00f5d4] mx-auto"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="pb-24">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-bold text-slate-900 dark:text-white">
                    {{ t('landing.pricing.ready_title') }}
                </h2>
                <p class="mt-4 text-xl text-slate-600 dark:text-slate-400">
                    {{ t('landing.pricing.ready_subtitle') }}
                </p>
                <div
                    class="mt-8 flex flex-col sm:flex-row gap-4 justify-center items-center"
                >
                    <Link
                        :href="route('register')"
                        class="inline-flex items-center px-8 py-4 bg-accent-rose hover:bg-pink-500 text-white font-semibold rounded-xl text-lg transition-colors"
                    >
                        {{ t('landing.pricing.try_14_days') }}
                    </Link>
                    <span class="text-slate-500 dark:text-slate-400 text-sm"
                        >{{ t('landing.pricing.no_credit_card') }}</span
                    >
                </div>
            </div>
        </div>

        <!-- Footer -->
    </MarketingLayout>
</template>
