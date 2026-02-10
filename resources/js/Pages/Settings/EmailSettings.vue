<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    settings: Object,
});

const form = useForm({
    default_message: props.settings.default_message || '',
    signature: props.settings.signature || '',
    send_copy_to_self: props.settings.send_copy_to_self || false,
    reminders_enabled: props.settings.reminders_enabled || false,
    reminder_levels: props.settings.reminder_levels || {},
});

const submit = () => {
    form.put(route('settings.email.update'), {
        preserveScroll: true,
    });
};

const reminderLevelNames = {
    1: 'Rappel',
    2: 'Relance',
    3: 'Mise en demeure',
};
</script>

<template>
    <Head :title="t('email_settings')" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('settings') }}
            </h1>
        </template>

        <!-- Settings Navigation -->
        <div class="mb-6 border-b border-slate-200 dark:border-slate-700">
            <nav class="flex space-x-8" aria-label="Settings tabs">
                <Link
                    :href="route('settings.business.edit')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300"
                >
                    {{ t('business_settings') }}
                </Link>
                <Link
                    :href="route('settings.email')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-primary-500 text-primary-600 dark:text-primary-400"
                >
                    {{ t('email_settings') }}
                </Link>
                <Link
                    :href="route('settings.email.provider')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300"
                >
                    Fournisseur Email
                </Link>
                <Link
                    :href="route('settings.accountant')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300"
                >
                    Accès Comptable
                </Link>
            </nav>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Default Email Message -->
            <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('default_email_message') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Ce message sera utilisé par défaut lors de l'envoi de factures par email.
                    </p>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label for="default_message" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                            {{ t('message') }}
                        </label>
                        <textarea
                            id="default_message"
                            v-model="form.default_message"
                            rows="4"
                            class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                            placeholder="Laissez vide pour utiliser le message par défaut du système..."
                        ></textarea>
                    </div>

                    <div>
                        <label for="signature" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                            {{ t('email_signature') }}
                        </label>
                        <textarea
                            id="signature"
                            v-model="form.signature"
                            rows="3"
                            class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                            placeholder="Signature qui sera ajoutée à vos emails..."
                        ></textarea>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="form.send_copy_to_self"
                                class="h-4 w-4 text-primary-600 border-slate-300 rounded focus:ring-primary-500"
                            />
                            <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">
                                {{ t('send_copy_to_self') }} (par défaut)
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Reminder Settings -->
            <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-medium text-slate-900 dark:text-white">Relances de paiement</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Configurez les modèles de relance pour les factures impayées.
                            </p>
                        </div>
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="form.reminders_enabled"
                                class="h-4 w-4 text-primary-600 border-slate-300 rounded focus:ring-primary-500"
                            />
                            <span class="ml-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                                {{ t('reminders_enabled') }}
                            </span>
                        </label>
                    </div>
                </div>
                <div class="px-6 py-4">
                    <div class="space-y-6">
                        <div
                            v-for="(level, key) in form.reminder_levels"
                            :key="key"
                            class="border rounded-xl p-4 dark:border-slate-600"
                            :class="level.enabled ? 'border-primary-200 bg-primary-50/50 dark:border-primary-700 dark:bg-primary-900/20' : 'border-slate-200'"
                        >
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-3">
                                    <span class="flex items-center justify-center w-8 h-8 rounded-xl text-sm font-bold"
                                        :class="key == 3 ? 'bg-pink-100 text-pink-700 dark:bg-pink-900 dark:text-pink-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300'"
                                    >
                                        {{ key }}
                                    </span>
                                    <h3 class="font-medium text-slate-900 dark:text-white">
                                        {{ reminderLevelNames[key] }}
                                    </h3>
                                </div>
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="level.enabled"
                                        class="h-4 w-4 text-primary-600 border-slate-300 rounded focus:ring-primary-500"
                                    />
                                    <span class="ml-2 text-sm text-slate-700 dark:text-slate-300">Activé</span>
                                </label>
                            </div>

                            <div v-if="level.enabled" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        {{ t('reminder_days_after_due') }}
                                    </label>
                                    <div class="mt-1 flex items-center space-x-2">
                                        <input
                                            type="number"
                                            v-model.number="level.days_after_due"
                                            min="1"
                                            max="365"
                                            class="w-20 rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                        />
                                        <span class="text-sm text-slate-500 dark:text-slate-400">jours</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        {{ t('subject') }}
                                    </label>
                                    <input
                                        type="text"
                                        v-model="level.subject"
                                        class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                    />
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        Variables: {numero}, {client_nom}, {montant}, {date_echeance}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        {{ t('message') }}
                                    </label>
                                    <textarea
                                        v-model="level.body"
                                        rows="4"
                                        class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                                    ></textarea>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        Variables: {numero}, {client_nom}, {montant}, {date_echeance}, {jours_retard}, {entreprise}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 disabled:opacity-50"
                >
                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ form.processing ? t('saving') : t('save') }}
                </button>
            </div>
        </form>
    </AppLayout>
</template>
