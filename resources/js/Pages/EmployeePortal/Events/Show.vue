<script setup>
import EmployeePortalLayout from '@/Layouts/EmployeePortalLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    event: { type: Object, required: true },
    canEdit: { type: Boolean, default: false },
});

const formatDate = (iso) => {
    if (!iso) return '';
    return new Date(iso).toLocaleString('fr-FR', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
};

const deleteEvent = () => {
    if (!confirm(t('hr_event_delete_confirm'))) return;
    router.delete(route('employee-portal.shared-calendar.events.destroy', props.event.id));
};

const internalParticipants = computed(() => (props.event.participants || []).filter(p => p.employee));
const externalParticipants = computed(() => (props.event.participants || []).filter(p => p.external_email && !p.employee));
</script>

<template>
    <Head :title="event.title" />

    <EmployeePortalLayout>
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ event.title }}</h1>
                <div v-if="canEdit" class="flex gap-2">
                    <Link :href="route('employee-portal.shared-calendar.events.edit', event.id)" class="inline-flex items-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-slate-300 dark:ring-slate-600">{{ t('edit') }}</Link>
                    <button @click="deleteEvent" class="inline-flex items-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-pink-600 shadow-sm ring-1 ring-inset ring-gray-200 hover:bg-pink-50 dark:bg-gray-800 dark:ring-slate-600">{{ t('delete') }}</button>
                </div>
            </div>

            <div class="bg-white dark:bg-surface-card rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-4">
                <div>
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium" :style="{ backgroundColor: event.effective_color + '20', color: event.effective_color }">
                        {{ t('hr_event_type_' + event.type) }}
                    </span>
                </div>
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ t('hr_event_starts_at') }}</p>
                    <p class="font-medium text-slate-900 dark:text-white">{{ formatDate(event.starts_at) }}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ t('hr_event_ends_at') }}</p>
                    <p class="font-medium text-slate-900 dark:text-white">{{ formatDate(event.ends_at) }}</p>
                </div>
                <div v-if="event.location_type !== 'none'">
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ t('hr_event_location') }}</p>
                    <p class="font-medium text-slate-900 dark:text-white">
                        <span v-if="event.location_type === 'room'">{{ event.room?.name }}</span>
                        <span v-else-if="event.location_type === 'address'">{{ event.address }}</span>
                        <a v-else-if="event.location_type === 'video'" :href="event.video_url" target="_blank" rel="noopener" class="text-primary-500 hover:underline">{{ event.video_url }}</a>
                    </p>
                </div>
                <div v-if="event.creator">
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ t('hr_event_creator') }}</p>
                    <p class="font-medium text-slate-900 dark:text-white">{{ event.creator.first_name }} {{ event.creator.last_name }}</p>
                </div>
                <div v-if="event.description">
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ t('hr_event_description') }}</p>
                    <p class="text-slate-900 dark:text-white whitespace-pre-wrap">{{ event.description }}</p>
                </div>
            </div>

            <div v-if="(internalParticipants.length + externalParticipants.length) > 0" class="bg-white dark:bg-surface-card rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-medium text-slate-900 dark:text-white mb-4">{{ t('hr_event_participants') }} ({{ internalParticipants.length + externalParticipants.length }})</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div v-for="p in internalParticipants" :key="'emp-' + p.id" class="flex items-center gap-3 p-2 rounded-lg">
                        <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center text-xs font-medium text-slate-600 dark:bg-gray-700 dark:text-slate-300">
                            {{ p.employee.first_name?.[0] }}{{ p.employee.last_name?.[0] }}
                        </div>
                        <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ p.employee.first_name }} {{ p.employee.last_name }}</p>
                    </div>
                    <div v-for="p in externalParticipants" :key="'ext-' + p.id" class="flex items-center gap-3 p-2 rounded-lg">
                        <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">@</div>
                        <p class="text-sm text-slate-700 dark:text-slate-300 truncate">{{ p.external_email }}</p>
                    </div>
                </div>
            </div>
        </div>
    </EmployeePortalLayout>
</template>
