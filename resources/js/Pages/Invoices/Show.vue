<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    invoice: Object,
});

const processing = ref(false);
const showCreditNoteModal = ref(false);
const creditNoteType = ref('full'); // 'full' or 'partial'
const selectedItemIds = ref([]);

// Preview modal state
const showPreviewModal = ref(false);
const previewHtml = ref('');
const loadingPreview = ref(false);

// PDF language selection
const pdfLocale = ref(props.invoice.buyer_snapshot?.locale || 'fr');

const pdfLanguages = [
    { value: 'fr', label: 'Français', flag: '🇫🇷' },
    { value: 'de', label: 'Deutsch', flag: '🇩🇪' },
    { value: 'en', label: 'English', flag: '🇬🇧' },
    { value: 'lb', label: 'Lëtzebuergesch', flag: '🇱🇺' },
];

const pdfUrl = computed(() => {
    const baseUrl = route('invoices.pdf.stream', props.invoice.id);
    return `${baseUrl}?locale=${pdfLocale.value}`;
});

// Load preview with locale
const loadPreview = async () => {
    loadingPreview.value = true;
    try {
        const url = route('invoices.preview-html', props.invoice.id) + `?locale=${pdfLocale.value}`;
        const response = await axios.get(url);
        previewHtml.value = response.data.html;
    } catch (error) {
        console.error('Error loading preview:', error);
        previewHtml.value = `<p style="color: red; padding: 20px;">${t('error_loading_preview')}</p>`;
    } finally {
        loadingPreview.value = false;
    }
};

// Reload preview when language changes
const changePdfLanguage = (locale) => {
    pdfLocale.value = locale;
    if (showPreviewModal.value) {
        loadPreview();
    }
};

const openPreview = () => {
    showPreviewModal.value = true;
    loadPreview();
};

const creditNoteForm = useForm({
    reason: 'cancellation',
    item_ids: null,
});

// Email sending state
const showEmailModal = ref(false);
const emailForm = useForm({
    recipient_email: props.invoice.buyer_snapshot?.email || '',
    subject: `${props.invoice.type === 'credit_note' ? 'Avoir' : 'Facture'} ${props.invoice.number}`,
    message: '',
    send_copy_to_self: false,
});

// Reminder state
const showReminderModal = ref(false);
const reminderLevel = ref(1);
const reminderForm = useForm({
    level: 1,
    recipient_email: props.invoice.buyer_snapshot?.email || '',
    subject: '',
    message: '',
});

// Email history
const showEmailHistory = ref(false);
const emailHistory = ref([]);
const loadingHistory = ref(false);

const canSendEmail = computed(() => {
    return props.invoice.status !== 'draft';
});

const canSendReminder = computed(() => {
    return ['finalized', 'sent'].includes(props.invoice.status) && props.invoice.type !== 'credit_note';
});

const canExportPeppol = computed(() => {
    return props.invoice.status !== 'draft'
        && props.invoice.seller_snapshot?.peppol_endpoint_id
        && props.invoice.seller_snapshot?.peppol_endpoint_scheme
        && props.invoice.buyer_snapshot?.peppol_endpoint_id
        && props.invoice.buyer_snapshot?.peppol_endpoint_scheme;
});

const isOverdue = computed(() => {
    if (!props.invoice.due_at) return false;
    return new Date(props.invoice.due_at) < new Date() && !['paid', 'cancelled'].includes(props.invoice.status);
});

const daysOverdue = computed(() => {
    if (!props.invoice.due_at || !isOverdue.value) return 0;
    const due = new Date(props.invoice.due_at);
    const now = new Date();
    return Math.floor((now - due) / (1000 * 60 * 60 * 24));
});

const openEmailModal = () => {
    emailForm.recipient_email = props.invoice.buyer_snapshot?.email || '';
    emailForm.subject = `${props.invoice.type === 'credit_note' ? 'Avoir' : 'Facture'} ${props.invoice.number}`;
    emailForm.message = '';
    emailForm.send_copy_to_self = false;
    showEmailModal.value = true;
};

const sendEmail = () => {
    emailForm.post(route('invoices.send-email', props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEmailModal.value = false;
            emailForm.reset();
        },
    });
};

