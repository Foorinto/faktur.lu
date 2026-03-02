<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    reminders: {
        type: Array,
        default: () => [],
    },
    clientId: {
        type: Number,
        required: true,
    },
    overdueCount: {
        type: Number,
        default: 0,
    },
});

const showForm = ref(false);

const form = useForm({
    title: '',
    description: '',
    remind_at: '',
});

const submit = () => {
    form.post(route('reminders.store', props.clientId), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        },
    });
};

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

const isOverdue = (reminder) => {
    return !reminder.is_completed && new Date(reminder.remind_at) < new Date();
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-medium text-slate-900 dark:text-white">
                {{ t('crm.reminders') }}
                <span v-if="overdueCount > 0" class="ml-1 inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700 dark:bg-rose-900 dark:text-rose-300">
                    {{ overdueCount }}
                </span>
            </h3>
            <button
                @click="showForm = !showForm"
                class="rounded-lg p-1 text-slate-400 hover:bg-gray-50 hover:text-primary-600 dark:hover:bg-gray-800 dark:hover:text-primary-400"
            >
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                </svg>
            </button>
        </div>

        <!-- Add form -->
        <div v-if="showForm" class="mb-3 rounded-xl border border-gray-200 p-3 dark:border-gray-700">
            <form @submit.prevent="submit" class="space-y-2">
                <input
                    v-model="form.title"
                    type="text"
                    :placeholder="t('crm.reminder_title')"
                    class="block w-full rounded-lg border-0 py-1.5 text-sm ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600"
                />
                <div v-if="form.errors.title" class="text-xs text-rose-600">{{ form.errors.title }}</div>
                <input
                    v-model="form.remind_at"
                    type="datetime-local"
                    class="block w-full rounded-lg border-0 py-1.5 text-sm ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600"
                />
                <div v-if="form.errors.remind_at" class="text-xs text-rose-600">{{ form.errors.remind_at }}</div>
                <textarea
                    v-model="form.description"
                    rows="2"
                    :placeholder="t('crm.reminder_description')"
                    class="block w-full rounded-lg border-0 py-1.5 text-sm ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600"
                ></textarea>
                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        @click="showForm = false; form.reset()"
                        class="rounded-lg px-2 py-1 text-xs text-slate-600 hover:bg-gray-50 dark:text-slate-400"
                    >
                        {{ t('cancel') }}
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-accent-rose px-2 py-1 text-xs text-white hover:bg-pink-500 disabled:opacity-50"
                    >
                        {{ t('crm.add_reminder') }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Reminders list -->
        <div v-if="reminders.length > 0" class="space-y-2">
            <div
                v-for="reminder in reminders"
                :key="reminder.id"
                :class="[
                    'rounded-xl border p-3',
                    isOverdue(reminder)
                        ? 'border-rose-200 bg-rose-50 dark:border-rose-800 dark:bg-rose-900/20'
                        : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-surface-card'
                ]"
            >
                <div class="flex items-start justify-between">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium" :class="isOverdue(reminder) ? 'text-rose-700 dark:text-rose-300' : 'text-slate-900 dark:text-white'">
                            {{ reminder.title }}
                        </p>
                        <p class="mt-0.5 text-xs" :class="isOverdue(reminder) ? 'text-rose-600 dark:text-rose-400' : 'text-slate-500 dark:text-slate-400'">
                            {{ formatDate(reminder.remind_at) }}
                            <span v-if="isOverdue(reminder)" class="ml-1 font-medium">{{ t('crm.overdue') }}</span>
                        </p>
                        <p v-if="reminder.description" class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                            {{ reminder.description }}
                        </p>
                    </div>
                    <div class="flex items-center gap-1 ml-2">
                        <button
                            @click="completeReminder(reminder)"
                            class="rounded-lg p-1 text-slate-400 hover:bg-emerald-50 hover:text-emerald-600 dark:hover:bg-emerald-900/20 dark:hover:text-emerald-400"
                            :title="t('crm.mark_completed')"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <button
                            @click="deleteReminder(reminder)"
                            class="rounded-lg p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/20"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <p v-else class="text-center text-xs text-slate-500 dark:text-slate-400 py-3">
            {{ t('crm.no_reminders') }}
        </p>
    </div>
</template>
