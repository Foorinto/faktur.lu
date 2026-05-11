<script setup>
import { computed, ref } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const props = defineProps({
    modelValue: {
        type: Object,
        required: true,
    },
    editability: {
        type: Object,
        required: true,
    },
    finalizedCounts: {
        type: Object,
        required: true,
    },
    currentYear: {
        type: Number,
        required: true,
    },
    placeholders: {
        type: Array,
        required: true,
    },
    defaultTemplate: {
        type: String,
        required: true,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['update:modelValue']);

const { t } = useTranslations();
const showHelp = ref(false);

const v = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

function update(field, value) {
    emit('update:modelValue', { ...props.modelValue, [field]: value });
}

const padding = computed(() => Math.max(1, parseInt(props.modelValue.number_padding) || 3));

function pad(num) {
    return String(num ?? 1).padStart(padding.value, '0');
}

function slugify(name) {
    if (!name) return '';
    return name
        .toString()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 20)
        .replace(/-+$/, '');
}

function renderPreview(prefix, startingNumber) {
    const template = props.modelValue.number_format || props.defaultTemplate;
    const date = new Date();
    const year = String(date.getFullYear());
    const yy = year.slice(-2);
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const sequence = pad(startingNumber || 1);
    const clientSlug = slugify('Client Exemple');

    let result = template
        .replaceAll('{prefix}', prefix || '')
        .replaceAll('{year}', year)
        .replaceAll('{yy}', yy)
        .replaceAll('{month}', month)
        .replaceAll('{day}', day)
        .replaceAll('{number}', sequence)
        .replaceAll('{client_name}', clientSlug);

    // Clean up empty segments (matches PHP DocumentNumberFormatter::cleanupEmptySegments)
    result = result.replace(/([\-_\/.])\1+/g, '$1').replace(/^[\-_\/.\s]+|[\-_\/.\s]+$/g, '');
    return result;
}

const previewInvoice = computed(() => renderPreview(props.modelValue.invoice_prefix, props.modelValue.invoice_starting_number));
const previewCreditNote = computed(() => renderPreview(props.modelValue.credit_note_prefix, props.modelValue.credit_note_starting_number));
const previewQuote = computed(() => renderPreview(props.modelValue.quote_prefix, props.modelValue.quote_starting_number));

const formatLocked = computed(() =>
    !props.editability.invoice || !props.editability.credit_note || !props.editability.quote
);

const placeholderDescriptions = {
    '{prefix}': t('numbering_settings.placeholder_prefix'),
    '{year}': t('numbering_settings.placeholder_year'),
    '{yy}': t('numbering_settings.placeholder_yy'),
    '{month}': t('numbering_settings.placeholder_month'),
    '{day}': t('numbering_settings.placeholder_day'),
    '{number}': t('numbering_settings.placeholder_number'),
    '{client_name}': t('numbering_settings.placeholder_client_name'),
};
</script>

<template>
    <section id="numbering" class="bg-white dark:bg-gray-900 shadow rounded-lg p-6 sm:p-8">
        <div class="flex items-start justify-between mb-1">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                {{ t('numbering_settings.heading') }}
            </h2>
            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                {{ t('numbering_settings.free_badge') }}
            </span>
        </div>
        <p class="text-sm text-slate-600 dark:text-slate-400 mb-6">
            {{ t('numbering_settings.description') }}
        </p>

        <!-- Format template -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-2">
                <label for="number_format" class="block text-sm font-medium text-slate-900 dark:text-slate-100">
                    {{ t('numbering_settings.format_label') }}
                </label>
                <button
                    type="button"
                    @click="showHelp = !showHelp"
                    class="inline-flex items-center text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                >
                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ showHelp ? t('numbering_settings.hide_help') : t('numbering_settings.show_help') }}
                </button>
            </div>
            <input
                id="number_format"
                type="text"
                :value="modelValue.number_format"
                @input="update('number_format', $event.target.value)"
                :disabled="formatLocked"
                :placeholder="defaultTemplate"
                class="block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:dark:bg-gray-800 disabled:cursor-not-allowed font-mono"
            />
            <p v-if="errors['number_format']" class="mt-1 text-sm text-red-600">{{ errors['number_format'] }}</p>

            <!-- Placeholder help panel -->
            <div v-if="showHelp" class="mt-3 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 p-4 text-sm">
                <p class="font-medium text-indigo-900 dark:text-indigo-100 mb-2">{{ t('numbering_settings.help_title') }}</p>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-indigo-900 dark:text-indigo-100">
                    <template v-for="placeholder in placeholders" :key="placeholder">
                        <dt class="font-mono font-semibold">{{ placeholder }}</dt>
                        <dd class="text-indigo-700 dark:text-indigo-200">{{ placeholderDescriptions[placeholder] || '' }}</dd>
                    </template>
                </dl>
                <p class="mt-3 text-xs text-indigo-700 dark:text-indigo-300">
                    {{ t('numbering_settings.help_examples') }}
                </p>
            </div>
        </div>

        <!-- Padding -->
        <div class="mb-6">
            <label for="number_padding" class="block text-sm font-medium text-slate-900 dark:text-slate-100 mb-2">
                {{ t('numbering_settings.padding_label') }}
            </label>
            <input
                id="number_padding"
                type="number"
                min="1"
                max="10"
                :value="modelValue.number_padding ?? 3"
                @input="update('number_padding', parseInt($event.target.value) || 3)"
                :disabled="formatLocked"
                class="block w-32 rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:dark:bg-gray-800 disabled:cursor-not-allowed"
            />
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ t('numbering_settings.padding_help') }}</p>
        </div>

        <!-- Format lock warning -->
        <div v-if="formatLocked" class="mb-6 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
                <div>
                    <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                        {{ t('numbering_settings.locked_format_title', { year: currentYear }) }}
                    </p>
                    <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                        {{ t('numbering_settings.locked_format_message', { year: currentYear, next_year: currentYear + 1 }) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Per-type prefix + starting_number + live preview -->
        <div class="space-y-5">
            <!-- Invoice -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-medium text-slate-900 dark:text-slate-100">{{ t('numbering_settings.invoice_section') }}</h3>
                    <code class="text-sm font-mono px-2 py-1 rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">{{ previewInvoice }}</code>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="invoice_prefix" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ t('numbering_settings.prefix_label') }}
                        </label>
                        <input
                            id="invoice_prefix"
                            type="text"
                            :value="modelValue.invoice_prefix"
                            @input="update('invoice_prefix', $event.target.value)"
                            :disabled="!editability.invoice"
                            maxlength="20"
                            class="block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:dark:bg-gray-800 disabled:cursor-not-allowed font-mono text-sm"
                        />
                        <p v-if="errors['invoice_prefix']" class="mt-1 text-xs text-red-600">{{ errors['invoice_prefix'] }}</p>
                    </div>
                    <div>
                        <label for="invoice_starting_number" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ t('numbering_settings.starting_number_label') }}
                        </label>
                        <input
                            id="invoice_starting_number"
                            type="number"
                            min="1"
                            :value="modelValue.invoice_starting_number"
                            @input="update('invoice_starting_number', $event.target.value ? parseInt($event.target.value) : null)"
                            :disabled="!editability.invoice"
                            :placeholder="t('numbering_settings.starting_number_placeholder')"
                            class="block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:dark:bg-gray-800 disabled:cursor-not-allowed text-sm"
                        />
                        <p v-if="errors['invoice_starting_number']" class="mt-1 text-xs text-red-600">{{ errors['invoice_starting_number'] }}</p>
                    </div>
                </div>
                <p v-if="!editability.invoice" class="mt-2 text-xs text-amber-700 dark:text-amber-300">
                    {{ t('numbering_settings.locked_invoice', { count: finalizedCounts.invoice, year: currentYear }) }}
                </p>
            </div>

            <!-- Credit note -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-medium text-slate-900 dark:text-slate-100">{{ t('numbering_settings.credit_note_section') }}</h3>
                    <code class="text-sm font-mono px-2 py-1 rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">{{ previewCreditNote }}</code>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="credit_note_prefix" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ t('numbering_settings.prefix_label') }}
                        </label>
                        <input
                            id="credit_note_prefix"
                            type="text"
                            :value="modelValue.credit_note_prefix"
                            @input="update('credit_note_prefix', $event.target.value)"
                            :disabled="!editability.credit_note"
                            maxlength="20"
                            class="block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:dark:bg-gray-800 disabled:cursor-not-allowed font-mono text-sm"
                        />
                    </div>
                    <div>
                        <label for="credit_note_starting_number" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ t('numbering_settings.starting_number_label') }}
                        </label>
                        <input
                            id="credit_note_starting_number"
                            type="number"
                            min="1"
                            :value="modelValue.credit_note_starting_number"
                            @input="update('credit_note_starting_number', $event.target.value ? parseInt($event.target.value) : null)"
                            :disabled="!editability.credit_note"
                            :placeholder="t('numbering_settings.starting_number_placeholder')"
                            class="block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:dark:bg-gray-800 disabled:cursor-not-allowed text-sm"
                        />
                    </div>
                </div>
                <p v-if="!editability.credit_note" class="mt-2 text-xs text-amber-700 dark:text-amber-300">
                    {{ t('numbering_settings.locked_credit_note', { count: finalizedCounts.credit_note, year: currentYear }) }}
                </p>
            </div>

            <!-- Quote -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-medium text-slate-900 dark:text-slate-100">{{ t('numbering_settings.quote_section') }}</h3>
                    <code class="text-sm font-mono px-2 py-1 rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">{{ previewQuote }}</code>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="quote_prefix" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ t('numbering_settings.prefix_label') }}
                        </label>
                        <input
                            id="quote_prefix"
                            type="text"
                            :value="modelValue.quote_prefix"
                            @input="update('quote_prefix', $event.target.value)"
                            :disabled="!editability.quote"
                            maxlength="20"
                            class="block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:dark:bg-gray-800 disabled:cursor-not-allowed font-mono text-sm"
                        />
                    </div>
                    <div>
                        <label for="quote_starting_number" class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ t('numbering_settings.starting_number_label') }}
                        </label>
                        <input
                            id="quote_starting_number"
                            type="number"
                            min="1"
                            :value="modelValue.quote_starting_number"
                            @input="update('quote_starting_number', $event.target.value ? parseInt($event.target.value) : null)"
                            :disabled="!editability.quote"
                            :placeholder="t('numbering_settings.starting_number_placeholder')"
                            class="block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:dark:bg-gray-800 disabled:cursor-not-allowed text-sm"
                        />
                    </div>
                </div>
                <p v-if="!editability.quote" class="mt-2 text-xs text-amber-700 dark:text-amber-300">
                    {{ t('numbering_settings.locked_quote', { count: finalizedCounts.quote, year: currentYear }) }}
                </p>
            </div>
        </div>

        <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">
            {{ t('numbering_settings.legal_footnote') }}
        </p>
    </section>
</template>