const openReminderModal = (level = 1) => {
    reminderLevel.value = level;
    reminderForm.level = level;
    reminderForm.recipient_email = props.invoice.buyer_snapshot?.email || '';

    // Set default subject and message based on level
    const clientName = props.invoice.buyer_snapshot?.name || 'Client';
    const amount = formatCurrency(props.invoice.total_ttc, props.invoice.currency);
    const dueDate = formatDate(props.invoice.due_at);
    const companyName = props.invoice.seller_snapshot?.company_name || '';

    if (level === 1) {
        reminderForm.subject = `Rappel : Facture ${props.invoice.number} en attente de paiement`;
        reminderForm.message = `Bonjour ${clientName},\n\nNous nous permettons de vous rappeler que la facture ${props.invoice.number} d'un montant de ${amount} est arrivée à échéance le ${dueDate}.\n\nNous vous remercions de bien vouloir procéder au règlement dans les meilleurs délais.\n\nCordialement,\n${companyName}`;
    } else if (level === 2) {
        reminderForm.subject = `Relance : Facture ${props.invoice.number} impayée`;
        reminderForm.message = `Bonjour ${clientName},\n\nMalgré notre précédent rappel, nous constatons que la facture ${props.invoice.number} d'un montant de ${amount} reste impayée.\n\nLe retard de paiement est actuellement de ${daysOverdue.value} jours.\n\nNous vous prions de régulariser cette situation dans les plus brefs délais.\n\nCordialement,\n${companyName}`;
    } else {
        reminderForm.subject = `Mise en demeure : Facture ${props.invoice.number}`;
        reminderForm.message = `Bonjour ${clientName},\n\nMalgré nos relances précédentes, la facture ${props.invoice.number} d'un montant de ${amount} demeure impayée depuis ${daysOverdue.value} jours.\n\nSans règlement de votre part sous 8 jours, nous nous verrons contraints d'engager les procédures de recouvrement appropriées.\n\nCordialement,\n${companyName}`;
    }

    showReminderModal.value = true;
};

const sendReminder = () => {
    reminderForm.post(route('invoices.send-reminder', props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => {
            showReminderModal.value = false;
            reminderForm.reset();
        },
    });
};

const loadEmailHistory = async () => {
    loadingHistory.value = true;
    try {
        const response = await axios.get(route('invoices.emails', props.invoice.id));
        emailHistory.value = response.data.emails;
    } catch (error) {
        console.error('Error loading email history:', error);
    } finally {
        loadingHistory.value = false;
    }
};

const openEmailHistory = () => {
    showEmailHistory.value = true;
    loadEmailHistory();
};

const toggleReminders = () => {
    router.post(route('invoices.toggle-reminders', props.invoice.id), {}, {
        preserveScroll: true,
    });
};

const creditNoteReasons = computed(() => ({
    billing_error: t('billing_error'),
    return: t('return_merchandise'),
    commercial_discount: t('commercial_discount'),
    cancellation: t('invoice_cancellation'),
    other: t('other'),
}));

// Compute if form can be submitted
const canSubmitCreditNote = computed(() => {
    if (creditNoteType.value === 'partial') {
        return selectedItemIds.value.length > 0;
    }
    return true;
});

// Calculate partial credit note total
const partialTotal = computed(() => {
    if (!props.invoice.items) return 0;
    return props.invoice.items
        .filter(item => selectedItemIds.value.includes(item.id))
        .reduce((sum, item) => sum + parseFloat(item.total_ttc || 0), 0);
});

const getStatusBadgeClass = (status) => {
    const classes = {
        draft: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
        finalized: 'bg-sky-100 text-sky-700 dark:bg-sky-900 dark:text-sky-300',
        sent: 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300',
        paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300',
        cancelled: 'bg-pink-100 text-pink-700 dark:bg-pink-900 dark:text-pink-300',
    };
    return classes[status] || classes.draft;
};

const getStatusLabel = (status) => {
    const labels = {
        draft: t('draft'),
        finalized: t('finalized'),
        sent: t('sent'),
        paid: t('paid'),
        cancelled: t('cancelled'),
    };
    return labels[status] || status;
};

const formatCurrency = (amount, currency = 'EUR') => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: currency,
    }).format(amount);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR');
};

const markAsSent = () => {
    if (processing.value) return;
    processing.value = true;
    router.post(route('invoices.mark-sent', props.invoice.id), {}, {
        preserveScroll: true,
        onFinish: () => processing.value = false,
    });
};

const markAsPaid = () => {
    if (processing.value) return;
    processing.value = true;
    router.post(route('invoices.mark-paid', props.invoice.id), {}, {
        preserveScroll: true,
        onFinish: () => processing.value = false,
    });
};

const openCreditNoteModal = () => {
    creditNoteType.value = 'full';
    selectedItemIds.value = [];
    creditNoteForm.reason = 'cancellation';
    creditNoteForm.item_ids = null;
    showCreditNoteModal.value = true;
};

const closeCreditNoteModal = () => {
    showCreditNoteModal.value = false;
};

const toggleItemSelection = (itemId) => {
    const index = selectedItemIds.value.indexOf(itemId);
    if (index === -1) {
        selectedItemIds.value.push(itemId);
    } else {
        selectedItemIds.value.splice(index, 1);
    }
};

const submitCreditNote = () => {
    if (!canSubmitCreditNote.value) return;

    // Set item_ids based on type
    if (creditNoteType.value === 'partial') {
        creditNoteForm.item_ids = selectedItemIds.value;
    } else {
        creditNoteForm.item_ids = null;
    }

    creditNoteForm.post(route('invoices.credit-note', props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => {
            showCreditNoteModal.value = false;
        },
    });
};
</script>

