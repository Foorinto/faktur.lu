<script setup>
import EmployeePortalLayout from '@/Layouts/EmployeePortalLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    rooms: { type: Array, required: true },
    employees: { type: Array, required: true },
    types: { type: Array, required: true },
    currentEmployeeId: { type: [Number, null], default: null },
    date: { type: String, default: null },
});

const defaultStart = computed(() => {
    if (props.date) return `${props.date}T09:00`;
    return new Date(Date.now() + 3600 * 1000).toISOString().slice(0, 16);
});
const defaultEnd = computed(() => {
    const start = new Date(defaultStart.value);
    start.setHours(start.getHours() + 1);
    return start.toISOString().slice(0, 16);
});

const form = useForm({
    title: '',
    type: 'meeting',
    starts_at: defaultStart.value,
    ends_at: defaultEnd.value,
    is_all_day: false,
    location_type: 'none',
    room_id: null,
    address: '',
    video_url: '',
    description: '',
    participant_employee_ids: props.currentEmployeeId ? [props.currentEmployeeId] : [],
    participant_external_emails: [],
});

const externalEmailInput = ref('');

const addExternalEmail = () => {
    const email = externalEmailInput.value.trim();
    if (email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) && !form.participant_external_emails.includes(email)) {
        form.participant_external_emails.push(email);
        externalEmailInput.value = '';
    }
};
const removeExternalEmail = (email) => {
    form.participant_external_emails = form.participant_external_emails.filter(e => e !== email);
};
const toggleEmployee = (id) => {
    if (form.participant_employee_ids.includes(id)) {
        form.participant_employee_ids = form.participant_employee_ids.filter(e => e !== id);
    } else {
        form.participant_employee_ids.push(id);
    }
};

const submit = () => form.post(route('employee-portal.shared-calendar.events.store'));
</script>

<template>
    <Head :title="t('hr_event_new')" />

    <EmployeePortalLayout>
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">{{ t('hr_event_new') }}</h1>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="bg-white dark:bg-surface-card rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-4">
                    <div>
                        <InputLabel for="title" :value="t('hr_event_title')" />
                        <input id="title" v-model="form.title" type="text" required maxlength="200" class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                        <InputError :message="form.errors.title" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="type" :value="t('hr_event_type')" />
                        <select v-model="form.type" class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option v-for="type in types" :key="type" :value="type">{{ t('hr_event_type_' + type) }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel :value="t('hr_event_starts_at')" />
                            <input v-model="form.starts_at" type="datetime-local" required class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                            <InputError :message="form.errors.starts_at" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel :value="t('hr_event_ends_at')" />
                            <input v-model="form.ends_at" type="datetime-local" required class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                            <InputError :message="form.errors.ends_at" class="mt-2" />
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="checkbox" v-model="form.is_all_day" class="rounded text-primary-500" />
                        {{ t('hr_event_all_day') }}
                    </label>
                </div>

                <div class="bg-white dark:bg-surface-card rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-4">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr_event_location') }}</h2>
                    <div class="flex flex-wrap gap-3">
                        <label v-for="lt in ['none', 'room', 'address', 'video']" :key="lt" class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="radio" v-model="form.location_type" :value="lt" />
                            {{ t('hr_event_location_' + lt) }}
                        </label>
                    </div>
                    <div v-if="form.location_type === 'room'">
                        <select v-model="form.room_id" class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option :value="null">- {{ t('hr_room_select') }} -</option>
                            <option v-for="room in rooms" :key="room.id" :value="room.id">{{ room.name }}<span v-if="room.capacity"> ({{ room.capacity }})</span></option>
                        </select>
                        <InputError :message="form.errors.room_id" class="mt-2" />
                    </div>
                    <div v-if="form.location_type === 'address'">
                        <input v-model="form.address" type="text" maxlength="500" :placeholder="t('hr_event_address')" class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                    </div>
                    <div v-if="form.location_type === 'video'">
                        <input v-model="form.video_url" type="url" maxlength="500" placeholder="https://..." class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                    </div>
                </div>

                <div class="bg-white dark:bg-surface-card rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-4">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr_event_participants') }}</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-60 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-xl p-3">
                        <label v-for="emp in employees" :key="emp.id" class="flex items-center gap-2 text-sm cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 rounded px-2 py-1">
                            <input type="checkbox" :checked="form.participant_employee_ids.includes(emp.id)" @change="toggleEmployee(emp.id)" class="rounded text-primary-500" />
                            {{ emp.first_name }} {{ emp.last_name }}
                        </label>
                    </div>

                    <div class="flex gap-2">
                        <input v-model="externalEmailInput" type="email" :placeholder="t('hr_event_external_email_placeholder')" @keydown.enter.prevent="addExternalEmail" class="flex-1 rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                        <button type="button" @click="addExternalEmail" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl text-sm font-medium dark:bg-gray-700 dark:text-slate-300">{{ t('hr_event_add') }}</button>
                    </div>
                    <div v-if="form.participant_external_emails.length > 0" class="flex flex-wrap gap-2">
                        <span v-for="email in form.participant_external_emails" :key="email" class="inline-flex items-center gap-1 px-2 py-1 bg-primary-50 text-primary-700 rounded-full text-xs">
                            {{ email }}
                            <button type="button" @click="removeExternalEmail(email)" class="hover:bg-primary-100 rounded-full p-0.5">×</button>
                        </span>
                    </div>
                </div>

                <div class="bg-white dark:bg-surface-card rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <InputLabel :value="t('hr_event_description')" />
                    <textarea v-model="form.description" rows="4" maxlength="5000" class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"></textarea>
                </div>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Link :href="route('employee-portal.shared-calendar.index')" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300">{{ t('cancel') }}</Link>
                    <PrimaryButton :disabled="form.processing">{{ form.processing ? t('loading') : t('hr_event_create') }}</PrimaryButton>
                </div>
            </form>
        </div>
    </EmployeePortalLayout>
</template>
