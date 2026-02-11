<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import { ref } from 'vue';

const { t } = useTranslations();

const props = defineProps({
    ticket: {
        type: Object,
        required: true,
    },
    statuses: {
        type: Object,
        required: true,
    },
    canReply: {
        type: Boolean,
        default: true,
    },
});

const form = useForm({
    message: '',
    attachment: null,
});

const fileInput = ref(null);
const fileName = ref('');

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.attachment = file;
        fileName.value = file.name;
    }
};

const removeFile = () => {
    form.attachment = null;
    fileName.value = '';
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submit = () => {
    form.post(route('support.reply', props.ticket.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            removeFile();
        },
    });
};

const getStatusBadgeClass = (status) => {
    const classes = {
        new: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        open: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        in_progress: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        waiting: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
        resolved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        closed: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-400',
    };
    return classes[status] || classes.closed;
};

const formatDateTime = (dateString) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};
</script>

<template>
    <Head :title="ticket.reference + ' - ' + ticket.subject" />

    <AppLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link
                    :href="route('support.index')"
                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-300"
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                        {{ ticket.reference }}
                    </h1>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-3xl">
            <!-- Ticket header -->
            <div class="mb-6 rounded-2xl bg-white p-6 border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">
                            {{ ticket.subject }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{ t('created_on') }} {{ formatDateTime(ticket.created_at) }}
                        </p>
                    </div>
                    <span
                        :class="getStatusBadgeClass(ticket.status)"
                        class="inline-flex items-center rounded-xl px-3 py-1 text-sm font-medium"
                    >
                        {{ statuses[ticket.status] }}
                    </span>
                </div>
            </div>

            <!-- Messages -->
            <div class="space-y-4 mb-6">
                <div
                    v-for="message in ticket.messages"
                    :key="message.id"
                    :class="[
                        'rounded-2xl p-4',
                        message.sender_type === 'admin' || message.sender_type === 'App\\Models\\AdminSession'
                            ? 'bg-primary-50 border border-primary-100 dark:bg-primary-900/20 dark:border-primary-800'
                            : 'bg-white border border-slate-200 dark:bg-slate-800 dark:border-slate-700'
                    ]"
                >
                    <div class="flex items-center gap-2 mb-2">
                        <div
                            :class="[
                                'h-8 w-8 rounded-full flex items-center justify-center text-sm font-medium',
                                message.sender_type === 'admin' || message.sender_type === 'App\\Models\\AdminSession'
                                    ? 'bg-primary-500 text-white'
                                    : 'bg-slate-200 text-slate-600 dark:bg-slate-600 dark:text-slate-300'
                            ]"
                        >
                            <template v-if="message.sender_type === 'admin' || message.sender_type === 'App\\Models\\AdminSession'">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-5.5-2.5a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0zM10 12a5.99 5.99 0 00-4.793 2.39A6.483 6.483 0 0010 16.5a6.483 6.483 0 004.793-2.11A5.99 5.99 0 0010 12z" clip-rule="evenodd" />
                                </svg>
                            </template>
                            <template v-else>
                                {{ message.sender?.name?.charAt(0)?.toUpperCase() || 'U' }}
                            </template>
                        </div>
                        <div class="flex-1">
                            <span
                                :class="[
                                    'text-sm font-medium',
                                    message.sender_type === 'admin' || message.sender_type === 'App\\Models\\AdminSession'
                                        ? 'text-primary-700 dark:text-primary-400'
                                        : 'text-slate-900 dark:text-white'
                                ]"
                            >
                                {{ message.sender_type === 'admin' || message.sender_type === 'App\\Models\\AdminSession' ? 'Support' : (message.sender?.name || t('you')) }}
                            </span>
                            <span class="text-xs text-slate-500 dark:text-slate-400 ml-2">
                                {{ formatDateTime(message.created_at) }}
                            </span>
                        </div>
                    </div>
                    <div class="pl-10 text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap">
                        {{ message.content }}
                    </div>

                    <!-- Attachments -->
                    <div v-if="message.attachments && message.attachments.length > 0" class="mt-3 pl-10">
                        <div v-for="attachment in message.attachments" :key="attachment.id" class="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-1.5 text-sm dark:bg-slate-700">
                            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M15.621 4.379a3 3 0 00-4.242 0l-7 7a3 3 0 004.241 4.243h.001l.497-.5a.75.75 0 011.064 1.057l-.498.501-.002.002a4.5 4.5 0 01-6.364-6.364l7-7a4.5 4.5 0 016.368 6.36l-3.455 3.553A2.625 2.625 0 119.52 9.52l3.45-3.451a.75.75 0 111.061 1.06l-3.45 3.451a1.125 1.125 0 001.587 1.595l3.454-3.553a3 3 0 000-4.242z" clip-rule="evenodd" />
                            </svg>
                            <a :href="attachment.url" target="_blank" class="text-primary-600 hover:text-primary-500 dark:text-primary-400">
                                {{ attachment.filename }}
                            </a>
                            <span class="text-slate-400">{{ attachment.size_formatted }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reply form -->
            <div v-if="canReply" class="rounded-2xl bg-white p-6 border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">
                    {{ t('reply') }}
                </h3>
                <form @submit.prevent="submit">
                    <textarea
                        v-model="form.message"
                        rows="4"
                        class="block w-full rounded-xl border-0 py-2 px-3 text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 dark:placeholder:text-slate-500 sm:text-sm sm:leading-6"
                        :placeholder="t('your_reply')"
                        required
                    />
                    <InputError :message="form.errors.message" class="mt-2" />

                    <!-- Attachment -->
                    <div class="mt-4">
                        <div v-if="fileName" class="flex items-center justify-between rounded-xl bg-slate-50 p-3 dark:bg-slate-700">
                            <div class="flex items-center gap-2">
                                <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M15.621 4.379a3 3 0 00-4.242 0l-7 7a3 3 0 004.241 4.243h.001l.497-.5a.75.75 0 011.064 1.057l-.498.501-.002.002a4.5 4.5 0 01-6.364-6.364l7-7a4.5 4.5 0 016.368 6.36l-3.455 3.553A2.625 2.625 0 119.52 9.52l3.45-3.451a.75.75 0 111.061 1.06l-3.45 3.451a1.125 1.125 0 001.587 1.595l3.454-3.553a3 3 0 000-4.242z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-sm text-slate-600 dark:text-slate-300">{{ fileName }}</span>
                            </div>
                            <button
                                type="button"
                                @click="removeFile"
                                class="rounded p-1 text-slate-400 hover:bg-slate-200 hover:text-slate-600 dark:hover:bg-slate-600"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                </svg>
                            </button>
                        </div>
                        <label
                            v-else
                            class="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-500 hover:text-primary-600 dark:text-slate-400 dark:hover:text-primary-400"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                            <span>{{ t('add_attachment') }}</span>
                            <input
                                ref="fileInput"
                                type="file"
                                class="hidden"
                                @change="handleFileChange"
                                accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt"
                            />
                        </label>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <PrimaryButton :disabled="form.processing">
                            {{ t('send') }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>

            <!-- Closed ticket message -->
            <div v-else class="rounded-2xl bg-slate-50 p-6 text-center dark:bg-slate-800/50">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    {{ t('ticket_closed_message') }}
                </p>
                <Link
                    :href="route('support.create')"
                    class="mt-4 inline-flex items-center text-primary-600 hover:text-primary-500 dark:text-primary-400"
                >
                    {{ t('new_request') }}
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
