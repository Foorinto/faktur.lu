<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';
import axios from 'axios';

const { t } = useTranslations();

const props = defineProps({
    clientTags: {
        type: Array,
        default: () => [],
    },
    availableTags: {
        type: Array,
        default: () => [],
    },
    clientId: {
        type: Number,
        required: true,
    },
});

const showDropdown = ref(false);
const showCreateForm = ref(false);
const newTagName = ref('');
const newTagColor = ref('#6366f1');

const presetColors = [
    '#ef4444', '#f97316', '#eab308', '#22c55e', '#14b8a6',
    '#3b82f6', '#6366f1', '#8b5cf6', '#ec4899', '#64748b',
];

const unassignedTags = () => {
    const assignedIds = props.clientTags.map(t => t.id);
    return props.availableTags.filter(t => !assignedIds.includes(t.id));
};

const attachTag = (tag) => {
    router.post(route('tags.attach', [props.clientId, tag.id]), {}, {
        preserveScroll: true,
    });
    showDropdown.value = false;
};

const detachTag = (tag) => {
    router.delete(route('tags.detach', [props.clientId, tag.id]), {
        preserveScroll: true,
    });
};

const createAndAttach = async () => {
    if (!newTagName.value.trim()) return;

    try {
        const response = await axios.post(route('tags.store'), {
            name: newTagName.value.trim(),
            color: newTagColor.value,
        });

        const newTag = response.data;
        router.post(route('tags.attach', [props.clientId, newTag.id]), {}, {
            preserveScroll: true,
        });

        newTagName.value = '';
        newTagColor.value = '#6366f1';
        showCreateForm.value = false;
        showDropdown.value = false;
    } catch (e) {
        // Handle silently
    }
};
</script>

<template>
    <div>
        <!-- Current tags -->
        <div class="flex flex-wrap gap-1.5">
            <span
                v-for="tag in clientTags"
                :key="tag.id"
                class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-white"
                :style="{ backgroundColor: tag.color }"
            >
                {{ tag.name }}
                <button
                    @click="detachTag(tag)"
                    class="ml-0.5 rounded-full p-0.5 hover:bg-white/20"
                >
                    <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                    </svg>
                </button>
            </span>

            <!-- Add tag button -->
            <div class="relative">
                <button
                    @click="showDropdown = !showDropdown"
                    class="inline-flex items-center rounded-lg border border-dashed border-gray-300 px-2 py-1 text-xs text-slate-500 hover:border-primary-400 hover:text-primary-600 dark:border-gray-700 dark:text-slate-400 dark:hover:border-primary-500 dark:hover:text-primary-400"
                >
                    <svg class="mr-1 h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    {{ t('crm.add_tag') }}
                </button>

                <!-- Dropdown -->
                <div
                    v-if="showDropdown"
                    class="absolute left-0 top-full z-10 mt-1 w-56 rounded-xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-surface-card"
                >
                    <!-- Existing tags -->
                    <div v-if="unassignedTags().length > 0" class="max-h-40 overflow-y-auto p-2">
                        <button
                            v-for="tag in unassignedTags()"
                            :key="tag.id"
                            @click="attachTag(tag)"
                            class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-gray-50 dark:hover:bg-gray-800"
                        >
                            <span class="h-3 w-3 rounded-full" :style="{ backgroundColor: tag.color }"></span>
                            <span class="text-slate-900 dark:text-white">{{ tag.name }}</span>
                        </button>
                    </div>
                    <div v-else class="p-2 text-center text-xs text-slate-500">
                        {{ t('crm.no_tags_available') }}
                    </div>

                    <!-- Create new -->
                    <div class="border-t border-gray-200 p-2 dark:border-gray-700">
                        <button
                            v-if="!showCreateForm"
                            @click="showCreateForm = true"
                            class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-primary-600 hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-900/20"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                            </svg>
                            {{ t('crm.new_tag') }}
                        </button>
                        <div v-else class="space-y-2">
                            <input
                                v-model="newTagName"
                                type="text"
                                :placeholder="t('crm.tag_name')"
                                class="block w-full rounded-lg border-0 py-1 text-sm ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600"
                                @keyup.enter="createAndAttach"
                            />
                            <div class="flex flex-wrap gap-1">
                                <button
                                    v-for="color in presetColors"
                                    :key="color"
                                    @click="newTagColor = color"
                                    :class="{ 'ring-2 ring-offset-1 ring-slate-900 dark:ring-white': newTagColor === color }"
                                    class="h-5 w-5 rounded-full"
                                    :style="{ backgroundColor: color }"
                                ></button>
                            </div>
                            <div class="flex justify-end gap-1">
                                <button @click="showCreateForm = false" class="rounded-lg px-2 py-1 text-xs text-slate-600 hover:bg-gray-50 dark:text-slate-400">
                                    {{ t('cancel') }}
                                </button>
                                <button @click="createAndAttach" class="rounded-lg bg-accent-rose px-2 py-1 text-xs text-white hover:bg-pink-500">
                                    {{ t('crm.create_tag') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
