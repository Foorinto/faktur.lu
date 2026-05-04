<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import BillingNav from "@/Components/BillingNav.vue";
import EmptyState from "@/Components/EmptyState.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, watch, computed, onMounted } from "vue";
import axios from "axios";
import { useTranslations } from "@/Composables/useTranslations";
import { useTour } from "@/Composables/useTour";

const { t } = useTranslations();
const { startTour } = useTour();

onMounted(() => setTimeout(() => startTour("invoices"), 600));

const props = defineProps({
    invoices: Object,
    filters: Object,
    statuses: Array,
    years: Array,
    clients: Array,
});

const statusFilter = ref(props.filters.status || "");
const yearFilter = ref(props.filters.year || "");
const clientFilter = ref(props.filters.client_id || "");

// Preview modal state
const showPreviewModal = ref(false);
const previewHtml = ref("");
const loadingPreview = ref(false);
const previewInvoice = ref(null);

// PDF language selection
const pdfLocale = ref("fr");

const pdfLanguages = [
    { value: "fr", label: "Français", flag: "🇫🇷" },
    { value: "de", label: "Deutsch", flag: "🇩🇪" },
    { value: "en", label: "English", flag: "🇬🇧" },
    { value: "lb", label: "Lëtzebuergesch", flag: "🇱🇺" },
    { value: "pt", label: "Português", flag: "🇵🇹" },
];

const pdfUrl = computed(() => {
    if (!previewInvoice.value) return "";
    const baseUrl =
        previewInvoice.value.status === "draft"
            ? route("invoices.draft-pdf", previewInvoice.value.id)
            : route("invoices.pdf.stream", previewInvoice.value.id);
    return `${baseUrl}?locale=${pdfLocale.value}`;
});

const loadPreview = async () => {
    if (!previewInvoice.value) return;
    loadingPreview.value = true;

    try {
        const routeName =
            previewInvoice.value.status === "draft"
                ? "invoices.preview-draft"
                : "invoices.preview-html";
        const url =
            route(routeName, previewInvoice.value.id) +
            `?locale=${pdfLocale.value}`;
        const response = await axios.get(url);
        previewHtml.value = response.data.html;
    } catch (error) {
        console.error("Error loading preview:", error);
        previewHtml.value = `<p style="color: red; padding: 20px;">${t("error_loading_preview")}</p>`;
    } finally {
        loadingPreview.value = false;
    }
};

const changePdfLanguage = (locale) => {
    pdfLocale.value = locale;
    if (showPreviewModal.value) {
        loadPreview();
    }
};

const openPreview = async (invoice) => {
    previewInvoice.value = invoice;
    pdfLocale.value = invoice.client?.locale || "fr";
    showPreviewModal.value = true;
    loadPreview();
};

const closePreview = () => {
    showPreviewModal.value = false;
    previewHtml.value = "";
    previewInvoice.value = null;
};