<template>
    <Head :title="`${t('invoice')} ${invoice.number}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('invoices.index')"
                        class="text-slate-400 hover:text-slate-500 dark:text-slate-500 dark:hover:text-slate-400"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                        </svg>
                    </Link>
                    <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                        <span v-if="invoice.type === 'credit_note'" class="text-pink-600 dark:text-pink-400">{{ t('credit_note') }} </span>
                        {{ invoice.number }}
                    </h1>
                    <span
                        :class="getStatusBadgeClass(invoice.status)"
                        class="inline-flex items-center rounded-xl px-3 py-1 text-xs font-medium"
                    >
                        {{ getStatusLabel(invoice.status) }}
                    </span>
                </div>

                <div class="flex items-center space-x-3">
                    <!-- Preview Button -->
                    <button
                        type="button"
                        @click="openPreview"
                        class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600"
                    >
                        <svg class="h-4 w-4 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" />
                            <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 010-1.186A10.004 10.004 0 0110 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0110 17c-4.257 0-7.893-2.66-9.336-6.41zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                        </svg>
                        {{ t('preview') }}
                    </button>

                    <!-- Mark as Sent -->
                    <button
                        v-if="invoice.status === 'finalized'"
                        @click="markAsSent"
                        :disabled="processing"
                        class="inline-flex items-center rounded-xl bg-amber-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-600 disabled:opacity-50"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M3 4a2 2 0 00-2 2v1.161l8.441 4.221a1.25 1.25 0 001.118 0L19 7.162V6a2 2 0 00-2-2H3z" />
                            <path d="M19 8.839l-7.77 3.885a2.75 2.75 0 01-2.46 0L1 8.839V14a2 2 0 002 2h14a2 2 0 002-2V8.839z" />
                        </svg>
                        {{ t('mark_as_sent') }}
                    </button>

                    <!-- Mark as Paid -->
                    <button
                        v-if="invoice.status === 'sent'"
                        @click="markAsPaid"
                        :disabled="processing"
                        class="inline-flex items-center rounded-xl bg-emerald-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-600 disabled:opacity-50"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                        {{ t('mark_as_paid') }}
                    </button>

                    <!-- Send Email Button -->
                    <button
                        v-if="canSendEmail"
                        @click="openEmailModal"
                        :disabled="processing"
                        class="inline-flex items-center rounded-xl bg-primary-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600 disabled:opacity-50"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M3 4a2 2 0 00-2 2v1.161l8.441 4.221a1.25 1.25 0 001.118 0L19 7.162V6a2 2 0 00-2-2H3z" />
                            <path d="M19 8.839l-7.77 3.885a2.75 2.75 0 01-2.46 0L1 8.839V14a2 2 0 002 2h14a2 2 0 002-2V8.839z" />
                        </svg>
                        {{ t('send_by_email') }}
                    </button>

                    <!-- Reminder Dropdown -->
                    <div v-if="canSendReminder && isOverdue" class="relative inline-block text-left">
                        <button
                            type="button"
                            @click="openReminderModal(1)"
                            class="inline-flex items-center rounded-xl bg-orange-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-600"
                        >
                            <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 2a6 6 0 00-6 6c0 1.887-.454 3.665-1.257 5.234a.75.75 0 00.515 1.076 32.91 32.91 0 003.256.508 3.5 3.5 0 006.972 0 32.903 32.903 0 003.256-.508.75.75 0 00.515-1.076A11.448 11.448 0 0116 8a6 6 0 00-6-6zM8.05 14.943a33.54 33.54 0 003.9 0 2 2 0 01-3.9 0z" clip-rule="evenodd" />
                            </svg>
                            {{ t('send_reminder') }}
                        </button>
                    </div>

                    <!-- Email History Button -->
                    <button
                        v-if="canSendEmail"
                        @click="openEmailHistory"
                        type="button"
                        class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600"
                        :title="t('email_history')"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <!-- Peppol Export Button -->
                    <a
                        v-if="canExportPeppol"
                        :href="route('invoices.peppol', invoice.id)"
                        class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600"
                        title="Export Peppol BIS 3.0"
                    >
                        <svg class="h-4 w-4 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 003 3.5v13A1.5 1.5 0 004.5 18h11a1.5 1.5 0 001.5-1.5V7.621a1.5 1.5 0 00-.44-1.06l-4.12-4.122A1.5 1.5 0 0011.378 2H4.5zm2.25 8.5a.75.75 0 000 1.5h6.5a.75.75 0 000-1.5h-6.5zm0 3a.75.75 0 000 1.5h6.5a.75.75 0 000-1.5h-6.5z" clip-rule="evenodd" />
                        </svg>
                        Peppol XML
                    </a>

                    <!-- Create Credit Note -->
                    <button
                        v-if="invoice.type === 'invoice' && ['finalized', 'sent', 'paid'].includes(invoice.status) && !invoice.credit_note"
                        @click="openCreditNoteModal"
                        :disabled="processing"
                        class="inline-flex items-center rounded-xl border border-pink-300 bg-white px-3 py-2 text-sm font-medium text-pink-700 shadow-sm hover:bg-pink-50 dark:border-pink-600 dark:bg-slate-700 dark:text-pink-400 dark:hover:bg-slate-600"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 2a2 2 0 00-2 2v11a3 3 0 106 0V4a2 2 0 00-2-2H4zm1 14a1 1 0 100-2 1 1 0 000 2zm5-1.757l4.9-4.9a2.121 2.121 0 013 3l-4.9 4.9a2.121 2.121 0 01-1.5.621h-1a.5.5 0 01-.5-.5v-1a2.121 2.121 0 01.621-1.5z" clip-rule="evenodd" />
                        </svg>
                        {{ t('credit_note') }}
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Invoice Header Info -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Seller Info -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('issuer') }}</h2>
                    </div>
                    <div class="px-6 py-4">
                        <div v-if="invoice.seller_snapshot" class="text-sm text-slate-700 dark:text-slate-300 space-y-1">
                            <p class="font-semibold">{{ invoice.seller_snapshot.company_name }}</p>
                            <p v-if="invoice.seller_snapshot.legal_name">{{ invoice.seller_snapshot.legal_name }}</p>
                            <p>{{ invoice.seller_snapshot.address_line1 }}</p>
                            <p v-if="invoice.seller_snapshot.address_line2">{{ invoice.seller_snapshot.address_line2 }}</p>
                            <p>{{ invoice.seller_snapshot.postal_code }} {{ invoice.seller_snapshot.city }}</p>
                            <p>{{ invoice.seller_snapshot.country }}</p>
                            <p class="pt-2">
                                <span class="text-slate-500">{{ t('matricule') }}:</span> {{ invoice.seller_snapshot.matricule }}
                            </p>
                            <p v-if="invoice.seller_snapshot.vat_number">
                                <span class="text-slate-500">{{ t('vat_number_short') }}:</span> {{ invoice.seller_snapshot.vat_number }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Buyer Info -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('client') }}</h2>
                    </div>
                    <div class="px-6 py-4">
                        <div v-if="invoice.buyer_snapshot" class="text-sm text-slate-700 dark:text-slate-300 space-y-1">
                            <p class="font-semibold">{{ invoice.buyer_snapshot.name }}</p>
                            <p v-if="invoice.buyer_snapshot.company_name">{{ invoice.buyer_snapshot.company_name }}</p>
                            <p>{{ invoice.buyer_snapshot.address_line1 }}</p>
                            <p v-if="invoice.buyer_snapshot.address_line2">{{ invoice.buyer_snapshot.address_line2 }}</p>
                            <p>{{ invoice.buyer_snapshot.postal_code }} {{ invoice.buyer_snapshot.city }}</p>
                            <p>{{ invoice.buyer_snapshot.country }}</p>
                            <p v-if="invoice.buyer_snapshot.vat_number" class="pt-2">
                                <span class="text-slate-500">{{ t('vat_number_short') }}:</span> {{ invoice.buyer_snapshot.vat_number }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice Details -->
            <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('details') }}</h2>
                </div>
                <div class="px-6 py-4">
                    <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div>
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('issue_date') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ formatDate(invoice.issued_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('due_date') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ formatDate(invoice.due_at) }}</dd>
                        </div>
                        <div v-if="invoice.sent_at">
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('sent_on') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ formatDate(invoice.sent_at) }}</dd>
                        </div>
                        <div v-if="invoice.paid_at">
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('paid_on') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-white">{{ formatDate(invoice.paid_at) }}</dd>
                        </div>
                        <div v-if="invoice.credit_note_for">
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('credit_note_for') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-white">
                                <Link
                                    v-if="invoice.original_invoice"
                                    :href="route('invoices.show', invoice.credit_note_for)"
                                    class="text-primary-600 hover:text-primary-500 dark:text-primary-400"
                                >
                                    {{ t('invoice') }} {{ invoice.original_invoice.number }}
                                </Link>
                                <Link
                                    v-else
                                    :href="route('invoices.show', invoice.credit_note_for)"
                                    class="text-primary-600 hover:text-primary-500 dark:text-primary-400"
                                >
                                    {{ t('see_original_invoice') }}
                                </Link>
                            </dd>
                        </div>
                        <div v-if="invoice.credit_note_reason">
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('reason') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-white">
                                {{ creditNoteReasons[invoice.credit_note_reason] || invoice.credit_note_reason }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Invoice Items -->
            <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('invoice_lines') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-700">
                            <tr>
                                <th class="py-3.5 pl-6 pr-3 text-left text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ t('description') }}
                                </th>
                                <th class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ t('qty') }}
                                </th>
                                <th class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ t('price_ht') }}
                                </th>
                                <th class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ t('vat') }}
                                </th>
                                <th class="py-3.5 pl-3 pr-6 text-right text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ t('total_ht') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-800">
                            <tr v-for="item in invoice.items" :key="item.id">
                                <td class="py-4 pl-6 pr-3 text-sm text-slate-900 dark:text-white">
                                    {{ item.description }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-slate-500 dark:text-slate-400">
                                    {{ item.quantity }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-slate-500 dark:text-slate-400">
                                    {{ formatCurrency(item.unit_price, invoice.currency) }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-right text-sm text-slate-500 dark:text-slate-400">
                                    {{ item.vat_rate }}%
                                </td>
                                <td class="whitespace-nowrap py-4 pl-3 pr-6 text-right text-sm font-medium text-slate-900 dark:text-white">
                                    {{ formatCurrency(item.total_ht, invoice.currency) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-slate-50 dark:bg-slate-700">
                            <tr>
                                <td colspan="4" class="py-3 pl-6 pr-3 text-right text-sm font-medium text-slate-500 dark:text-slate-400">
                                    {{ t('total_ht') }}
                                </td>
                                <td class="whitespace-nowrap py-3 pl-3 pr-6 text-right text-sm font-medium text-slate-900 dark:text-white">
                                    {{ formatCurrency(invoice.total_ht, invoice.currency) }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="4" class="py-3 pl-6 pr-3 text-right text-sm font-medium text-slate-500 dark:text-slate-400">
                                    {{ t('total_vat') }}
                                </td>
                                <td class="whitespace-nowrap py-3 pl-3 pr-6 text-right text-sm font-medium text-slate-900 dark:text-white">
                                    {{ formatCurrency(invoice.total_vat, invoice.currency) }}
                                </td>
                            </tr>
                            <tr class="border-t-2 border-slate-300 dark:border-slate-600">
                                <td colspan="4" class="py-3 pl-6 pr-3 text-right text-sm font-bold text-slate-900 dark:text-white">
                                    {{ t('total_ttc') }}
                                </td>
                                <td class="whitespace-nowrap py-3 pl-3 pr-6 text-right text-sm font-bold"
                                    :class="invoice.type === 'credit_note' ? 'text-pink-600 dark:text-pink-400' : 'text-slate-900 dark:text-white'"
                                >
                                    {{ formatCurrency(invoice.total_ttc, invoice.currency) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Notes -->
            <div v-if="invoice.notes" class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('notes') }}</h2>
                </div>
                <div class="px-6 py-4">
                    <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap">{{ invoice.notes }}</p>
                </div>
            </div>

            <!-- Credit Notes linked to this invoice -->
            <div v-if="invoice.credit_notes && invoice.credit_notes.length > 0" class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('related_credit_notes') }}</h2>
                </div>
                <div class="px-6 py-4">
                    <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                        <li v-for="creditNote in invoice.credit_notes" :key="creditNote.id" class="py-3 flex justify-between items-center">
                            <Link
                                :href="route('invoices.show', creditNote.id)"
                                class="text-primary-600 hover:text-primary-500 dark:text-primary-400"
                            >
                                {{ creditNote.number }}
                            </Link>
                            <span class="text-sm text-pink-600 dark:text-pink-400">
                                {{ formatCurrency(creditNote.total_ttc, creditNote.currency) }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Credit Note Modal -->
        <div v-if="showCreditNoteModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="closeCreditNoteModal"></div>

                <div class="inline-block transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all dark:bg-slate-800 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                    <div class="px-4 pt-5 pb-4 sm:p-6">
                        <h3 class="text-lg font-medium leading-6 text-slate-900 dark:text-white mb-4">
                            {{ t('create_credit_note_for').replace(':number', invoice.number) }}
                        </h3>

                        <!-- Credit Note Type -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                {{ t('credit_note_type') }}
                            </label>
                            <div class="flex space-x-4">
                                <label class="flex items-center">
                                    <input
                                        type="radio"
                                        v-model="creditNoteType"
                                        value="full"
                                        class="h-4 w-4 text-primary-500 border-slate-300 focus:ring-primary-500"
                                    />
                                    <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">{{ t('full_credit_note') }}</span>
                                </label>
                                <label class="flex items-center">
                                    <input
                                        type="radio"
                                        v-model="creditNoteType"
                                        value="partial"
                                        class="h-4 w-4 text-primary-500 border-slate-300 focus:ring-primary-500"
                                    />
                                    <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">{{ t('partial_credit_note') }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div class="mb-4">
                            <label for="credit_note_reason" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                {{ t('credit_note_reason') }}
                            </label>
                            <select
                                id="credit_note_reason"
                                v-model="creditNoteForm.reason"
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                            >
                                <option v-for="(label, value) in creditNoteReasons" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                        </div>

                        <!-- Item Selection for Partial -->
                        <div v-if="creditNoteType === 'partial'" class="mb-4">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                {{ t('lines_to_cancel') }}
                            </label>
                            <div class="border border-slate-200 rounded-xl divide-y divide-slate-200 dark:border-slate-600 dark:divide-slate-600 max-h-48 overflow-y-auto">
                                <label
                                    v-for="item in invoice.items"
                                    :key="item.id"
                                    class="flex items-center px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-700 cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="selectedItemIds.includes(item.id)"
                                        @change="toggleItemSelection(item.id)"
                                        class="h-4 w-4 text-primary-500 border-slate-300 rounded focus:ring-primary-500"
                                    />
                                    <span class="ml-3 flex-1 text-sm text-slate-700 dark:text-slate-300">
                                        {{ item.title || item.description }}
                                    </span>
                                    <span class="text-sm text-slate-500 dark:text-slate-400">
                                        {{ formatCurrency(item.total_ttc, invoice.currency) }}
                                    </span>
                                </label>
                            </div>
                            <p v-if="selectedItemIds.length === 0" class="mt-2 text-sm text-pink-600 dark:text-pink-400">
                                {{ t('select_at_least_one_line') }}
                            </p>
                        </div>

                        <!-- Summary -->
                        <div class="bg-slate-50 dark:bg-slate-700 rounded-xl p-3 mb-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                    {{ t('credit_note_amount') }} :
                                </span>
                                <span class="text-lg font-bold text-pink-600 dark:text-pink-400">
                                    {{ creditNoteType === 'partial'
                                        ? formatCurrency(-partialTotal, invoice.currency)
                                        : formatCurrency(-invoice.total_ttc, invoice.currency)
                                    }}
                                </span>
                            </div>
                        </div>

                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            {{ t('credit_note_draft_info') }}
                        </p>
                    </div>

                    <div class="bg-slate-50 px-4 py-3 dark:bg-slate-700 sm:flex sm:flex-row-reverse sm:px-6">
                        <button
                            type="button"
                            @click="submitCreditNote"
                            :disabled="!canSubmitCreditNote || creditNoteForm.processing"
                            class="inline-flex w-full justify-center rounded-xl bg-pink-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-pink-500 disabled:opacity-50 sm:ml-3 sm:w-auto"
                        >
                            {{ creditNoteForm.processing ? t('creating') : t('create_credit_note') }}
                        </button>
                        <button
                            type="button"
                            @click="closeCreditNoteModal"
                            class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:bg-slate-600 dark:text-white dark:ring-slate-500 sm:mt-0 sm:w-auto"
                        >
                            {{ t('cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <div v-if="showPreviewModal" class="fixed inset-0 z-50 overflow-hidden">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="showPreviewModal = false"></div>

                <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-5xl max-h-[90vh] flex flex-col">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h3 class="text-lg font-medium text-slate-900 dark:text-white">
                            <span v-if="invoice.type === 'credit_note'" class="text-pink-600 dark:text-pink-400">{{ t('credit_note') }} </span>
                            {{ invoice.number }}
                        </h3>
                        <div class="flex items-center space-x-2">
                            <!-- Language selector -->
                            <div class="flex items-center border border-slate-200 dark:border-slate-600 rounded-xl overflow-hidden">
                                <button
                                    v-for="lang in pdfLanguages"
                                    :key="lang.value"
                                    type="button"
                                    @click="changePdfLanguage(lang.value)"
                                    :title="lang.label"
                                    class="px-2 py-1.5 text-base transition-colors"
                                    :class="pdfLocale === lang.value
                                        ? 'bg-primary-100 dark:bg-primary-900'
                                        : 'bg-white dark:bg-slate-700 hover:bg-slate-50 dark:hover:bg-slate-600'"
                                >
                                    {{ lang.flag }}
                                </button>
                            </div>
                            <a
                                :href="pdfUrl"
                                target="_blank"
                                class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300"
                            >
                                <svg class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z" />
                                    <path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" />
                                </svg>
                                PDF
                            </a>
                            <button
                                type="button"
                                @click="showPreviewModal = false"
                                class="text-slate-400 hover:text-slate-500 dark:hover:text-slate-300"
                            >
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal body -->
                    <div class="flex-1 overflow-auto p-6 bg-slate-100 dark:bg-slate-900">
                        <div v-if="loadingPreview" class="flex items-center justify-center h-96">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500"></div>
                        </div>
                        <div
                            v-else
                            class="bg-white shadow-lg mx-auto"
                            style="width: 210mm; min-height: 297mm; transform: scale(1); transform-origin: top center;"
                            v-html="previewHtml"
                        ></div>
                    </div>

                    <!-- Modal footer -->
                    <div class="flex items-center justify-end px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50">
                        <button
                            type="button"
                            @click="showPreviewModal = false"
                            class="inline-flex items-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:bg-slate-600 dark:text-white dark:ring-slate-500"
                        >
                            {{ t('close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Email Modal -->
        <div v-if="showEmailModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="showEmailModal = false"></div>

                <div class="inline-block transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all dark:bg-slate-800 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                    <form @submit.prevent="sendEmail">
                        <div class="px-4 pt-5 pb-4 sm:p-6">
                            <h3 class="text-lg font-medium leading-6 text-slate-900 dark:text-white mb-4">
                                {{ t('send_invoice_by_email') }}
                            </h3>

                            <!-- Recipient Email -->
                            <div class="mb-4">
                                <label for="recipient_email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    {{ t('recipient_email') }} *
                                </label>
                                <input
                                    type="email"
                                    id="recipient_email"
                                    v-model="emailForm.recipient_email"
                                    required
                                    class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                />
                                <p v-if="emailForm.errors.recipient_email" class="mt-1 text-sm text-pink-600">
                                    {{ emailForm.errors.recipient_email }}
                                </p>
                            </div>

                            <!-- Subject -->
                            <div class="mb-4">
                                <label for="subject" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    {{ t('subject') }} *
                                </label>
                                <input
                                    type="text"
                                    id="subject"
                                    v-model="emailForm.subject"
                                    required
                                    class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                />
                                <p v-if="emailForm.errors.subject" class="mt-1 text-sm text-pink-600">
                                    {{ emailForm.errors.subject }}
                                </p>
                            </div>

                            <!-- Message -->
                            <div class="mb-4">
                                <label for="message" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    {{ t('custom_message') }}
                                </label>
                                <textarea
                                    id="message"
                                    v-model="emailForm.message"
                                    rows="4"
                                    class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                    :placeholder="t('leave_empty_for_default')"
                                ></textarea>
                                <p v-if="emailForm.errors.message" class="mt-1 text-sm text-pink-600">
                                    {{ emailForm.errors.message }}
                                </p>
                            </div>

                            <!-- Send copy to self -->
                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="emailForm.send_copy_to_self"
                                        class="h-4 w-4 text-primary-500 border-slate-300 rounded focus:ring-primary-500"
                                    />
                                    <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">
                                        {{ t('send_copy_to_self') }}
                                    </span>
                                </label>
                            </div>

                            <!-- Info -->
                            <div class="bg-sky-50 dark:bg-sky-900/20 rounded-xl p-3">
                                <p class="text-sm text-sky-700 dark:text-sky-300">
                                    {{ t('invoice_pdf_attached') }}
                                </p>
                            </div>
                        </div>

                        <div class="bg-slate-50 px-4 py-3 dark:bg-slate-700 sm:flex sm:flex-row-reverse sm:px-6">
                            <button
                                type="submit"
                                :disabled="emailForm.processing"
                                class="inline-flex w-full justify-center rounded-xl bg-primary-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600 disabled:opacity-50 sm:ml-3 sm:w-auto"
                            >
                                <svg v-if="emailForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ emailForm.processing ? t('sending') : t('send') }}
                            </button>
                            <button
                                type="button"
                                @click="showEmailModal = false"
                                class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:bg-slate-600 dark:text-white dark:ring-slate-500 sm:mt-0 sm:w-auto"
                            >
                                {{ t('cancel') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reminder Modal -->
        <div v-if="showReminderModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="showReminderModal = false"></div>

                <div class="inline-block transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all dark:bg-slate-800 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                    <form @submit.prevent="sendReminder">
                        <div class="px-4 pt-5 pb-4 sm:p-6">
                            <h3 class="text-lg font-medium leading-6 text-slate-900 dark:text-white mb-4">
                                {{ t('send_payment_reminder') }}
                            </h3>

                            <!-- Reminder Level -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    {{ t('reminder_level') }}
                                </label>
                                <div class="flex space-x-2">
                                    <button
                                        type="button"
                                        @click="openReminderModal(1)"
                                        :class="reminderLevel === 1 ? 'bg-orange-100 border-orange-500 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300' : 'bg-white border-slate-200 text-slate-700 dark:bg-slate-700 dark:border-slate-600 dark:text-slate-300'"
                                        class="flex-1 py-2 px-3 rounded-xl border text-sm font-medium"
                                    >
                                        {{ t('level') }} 1 - {{ t('reminder') }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="openReminderModal(2)"
                                        :class="reminderLevel === 2 ? 'bg-orange-100 border-orange-500 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300' : 'bg-white border-slate-200 text-slate-700 dark:bg-slate-700 dark:border-slate-600 dark:text-slate-300'"
                                        class="flex-1 py-2 px-3 rounded-xl border text-sm font-medium"
                                    >
                                        {{ t('level') }} 2 - {{ t('follow_up') }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="openReminderModal(3)"
                                        :class="reminderLevel === 3 ? 'bg-pink-100 border-pink-500 text-pink-700 dark:bg-pink-900/30 dark:text-pink-300' : 'bg-white border-slate-200 text-slate-700 dark:bg-slate-700 dark:border-slate-600 dark:text-slate-300'"
                                        class="flex-1 py-2 px-3 rounded-xl border text-sm font-medium"
                                    >
                                        {{ t('level') }} 3 - {{ t('formal_notice') }}
                                    </button>
                                </div>
                            </div>

                            <!-- Overdue Info -->
                            <div class="mb-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl p-3">
                                <div class="flex items-center">
                                    <svg class="h-5 w-5 text-orange-600 dark:text-orange-400 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-sm font-medium text-orange-700 dark:text-orange-300">
                                        {{ t('payment_overdue_by').replace(':days', daysOverdue) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Recipient Email -->
                            <div class="mb-4">
                                <label for="reminder_email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    {{ t('recipient_email') }} *
                                </label>
                                <input
                                    type="email"
                                    id="reminder_email"
                                    v-model="reminderForm.recipient_email"
                                    required
                                    class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                />
                            </div>

                            <!-- Subject -->
                            <div class="mb-4">
                                <label for="reminder_subject" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    {{ t('subject') }} *
                                </label>
                                <input
                                    type="text"
                                    id="reminder_subject"
                                    v-model="reminderForm.subject"
                                    required
                                    class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                />
                            </div>

                            <!-- Message -->
                            <div class="mb-4">
                                <label for="reminder_message" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    {{ t('message') }} *
                                </label>
                                <textarea
                                    id="reminder_message"
                                    v-model="reminderForm.message"
                                    rows="6"
                                    required
                                    class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                ></textarea>
                            </div>

                            <!-- Info -->
                            <div class="bg-sky-50 dark:bg-sky-900/20 rounded-xl p-3">
                                <p class="text-sm text-sky-700 dark:text-sky-300">
                                    {{ t('invoice_pdf_attached') }}
                                </p>
                            </div>
                        </div>

                        <div class="bg-slate-50 px-4 py-3 dark:bg-slate-700 sm:flex sm:flex-row-reverse sm:px-6">
                            <button
                                type="submit"
                                :disabled="reminderForm.processing"
                                class="inline-flex w-full justify-center rounded-xl bg-orange-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 disabled:opacity-50 sm:ml-3 sm:w-auto"
                            >
                                <svg v-if="reminderForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ reminderForm.processing ? t('sending') : t('send_reminder') }}
                            </button>
                            <button
                                type="button"
                                @click="showReminderModal = false"
                                class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:bg-slate-600 dark:text-white dark:ring-slate-500 sm:mt-0 sm:w-auto"
                            >
                                {{ t('cancel') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Email History Modal -->
        <div v-if="showEmailHistory" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="showEmailHistory = false"></div>

                <div class="inline-block transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all dark:bg-slate-800 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                    <div class="px-4 pt-5 pb-4 sm:p-6">
                        <h3 class="text-lg font-medium leading-6 text-slate-900 dark:text-white mb-4">
                            {{ t('email_history') }}
                        </h3>

                        <div v-if="loadingHistory" class="flex items-center justify-center py-8">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
                        </div>

                        <div v-else-if="emailHistory.length === 0" class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                                {{ t('no_emails_sent') }}
                            </p>
                        </div>

                        <div v-else class="space-y-3 max-h-96 overflow-y-auto">
                            <div
                                v-for="email in emailHistory"
                                :key="email.id"
                                class="border rounded-xl p-3 dark:border-slate-600"
                                :class="email.status === 'failed' ? 'border-pink-300 bg-pink-50 dark:border-pink-600 dark:bg-pink-900/20' : 'border-slate-200 dark:border-slate-600'"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span
                                        class="inline-flex items-center rounded-xl px-2.5 py-0.5 text-xs font-medium"
                                        :class="email.is_reminder ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300' : 'bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-300'"
                                    >
                                        {{ email.type_label }}
                                    </span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ email.sent_at }}
                                    </span>
                                </div>
                                <p class="text-sm font-medium text-slate-900 dark:text-white truncate">
                                    {{ email.subject }}
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    {{ t('to') }}: {{ email.recipient_email }}
                                </p>
                                <div v-if="email.status === 'failed'" class="mt-2 flex items-center text-xs text-pink-600 dark:text-pink-400">
                                    <svg class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                    </svg>
                                    {{ t('sending_failed') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-4 py-3 dark:bg-slate-700 sm:flex sm:flex-row-reverse sm:px-6">
                        <button
                            type="button"
                            @click="showEmailHistory = false"
                            class="inline-flex w-full justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:bg-slate-600 dark:text-white dark:ring-slate-500 sm:w-auto"
                        >
                            {{ t('close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
