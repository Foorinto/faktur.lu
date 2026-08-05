<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import EmptyState from "@/Components/EmptyState.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, ref, watch, onMounted } from "vue";
import { useTranslations } from "@/Composables/useTranslations";
import { useTour } from "@/Composables/useTour";

const { t } = useTranslations();
const { startTour } = useTour();

onMounted(() => setTimeout(() => startTour("expenses"), 600));

const props = defineProps({
    expenses: Object,
    summary: Object,
    filters: Object,
    categories: Array,
    years: Array,
    months: Array,
});

// Ce que les totaux recouvrent, dit en toutes lettres. « 1 240 € » ne veut rien
// dire si l'on ne sait plus quel filtre est actif — et l'utilisateur qui vient
// d'arriver par une URL partagée ne l'a jamais su.
const activeFilterLabel = computed(() => {
    const parts = [];

    if (props.filters.category) {
        const found = props.categories.find((c) => c.value === props.filters.category);
        if (found) parts.push(found.label);
    }

    if (props.filters.provider) parts.push(props.filters.provider);

    if (props.filters.month) {
        const found = props.months.find((m) => String(m.value) === String(props.filters.month));
        if (found) parts.push(found.label);
    }

    if (props.filters.year) parts.push(props.filters.year);

    return parts.join(' · ');
});

const categoryFilter = ref(props.filters.category || "");
const yearFilter = ref(props.filters.year || "");
const monthFilter = ref(props.filters.month || "");