const updateFilters = () => {
    router.get(
        route("invoices.index"),
        {
            status: statusFilter.value || undefined,
            year: yearFilter.value || undefined,
            client_id: clientFilter.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

watch([statusFilter, yearFilter, clientFilter], updateFilters);

const getStatusBadgeClass = (status) => {
    const classes = {
        draft: "bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-slate-300",
        finalized:
            "bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400",
        sent: "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400",
        paid: "bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400",
        cancelled:
            "bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400",
    };
    return classes[status] || classes.draft;
};

const getStatusLabel = (status) => {
    const labels = {
        draft: t("draft"),
        finalized: t("finalized"),
        sent: t("sent"),
        paid: t("paid"),
        cancelled: t("cancelled"),
    };
    return labels[status] || status;
};

const formatCurrency = (amount, currency = "EUR") => {
    return new Intl.NumberFormat("fr-FR", {
        style: "currency",
        currency: currency,
    }).format(amount);
};

const formatDate = (date) => {
    if (!date) return "-";
    return new Date(date).toLocaleDateString("fr-FR");
};

// Check if invoice is overdue
const isOverdue = (invoice) => {
    if (!invoice.due_at) return false;
    if (["paid", "cancelled", "draft"].includes(invoice.status)) return false;
    const dueDate = new Date(invoice.due_at);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return dueDate < today;
};

// Get days overdue
const getDaysOverdue = (invoice) => {
    if (!isOverdue(invoice)) return 0;
    const dueDate = new Date(invoice.due_at);
    const today = new Date();
    const diffTime = today - dueDate;
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
};

// Status change functionality
const changingStatus = ref(null);

const getAvailableStatuses = (currentStatus) => {
    // Define allowed transitions
    const transitions = {
        finalized: ["sent", "paid"],
        sent: ["paid"],
    };
    return transitions[currentStatus] || [];
};

const canChangeStatus = (invoice) => {
    // Can change status if finalized or sent (not draft, paid, or cancelled)
    return ["finalized", "sent"].includes(invoice.status);
};

const duplicateInvoice = (invoice) => {
    if (!confirm("Dupliquer cette facture en nouveau brouillon ? Vous pourrez ensuite l'éditer avant de la finaliser.")) return;
    router.post(route("invoices.duplicate", invoice.id), {}, {
        preserveScroll: true,
    });
};

const changeStatus = (invoice, newStatus) => {
    changingStatus.value = invoice.id;

    const routeMap = {
        sent: "invoices.mark-sent",
        paid: "invoices.mark-paid",
    };

    const routeName = routeMap[newStatus];
    if (!routeName) return;

    router.post(
        route(routeName, invoice.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                changingStatus.value = null;
            },
        },
    );
};
</script>

<template>
    <Head :title="t('invoices')" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">
                {{ t("invoices") }}
            </h1>
        </template>
        <template #header-actions>
            <Link
                data-tour="invoices-new-btn"
                :href="route('invoices.create')"
                class="inline-flex items-center rounded-xl bg-accent-rose px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-pink-500 transition-colors"
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
                {{ t("new_invoice") }}
            </Link>
        </template>

        <BillingNav class="mb-6" />

        <!-- Filters -->
        <div data-tour="invoices-filters" class="mb-6 flex flex-wrap gap-4">
            <select
                v-model="statusFilter"
                class="rounded-xl border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-primary-600 dark:bg-surface-card dark:text-white dark:ring-slate-600 sm:text-sm"
            >
                <option value="">{{ t("all_statuses") }}</option>
                <option
                    v-for="status in statuses"
                    :key="status.value"
                    :value="status.value"
                >
                    {{ status.label }}
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
                v-model="clientFilter"
                class="rounded-xl border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-primary-600 dark:bg-surface-card dark:text-white dark:ring-slate-600 sm:text-sm"
            >
                <option value="">{{ t("all_clients") }}</option>
                <option
                    v-for="client in clients"
                    :key="client.id"
                    :value="client.id"
                >
                    {{ client.name }}
                </option>
            </select>
        </div>

        <!-- Invoices list -->
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
                            {{ t("number") }}
                        </th>
                        <th
                            class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white"
                        >
                            {{ t("client") }}
                        </th>
                        <th
                            class="hidden px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white md:table-cell"
                        >
                            {{ t("date") }}
                        </th>
                        <th
                            class="hidden px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white lg:table-cell"
                        >
                            {{ t("due_date") }}
                        </th>
                        <th
                            class="hidden px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white lg:table-cell"
                        >
                            {{ t("paid_on") }}
                        </th>
                        <th
                            class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900 dark:text-white"
                        >
                            {{ t("total") }} {{ t("ttc") }}
                        </th>
                        <th
                            class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white"
                        >
                            {{ t("status") }}
                        </th>
                        <th class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">{{ t("actions") }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody
                    class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-surface-card"
                >
                    <tr v-if="invoices.data.length === 0">
                        <td colspan="7">
                            <EmptyState
                                icon="document"
                                title="Aucune facture pour le moment"
                                description="Créez votre première facture conforme au Luxembourg en quelques clics."
                                cta-label="Créer ma première facture"
                                :cta-href="route('invoices.create')"
                            />
                        </td>
                    </tr>
                    <tr
                        v-for="invoice in invoices.data"
                        :key="invoice.id"
                        :class="[
                            isOverdue(invoice)
                                ? 'bg-pink-50/50 hover:bg-pink-50 dark:bg-pink-900/10 dark:hover:bg-pink-900/20'
                                : 'hover:bg-gray-50 dark:hover:bg-gray-800',
                        ]"
                    >
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-6">
                            <Link
                                :href="
                                    invoice.status === 'draft'
                                        ? route('invoices.edit', invoice.id)
                                        : route('invoices.show', invoice.id)
                                "
                                class="font-semibold text-slate-900 hover:text-primary-600 dark:text-white dark:hover:text-primary-400 transition-colors"
                            >
                                <span
                                    v-if="invoice.type === 'credit_note'"
                                    class="text-pink-600 dark:text-pink-400"
                                >
                                    NC-
                                </span>
                                {{ invoice.number || t("draft").toUpperCase() }}
                            </Link>
                            <p
                                v-if="invoice.title"
                                class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-[200px]"
                            >
                                {{ invoice.title }}
                            </p>
                        </td>
                        <td
                            class="px-3 py-4 text-sm text-slate-600 dark:text-slate-400 max-w-[180px] truncate"
                        >
                            {{ invoice.client?.name }}
                        </td>
                        <td
                            class="hidden whitespace-nowrap px-3 py-4 text-sm text-slate-500 dark:text-slate-400 md:table-cell"
                        >
                            {{ formatDate(invoice.issued_at) }}
                        </td>
                        <td
                            class="hidden whitespace-nowrap px-3 py-4 text-sm lg:table-cell"
                        >
                            <div class="flex items-center gap-1.5">
                                <!-- Overdue warning icon -->
                                <svg
                                    v-if="isOverdue(invoice)"
                                    class="h-4 w-4 text-pink-500 flex-shrink-0"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    :title="
                                        t('overdue_days', {
                                            days: getDaysOverdue(invoice),
                                        })
                                    "
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                <span
                                    :class="
                                        isOverdue(invoice)
                                            ? 'text-pink-600 dark:text-pink-400 font-medium'
                                            : 'text-slate-500 dark:text-slate-400'
                                    "
                                >
                                    {{ formatDate(invoice.due_at) }}
                                </span>
                            </div>
                        </td>
                        <td
                            class="hidden whitespace-nowrap px-3 py-4 text-sm lg:table-cell"
                        >
                            <span v-if="invoice.paid_at" class="text-emerald-600 dark:text-emerald-400">
                                {{ formatDate(invoice.paid_at) }}
                            </span>
                            <span v-else class="text-slate-300 dark:text-slate-600">—</span>
                        </td>
                        <td
                            class="whitespace-nowrap px-3 py-4 text-right text-sm font-semibold"
                            :class="
                                invoice.type === 'credit_note'
                                    ? 'text-pink-600 dark:text-pink-400'
                                    : 'text-slate-900 dark:text-white'
                            "
                        >
                            {{
                                formatCurrency(
                                    invoice.total_ttc,
                                    invoice.currency,
                                )
                            }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <div class="flex items-center gap-2">
                                <!-- Dropdown for changeable statuses -->
                                <div
                                    v-if="canChangeStatus(invoice)"
                                    class="inline-flex relative"
                                >
                                    <select
                                        :value="invoice.status"
                                        @change="
                                            changeStatus(
                                                invoice,
                                                $event.target.value,
                                            )
                                        "
                                        :disabled="
                                            changingStatus === invoice.id
                                        "
                                        :class="[
                                            getStatusBadgeClass(invoice.status),
                                            'appearance-none cursor-pointer rounded-xl pl-3 pr-7 py-1 text-xs font-semibold border-0 focus:ring-2 focus:ring-primary-500',
                                            changingStatus === invoice.id
                                                ? 'opacity-50'
                                                : '',
                                        ]"
                                    >
                                        <option :value="invoice.status">
                                            {{ getStatusLabel(invoice.status) }}
                                        </option>
                                        <option
                                            v-for="status in getAvailableStatuses(
                                                invoice.status,
                                            )"
                                            :key="status"
                                            :value="status"
                                        >
                                            → {{ getStatusLabel(status) }}
                                        </option>
                                    </select>
                                </div>
                                <!-- Static badge for non-changeable statuses -->
                                <span
                                    v-else
                                    :class="getStatusBadgeClass(invoice.status)"
                                    class="inline-flex items-center rounded-xl px-3 py-1 text-xs font-semibold"
                                >
                                    {{ getStatusLabel(invoice.status) }}
                                </span>
                                <!-- Overdue badge -->
                                <span
                                    v-if="isOverdue(invoice)"
                                    class="inline-flex items-center gap-1 rounded-xl bg-pink-100 px-2 py-0.5 text-xs font-semibold text-pink-700 dark:bg-pink-900/30 dark:text-pink-400"
                                    :title="
                                        t('overdue_days', {
                                            days: getDaysOverdue(invoice),
                                        })
                                    "
                                >
                                    <svg
                                        class="h-3 w-3"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    {{ t("overdue") }}
                                </span>
                            </div>
                        </td>
                        <td
                            class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6"
                        >
                            <div class="flex items-center justify-end gap-2">
                                <!-- Preview button -->
                                <button
                                    type="button"
                                    @click="openPreview(invoice)"
                                    class="text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 transition-colors"
                                    :title="t('preview')"
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
                                            d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.64 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.64 0-8.573-3.007-9.963-7.178z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                    </svg>
                                </button>
                                <!-- PDF download -->
                                <a
                                    :href="
                                        invoice.status === 'draft'
                                            ? route(
                                                  'invoices.draft-pdf',
                                                  invoice.id,
                                              )
                                            : route(
                                                  'invoices.pdf.stream',
                                                  invoice.id,
                                              )
                                    "
                                    target="_blank"
                                    class="text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 transition-colors"
                                    :title="t('download_pdf')"
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
                                            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"
                                        />
                                    </svg>
                                </a>
                                <!-- Edit (draft) -->
                                <Link
                                    v-if="invoice.status === 'draft'"
                                    :href="route('invoices.edit', invoice.id)"
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
                                <!-- View (finalized) -->
                                <Link
                                    v-else
                                    :href="route('invoices.show', invoice.id)"
                                    class="text-primary-500 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition-colors"
                                    :title="t('view')"
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
                                            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"
                                        />
                                    </svg>
                                </Link>
                                <!-- Duplicate (any invoice except credit notes) -->
                                <button
                                    v-if="invoice.type !== 'credit_note'"
                                    @click="duplicateInvoice(invoice)"
                                    type="button"
                                    class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 transition-colors"
                                    title="Dupliquer en brouillon"
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
                                            d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"
                                        />
                                    </svg>
                                </button>
                                <!-- Finalize (draft only) -->
                                <Link
                                    v-if="invoice.status === 'draft'"
                                    :href="
                                        route('invoices.finalize', invoice.id)
                                    "
                                    method="post"
                                    as="button"
                                    class="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors"
                                    :title="t('finalize')"
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
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                    <span class="text-sm font-medium">{{
                                        t("finalize")
                                    }}</span>
                                </Link>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div
            v-if="invoices.links && invoices.links.length > 3"
            class="mt-6 flex items-center justify-between"
        >
            <div class="text-sm text-slate-600 dark:text-slate-400">
                {{
                    t("showing_x_to_y_of_z", {
                        from: invoices.from,
                        to: invoices.to,
                        count: invoices.total,
                        items: t("invoices").toLowerCase(),
                    })
                }}
            </div>
            <nav
                class="isolate inline-flex -space-x-px rounded-xl shadow-sm overflow-hidden"
            >
                <template v-for="(link, index) in invoices.links" :key="index">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            link.active
                                ? 'z-10 bg-accent-rose text-white'
                                : 'text-slate-700 bg-white ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:text-slate-300 dark:bg-surface-card dark:ring-gray-700 dark:hover:bg-gray-800',
                            'relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20 transition-colors',
                            index === 0 ? 'rounded-l-xl' : '',
                            index === invoices.links.length - 1
                                ? 'rounded-r-xl'
                                : '',
                        ]"
                        v-html="link.label"
                        preserve-scroll
                    />
                </template>
            </nav>
        </div>

        <!-- Preview Modal -->
        <div v-if="showPreviewModal" class="fixed inset-0 z-50 overflow-hidden">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div
                    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"
                    @click="closePreview"
                ></div>

                <div
                    class="relative bg-white dark:bg-surface-card rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col border border-gray-200 dark:border-gray-700"
                >
                    <!-- Modal header -->
                    <div
                        class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"
                    >
                        <h3
                            class="text-lg font-semibold text-slate-900 dark:text-white"
                        >
                            <span
                                v-if="previewInvoice?.type === 'credit_note'"
                                class="text-pink-600 dark:text-pink-400"
                                >NC-</span
                            >{{ previewInvoice?.number || t("draft") }}
                        </h3>
                        <div class="flex items-center space-x-3">
                            <!-- Language selector -->
                            <div
                                class="flex items-center border border-gray-300 dark:border-gray-700 rounded-xl overflow-hidden"
                            >
                                <button
                                    v-for="lang in pdfLanguages"
                                    :key="lang.value"
                                    type="button"
                                    @click="changePdfLanguage(lang.value)"
                                    :title="lang.label"
                                    class="px-2.5 py-1.5 text-base transition-colors"
                                    :class="
                                        pdfLocale === lang.value
                                            ? 'bg-primary-100 dark:bg-primary-900/30'
                                            : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800'
                                    "
                                >
                                    {{ lang.flag }}
                                </button>
                            </div>
                            <a
                                :href="pdfUrl"
                                target="_blank"
                                class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-gray-50 transition-colors dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300"
                            >
                                <svg
                                    class="h-4 w-4 mr-1"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z"
                                    />
                                    <path
                                        d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z"
                                    />
                                </svg>
                                PDF
                            </a>
                            <button
                                type="button"
                                @click="closePreview"
                                class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors"
                            >
                                <svg
                                    class="h-6 w-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal body -->
                    <div
                        class="flex-1 overflow-auto p-6 bg-slate-100 dark:bg-surface-dark"
                    >
                        <div
                            v-if="loadingPreview"
                            class="flex items-center justify-center h-96"
                        >
                            <div
                                class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500"
                            ></div>
                        </div>
                        <iframe
                            v-else
                            :srcdoc="previewHtml"
                            class="bg-white shadow-xl rounded-lg mx-auto block border-0"
                            style="
                                width: 210mm;
                                height: 297mm;
                                color-scheme: light;
                            "
                        ></iframe>
                    </div>

                    <!-- Modal footer -->
                    <div
                        class="flex items-center justify-end px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-800/50"
                    >
                        <button
                            type="button"
                            @click="closePreview"
                            class="inline-flex items-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-gray-50 transition-colors dark:bg-slate-600 dark:text-white dark:ring-slate-500"
                        >
                            {{ t("close") }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
