<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    tickets: {
        type: Object,
        required: true,
    },
    statuses: {
        type: Object,
        required: true,
    },
});

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

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(date);
};

const formatTimeAgo = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 60) return `${diffMins} min`;
    if (diffHours < 24) return `${diffHours}h`;
    return `${diffDays}j`;
};
</script>

<template>
    <Head :title="t('support')" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ t('support') }}
                </h1>
                <Link
                    :href="route('support.create')"
                    class="inline-flex items-center rounded-xl bg-primary-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                >
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    {{ t('new_request') }}
                </Link>
            </div>
        </template>

        <!-- Tickets list -->
        <div class="space-y-4">
            <h2 class="text-lg font-medium text-slate-900 dark:text-white">
                {{ t('your_requests') }} ({{ tickets.total }})
            </h2>

            <div v-if="tickets.data.length === 0" class="rounded-2xl bg-white p-12 text-center shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
                <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">{{ t('no_support_requests') }}</p>
                <Link
                    :href="route('support.create')"
                    class="mt-4 inline-flex items-center text-primary-600 hover:text-primary-500 dark:text-primary-400"
                >
                    {{ t('create_first_request') }}
                </Link>
            </div>

            <div v-else class="space-y-4">
                <Link
                    v-for="ticket in tickets.data"
                    :key="ticket.id"
                    :href="route('support.show', ticket.id)"
                    class="block rounded-2xl bg-white p-6 border border-slate-200 hover:border-primary-300 hover:shadow-primary-200/30 transition-all dark:bg-slate-800 dark:border-slate-700 dark:hover:border-primary-500/50"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-medium text-slate-500 dark:text-slate-400">
                                    {{ ticket.reference }}
                                </span>
                                <span
                                    :class="getStatusBadgeClass(ticket.status)"
                                    class="inline-flex items-center rounded-xl px-2.5 py-0.5 text-xs font-medium"
                                >
                                    {{ statuses[ticket.status] }}
                                </span>
                            </div>
                            <h3 class="mt-2 text-lg font-medium text-slate-900 dark:text-white">
                                {{ ticket.subject }}
                            </h3>
                            <div class="mt-2 flex items-center gap-4 text-sm text-slate-500 dark:text-slate-400">
                                <span>{{ formatDate(ticket.created_at) }}</span>
                                <span v-if="ticket.latest_message">
                                    {{ t('last_response') }}: {{ formatTimeAgo(ticket.latest_message.created_at) }}
                                </span>
                                <span>{{ ticket.messages_count }} {{ t('messages') }}</span>
                            </div>
                        </div>
                        <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="tickets.links && tickets.links.length > 3" class="mt-6 flex items-center justify-between">
                <div class="text-sm text-slate-700 dark:text-slate-400">
                    {{ t('showing_x_to_y_of_z', { from: tickets.from, to: tickets.to, total: tickets.total, items: t('requests').toLowerCase() }) }}
                </div>
                <nav class="isolate inline-flex -space-x-px rounded-xl shadow-sm">
                    <template v-for="(link, index) in tickets.links" :key="index">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                link.active
                                    ? 'z-10 bg-primary-500 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500'
                                    : 'text-slate-900 ring-1 ring-inset ring-slate-200 hover:bg-slate-50 dark:text-slate-300 dark:ring-slate-600 dark:hover:bg-slate-700',
                                'relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20',
                                index === 0 ? 'rounded-l-xl' : '',
                                index === tickets.links.length - 1 ? 'rounded-r-xl' : '',
                            ]"
                            v-html="link.label"
                            preserve-scroll
                        />
                        <span
                            v-else
                            :class="[
                                'relative inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-400 dark:text-slate-500',
                                index === 0 ? 'rounded-l-xl' : '',
                                index === tickets.links.length - 1 ? 'rounded-r-xl' : '',
                            ]"
                            v-html="link.label"
                        />
                    </template>
                </nav>
            </div>
        </div>
    </AppLayout>
</template>
