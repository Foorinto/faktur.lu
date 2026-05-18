<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import HRNav from '@/Components/HRNav.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    rooms: { type: Object, required: true },
});

const EQUIPMENT_OPTIONS = ['projector', 'whiteboard', 'video_conf', 'tv_screen', 'conference_phone', 'flipchart'];

const showForm = ref(false);
const editingId = ref(null);

const form = useForm({
    name: '',
    capacity: null,
    location: '',
    equipment: [],
    active: true,
});

const resetForm = () => {
    form.reset();
    editingId.value = null;
    showForm.value = false;
};

const startEdit = (room) => {
    editingId.value = room.id;
    form.name = room.name;
    form.capacity = room.capacity;
    form.location = room.location || '';
    form.equipment = room.equipment || [];
    form.active = room.active;
    showForm.value = true;
};

const toggleEquipment = (eq) => {
    if (form.equipment.includes(eq)) {
        form.equipment = form.equipment.filter(e => e !== eq);
    } else {
        form.equipment.push(eq);
    }
};

const submit = () => {
    if (editingId.value) {
        form.put(route('hr.rooms.update', editingId.value), {
            onSuccess: resetForm,
        });
    } else {
        form.post(route('hr.rooms.store'), {
            onSuccess: resetForm,
        });
    }
};

const deleteRoom = (room) => {
    if (!confirm(t('hr_room_delete_confirm', { name: room.name }))) return;
    router.delete(route('hr.rooms.destroy', room.id));
};
</script>

<template>
    <Head :title="t('hr_room_management')" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">{{ t('hr_room_management') }}</h1>
        </template>
        <template #header-actions>
            <Link
                :href="route('hr.shared-calendar.index')"
                class="inline-flex items-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-slate-300 dark:ring-slate-600"
            >
                {{ t('hr_calendar_title') }}
            </Link>
            <button
                v-if="!showForm"
                @click="showForm = true"
                class="inline-flex items-center gap-1.5 rounded-xl bg-primary-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                {{ t('hr_room_new') }}
            </button>
        </template>

        <HRNav class="mb-6" />

        <div class="py-6">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Form (collapsible) -->
                <div v-if="showForm" class="bg-white dark:bg-surface-card rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white mb-4">
                        {{ editingId ? t('hr_room_edit') : t('hr_room_new') }}
                    </h2>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <InputLabel for="name" :value="t('hr_room_name')" />
                            <input id="name" v-model="form.name" type="text" required maxlength="150" class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="capacity" :value="t('hr_room_capacity')" />
                                <input id="capacity" v-model="form.capacity" type="number" min="1" max="1000" class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                            </div>
                            <div>
                                <InputLabel for="location" :value="t('hr_room_location')" />
                                <input id="location" v-model="form.location" type="text" maxlength="200" class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                            </div>
                        </div>

                        <div>
                            <InputLabel :value="t('hr_room_equipment')" />
                            <div class="mt-2 flex flex-wrap gap-2">
                                <button
                                    v-for="eq in EQUIPMENT_OPTIONS"
                                    :key="eq"
                                    type="button"
                                    @click="toggleEquipment(eq)"
                                    :class="[
                                        'px-3 py-1.5 rounded-full text-sm font-medium border transition-colors',
                                        form.equipment.includes(eq)
                                            ? 'bg-primary-500 text-white border-primary-500'
                                            : 'bg-white text-slate-700 border-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-slate-300 dark:border-gray-700'
                                    ]"
                                >
                                    {{ t('hr_room_eq_' + eq) }}
                                </button>
                            </div>
                        </div>

                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" v-model="form.active" class="rounded text-primary-500" />
                            {{ t('hr_room_active') }}
                        </label>

                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button type="button" @click="resetForm" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300">{{ t('cancel') }}</button>
                            <PrimaryButton :disabled="form.processing">{{ form.processing ? t('loading') : t('save') }}</PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- List -->
                <div class="bg-white dark:bg-surface-card rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div v-if="rooms.data.length === 0" class="p-12 text-center">
                        <p class="text-slate-500 dark:text-slate-400">{{ t('hr_room_empty') }}</p>
                    </div>
                    <table v-else class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">{{ t('hr_room_name') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">{{ t('hr_room_capacity') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">{{ t('hr_room_location') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">{{ t('status') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <tr v-for="room in rooms.data" :key="room.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-6 py-3 text-sm font-medium text-slate-900 dark:text-white">{{ room.name }}</td>
                                <td class="px-6 py-3 text-sm text-slate-600 dark:text-slate-400">{{ room.capacity || '—' }}</td>
                                <td class="px-6 py-3 text-sm text-slate-600 dark:text-slate-400">{{ room.location || '—' }}</td>
                                <td class="px-6 py-3 text-sm">
                                    <span :class="room.active ? 'text-emerald-600' : 'text-slate-400'">
                                        {{ room.active ? t('hr_room_active') : t('hr_room_inactive') }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right text-sm space-x-2">
                                    <button @click="startEdit(room)" class="text-primary-500 hover:underline">{{ t('edit') }}</button>
                                    <button @click="deleteRoom(room)" class="text-pink-600 hover:underline">{{ t('delete') }}</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
