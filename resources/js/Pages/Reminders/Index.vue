<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    overdue: { type: Array, default: () => [] },
    upcoming: { type: Array, default: () => [] },
    completed: { type: Array, default: () => [] },
});

const completeReminder = (reminder) => {
    router.patch(route('reminders.complete', reminder.id), {}, {
        preserveScroll: true,
    });
};

const deleteReminder = (reminder) => {
    if (confirm(t('crm.confirm_delete_reminder'))) {
        router.delete(route('reminders.destroy', reminder.id), {
            preserveScroll: true,
        });
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head :title="t('crm.reminders')" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('crm.reminders') }}
            </h1>
        </template>

        <div class="space-y-8">
            <!-- Overdue -->
            <div v-if="overdue.length > 0">
                <h2 class="mb-4 flex items-center text-lg font-medium text-rose-600 dark:text-rose-400">
                    <svg class="mr-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                    {{ t('crm.overdue') }} ({{ overdue.length }})
                </h2>
                <div class="space-y-3">
                    <div v-for="reminder in overdue" :key="reminder.id" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-800 dark:bg-rose-900/20">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="font-medium text-rose-700 dark:text-rose-300">{{ reminder.title }}</p>
                                <Link v-if="reminder.client" :href="route('clients.show', reminder.client.id)" class="text-sm text-rose-600 hover:underline dark:text-rose-400">
                                    {{ reminder.client.name }}
                                </Link>
                                <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ formatDate(reminder.remind_at) }}</p>
                                <p v-if="reminder.description" class="mt-1 text-sm text-rose-600/80 dark:text-rose-400/80">{{ reminder.description }}</p>
                            </div>
                            <div class="flex gap-1">
                                <button @click="completeReminder(reminder)" class="rounded-lg p-2 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20" :title="t('crm.mark_completed')">
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <button @click="deleteReminder(reminder)" class="rounded-lg p-2 text-slate-400 hover:bg-rose-100 hover:text-rose-600 dark:hover:bg-rose-900/30">
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming -->
            <div>
                <h2 class="mb-4 text-lg font-medium text-slate-900 dark:text-white">
                    {{ t('crm.reminders') }} ({{ upcoming.length }})
                </h2>
                <div v-if="upcoming.length > 0" class="space-y-3">
                    <div v-for="reminder in upcoming" :key="reminder.id" class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="font-medium text-slate-900 dark:text-white">{{ reminder.title }}</p>
                                <Link v-if="reminder.client" :href="route('clients.show', reminder.client.id)" class="text-sm text-primary-600 hover:underline dark:text-primary-400">
                                    {{ reminder.client.name }}
                                </Link>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ formatDate(reminder.remind_at) }}</p>
                                <p v-if="reminder.description" class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ reminder.description }}</p>
                            </div>
                            <div class="flex gap-1">
                                <button @click="completeReminder(reminder)" class="rounded-lg p-2 text-slate-400 hover:bg-emerald-50 hover:text-emerald-600 dark:hover:bg-emerald-900/20" :title="t('crm.mark_completed')">
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <button @click="deleteReminder(reminder)" class="rounded-lg p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/30">
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-12 text-slate-500 dark:text-slate-400">
                    <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="mt-2">{{ t('crm.no_reminders') }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