const updateFilters = () => {
    router.get(
        route("expenses.index"),
        {
            category: categoryFilter.value || undefined,
            year: yearFilter.value || undefined,
            month: monthFilter.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

watch([categoryFilter, yearFilter, monthFilter], updateFilters);

const formatCurrency = (amount) => {
    return new Intl.NumberFormat("fr-FR", {
        style: "currency",
        currency: "EUR",
    }).format(amount);
};

const formatDate = (date) => {
    if (!date) return "-";
    return new Date(date).toLocaleDateString("fr-FR");
};

const getCategoryLabel = (category) => {
    const found = props.categories.find((c) => c.value === category);
    return found ? found.label : category;
};

const deleteExpense = (expense) => {
    if (confirm(t("delete_expense").replace(":name", expense.provider_name))) {
        router.delete(route("expenses.destroy", expense.id));
    }
};
</script>

<template>
    <Head :title="t('expenses')" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t("expenses") }}
            </h1>
        </template>
        <template #header-actions>
            <Link
                :href="route('settings.purchase-categories')"
                class="mr-2 inline-flex items-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300 dark:hover:bg-gray-800"
            >
                {{ t("purchase_categories.from_expenses") }}
            </Link>
            <Link
                :href="route('expenses.summary')"
                class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300 dark:hover:bg-gray-800"
            >
                <svg
                    class="-ml-0.5 mr-1.5 h-5 w-5"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path
                        d="M15.5 2A1.5 1.5 0 0014 3.5v13a1.5 1.5 0 001.5 1.5h1a1.5 1.5 0 001.5-1.5v-13A1.5 1.5 0 0016.5 2h-1zM9.5 6A1.5 1.5 0 008 7.5v9A1.5 1.5 0 009.5 18h1a1.5 1.5 0 001.5-1.5v-9A1.5 1.5 0 0010.5 6h-1zM3.5 10A1.5 1.5 0 002 11.5v5A1.5 1.5 0 003.5 18h1A1.5 1.5 0 006 16.5v-5A1.5 1.5 0 004.5 10h-1z"
                    />
                </svg>
                {{ t("reports") }}
            </Link>
            <Link
                data-tour="expenses-new-btn"
                :href="route('expenses.create')"
                class="inline-flex items-center rounded-xl bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
            >
                <svg
                    class="-ml-0.5 mr-1.5 h-5 w-5"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path
                        d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z"
                    />
                </svg>
                {{ t("new_expense") }}
            </Link>
        </template>

        <!-- Summary Cards -->
        <p
            v-if="activeFilterLabel"
            class="mb-2 text-sm text-slate-500 dark:text-slate-400"
        >
            {{ t("expenses_totals_for") }}
            <span class="font-medium text-slate-700 dark:text-slate-200">{{ activeFilterLabel }}</span>
        </p>

        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div
                class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-surface-card px-4 py-5"
            >
                <dt
                    class="truncate text-sm font-medium text-slate-500 dark:text-slate-400"
                >
                    {{ t("total_ht") }}
                </dt>
                <dd
                    class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white"
                >
                    {{ formatCurrency(summary.total_ht) }}
                </dd>
            </div>
            <div
                class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-surface-card px-4 py-5"
            >
                <dt
                    class="truncate text-sm font-medium text-slate-500 dark:text-slate-400"
                >
                    {{ t("vat_deductible") }}
                </dt>
                <dd
                    class="mt-1 text-2xl font-semibold text-green-600 dark:text-green-400"
                >
                    {{ formatCurrency(summary.total_vat) }}
                </dd>
            </div>
            <div
                class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-surface-card px-4 py-5"
            >
                <dt
                    class="truncate text-sm font-medium text-slate-500 dark:text-slate-400"
                >
                    {{ t("total_ttc") }}
                </dt>
                <dd
                    class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white"
                >
                    {{ formatCurrency(summary.total_ttc) }}
                </dd>
            </div>
            <div
                class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-surface-card px-4 py-5"
            >
                <dt
                    class="truncate text-sm font-medium text-slate-500 dark:text-slate-400"
                >
                    {{ t("count") }}
                </dt>
                <dd
                    class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white"
                >
                    {{ summary.count }}
                </dd>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-4">
            <select
                v-model="categoryFilter"
                class="rounded-xl border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-primary-600 dark:bg-surface-card dark:text-white dark:ring-slate-600 sm:text-sm"
            >
                <option value="">{{ t("all_categories") }}</option>
                <option
                    v-for="cat in categories"
                    :key="cat.value"
                    :value="cat.value"
                >
                    {{ cat.label }}
                </option>
            </select>

            <select
                v-model="yearFilter"
                class="rounded-xl border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-primary-600 dark:bg-surface-card dark:text-white dark:ring-slate-600 sm:text-sm"
            >
                <option value="">{{ t("all_years") }}</option>
                <option v-for="year in years" :key="year" :value="year">
                    {{ year }}
                </option>
            </select>

            <select
                v-model="monthFilter"
                class="rounded-xl border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-primary-600 dark:bg-surface-card dark:text-white dark:ring-slate-600 sm:text-sm"
            >
                <option value="">{{ t("all_months") }}</option>
                <option
                    v-for="month in months"
                    :key="month.value"
                    :value="month.value"
                >
                    {{ month.label }}
                </option>
            </select>
        </div>

        <!-- Expenses list -->
        <div
            class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-surface-card"
        >
            <table
                class="min-w-full divide-y divide-slate-200 dark:divide-slate-700"
            >
                <thead class="bg-slate-50 dark:bg-gray-800">
                    <tr>
                        <th
                            class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 dark:text-white sm:pl-6"
                        >
                            {{ t("date") }}
                        </th>
                        <th
                            class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white"
                        >
                            {{ t("supplier") }}
                        </th>
                        <th
                            class="hidden px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white md:table-cell"
                        >
                            {{ t("category") }}
                        </th>
                        <th
                            class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900 dark:text-white"
                        >
                            {{ t("ht") }}
                        </th>
                        <th
                            class="hidden px-3 py-3.5 text-right text-sm font-semibold text-slate-900 dark:text-white lg:table-cell"
                        >
                            {{ t("vat") }}
                        </th>
                        <th
                            class="px-3 py-3.5 text-center text-sm font-semibold text-slate-900 dark:text-white"
                        >
                            {{ t("receipt") }}
                        </th>
                        <th class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody
                    class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-surface-card"
                >
                    <tr v-if="expenses.data.length === 0">
                        <td colspan="7">
                            <EmptyState
                                icon="credit-card"
                                :title="t('no_expenses_yet_full')"
                                :description="t('no_expenses_yet_description')"
                                :cta-label="t('add_expense_cta')"
                                :cta-href="route('expenses.create')"
                            />
                        </td>
                    </tr>
                    <tr
                        v-for="expense in expenses.data"
                        :key="expense.id"
                        class="hover:bg-gray-50 dark:hover:bg-gray-800"
                    >
                        <td
                            class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-slate-500 dark:text-slate-400 sm:pl-6"
                        >
                            {{ formatDate(expense.date) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4">
                            <Link
                                :href="route('expenses.show', expense.id)"
                                class="font-medium text-slate-900 hover:text-primary-600 dark:text-white dark:hover:text-primary-400"
                            >
                                {{ expense.provider_name }}
                            </Link>
                            <p
                                v-if="expense.description"
                                class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-xs"
                            >
                                {{ expense.description }}
                            </p>
                        </td>
                        <td
                            class="hidden whitespace-nowrap px-3 py-4 text-sm text-slate-500 dark:text-slate-400 md:table-cell"
                        >
                            <span
                                class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-800 dark:bg-gray-800 dark:text-slate-300"
                            >
                                {{ getCategoryLabel(expense.category) }}
                            </span>
                        </td>
                        <td
                            class="whitespace-nowrap px-3 py-4 text-right text-sm font-medium text-slate-900 dark:text-white"
                        >
                            {{ formatCurrency(expense.amount_ht) }}
                        </td>
                        <td
                            class="hidden whitespace-nowrap px-3 py-4 text-right text-sm text-slate-500 dark:text-slate-400 lg:table-cell"
                        >
                            {{ formatCurrency(expense.amount_vat) }}
                        </td>
                        <td
                            class="whitespace-nowrap px-3 py-4 text-center text-sm"
                        >
                            <a
                                v-if="expense.attachment_url"
                                :href="expense.attachment_url"
                                target="_blank"
                                class="text-primary-600 hover:text-primary-500 dark:text-primary-400"
                                :title="
                                    t('view') + ' ' + t('receipt').toLowerCase()
                                "
                            >
                                <svg
                                    class="h-5 w-5 inline"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M15.621 4.379a3 3 0 00-4.242 0l-7 7a3 3 0 004.241 4.243h.001l.497-.5a.75.75 0 011.064 1.057l-.498.501-.002.002a4.5 4.5 0 01-6.364-6.364l7-7a4.5 4.5 0 016.368 6.36l-3.455 3.553A2.625 2.625 0 119.52 9.52l3.45-3.451a.75.75 0 111.061 1.06l-3.45 3.451a1.125 1.125 0 001.587 1.595l3.454-3.553a3 3 0 000-4.242z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </a>
                            <span
                                v-else
                                class="text-slate-300 dark:text-slate-600"
                                >-</span
                            >
                        </td>
                        <td
                            class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6"
                        >
                            <div class="flex items-center justify-end gap-4">
                                <Link
                                    :href="route('expenses.edit', expense.id)"
                                    class="text-primary-500 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition-colors"
                                    :title="t('edit')"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"
                                        />
                                    </svg>
                                </Link>
                                <button
                                    @click="deleteExpense(expense)"
                                    class="text-red-400 hover:text-red-600 dark:text-red-300 dark:hover:text-red-400 transition-colors"
                                    :title="t('delete')"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div
            v-if="expenses.links && expenses.links.length > 3"
            class="mt-6 flex items-center justify-between"
        >
            <div class="text-sm text-slate-700 dark:text-slate-400">
                {{
                    t("showing_x_to_y_of_z")
                        .replace(":from", expenses.from)
                        .replace(":to", expenses.to)
                        .replace(":count", expenses.total)
                        .replace(":items", t("expenses").toLowerCase())
                }}
            </div>
            <nav class="isolate inline-flex -space-x-px rounded-xl shadow-sm">
                <template v-for="(link, index) in expenses.links" :key="index">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            link.active
                                ? 'z-10 bg-primary-600 text-white'
                                : 'text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-gray-50 dark:text-slate-300 dark:ring-slate-600 dark:hover:bg-gray-800',
                            'relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20',
                            index === 0 ? 'rounded-l-md' : '',
                            index === expenses.links.length - 1
                                ? 'rounded-r-md'
                                : '',
                        ]"
                        v-html="link.label"
                        preserve-scroll
                    />
                </template>
            </nav>
        </div>
    </AppLayout>
</template>
