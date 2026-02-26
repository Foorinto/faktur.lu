<script setup>
import { ref, computed, nextTick, onMounted, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import RichTextDisplay from '@/Components/RichTextDisplay.vue';

const { t } = useTranslations();

const props = defineProps({
    interactions: {
        type: [Array, Object],
        default: () => [],
    },
    clientId: {
        type: Number,
        required: true,
    },
    interactionTypes: {
        type: Array,
        default: () => ['note', 'call', 'email', 'meeting', 'other'],
    },
});

const showForm = ref(false);
const editingId = ref(null);

const form = useForm({
    type: 'note',
    content: '',
    contacted_at: new Date().toISOString().slice(0, 16),
    subject: '',
    send_email: false,
});

const editForm = useForm({
    type: 'note',
    content: '',
    contacted_at: '',
});

const typeIcons = {
    note: 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
    call: 'M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z',
    email: 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75',
    meeting: 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z',
    other: 'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
};

const typeColors = {
    note: 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
    call: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900 dark:text-emerald-300',
    email: 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300',
    meeting: 'bg-violet-100 text-violet-600 dark:bg-violet-900 dark:text-violet-300',
    other: 'bg-amber-100 text-amber-600 dark:bg-amber-900 dark:text-amber-300',
};

const submit = () => {
    form.post(route('interactions.store', props.clientId), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.contacted_at = new Date().toISOString().slice(0, 16);
            showForm.value = false;
        },
    });
};

const startEdit = (interaction) => {
    editingId.value = interaction.id;
    editForm.type = interaction.type;
    editForm.content = interaction.content;
    editForm.contacted_at = interaction.contacted_at?.slice(0, 16) || '';
};

const cancelEdit = () => {
    editingId.value = null;
    editForm.reset();
};

const saveEdit = (interaction) => {
    editForm.put(route('interactions.update', interaction.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
            editForm.reset();
        },
    });
};

const deleteInteraction = (interaction) => {
    if (confirm(t('crm.confirm_delete_interaction'))) {
        router.delete(route('interactions.destroy', interaction.id), {
            preserveScroll: true,
        });
    }
};

const items = computed(() => Array.isArray(props.interactions) ? props.interactions : (props.interactions?.data || []));

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Collapsible content
const expandedIds = ref(new Set());
const overflowingIds = ref(new Set());
const contentRefs = ref({});

const setContentRef = (id, el) => {
    if (el) contentRefs.value[id] = el;
};

const toggleExpand = (id) => {
    if (expandedIds.value.has(id)) {
        expandedIds.value.delete(id);
    } else {
        expandedIds.value.add(id);
    }
    // Force reactivity
    expandedIds.value = new Set(expandedIds.value);
};

const isExpanded = (id) => expandedIds.value.has(id);
const isOverflowing = (id) => overflowingIds.value.has(id);

const checkOverflows = () => {
    nextTick(() => {
        const newOverflowing = new Set();
        for (const [id, el] of Object.entries(contentRefs.value)) {
            if (el && el.scrollHeight > el.clientHeight + 1) {
                newOverflowing.add(Number(id));
            }
        }
        overflowingIds.value = newOverflowing;
    });
};

onMounted(checkOverflows);
watch(items, checkOverflows);
</script>

<template>
    <div>
        <!-- Add interaction button -->
        <div class="mb-4">
            <button
                v-if="!showForm"
                @click="showForm = true"
                class="inline-flex items-center rounded-xl bg-primary-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600"
            >
                <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                </svg>
                {{ t('crm.add_interaction') }}
            </button>
        </div>

        <!-- Add form -->
        <div v-if="showForm" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
            <h3 class="mb-3 text-sm font-medium text-slate-900 dark:text-white">{{ t('crm.new_interaction') }}</h3>
            <form @submit.prevent="submit" class="space-y-3">
                <div class="flex gap-3">
                    <select
                        v-model="form.type"
                        class="rounded-xl border-0 py-1.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600"
                    >
                        <option v-for="type in interactionTypes" :key="type" :value="type">
                            {{ t(`crm.type_${type}`) }}
                        </option>
                    </select>
                    <input
                        v-model="form.contacted_at"
                        type="datetime-local"
                        class="rounded-xl border-0 py-1.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600"
                    />
                </div>
                <!-- Email-specific fields -->
                <div v-if="form.type === 'email'" class="space-y-3">
                    <input
                        v-model="form.subject"
                        type="text"
                        :placeholder="t('crm.email_subject')"
                        class="block w-full rounded-xl border-0 py-1.5 text-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600"
                    />
                    <div v-if="form.errors.subject" class="text-sm text-rose-600">{{ form.errors.subject }}</div>
                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                        <input
                            v-model="form.send_email"
                            type="checkbox"
                            class="rounded border-slate-300 text-primary-500 focus:ring-primary-500 dark:border-slate-600"
                        />
                        {{ t('crm.send_email') }}
                    </label>
                </div>
                <RichTextEditor v-model="form.content" :placeholder="form.type === 'email' ? t('crm.email_content') : t('crm.interaction_placeholder')" />
                <div v-if="form.errors.content" class="text-sm text-rose-600">{{ form.errors.content }}</div>
                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        @click="showForm = false; form.reset()"
                        class="rounded-xl px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700"
                    >
                        {{ t('cancel') }}
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-xl bg-primary-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-primary-600 disabled:opacity-50"
                    >
                        {{ form.type === 'email' && form.send_email ? t('crm.send_email') : t('save') }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Timeline -->
        <div v-if="items.length > 0" class="flow-root">
            <ul class="-mb-8">
                <li v-for="(interaction, index) in items" :key="interaction.id" class="relative pb-8">
                    <span
                        v-if="index < items.length - 1"
                        class="absolute left-5 top-5 -ml-px h-full w-0.5 bg-slate-200 dark:bg-slate-700"
                    ></span>
                    <div class="relative flex items-start space-x-3">
                        <div :class="typeColors[interaction.type] || typeColors.other" class="flex h-10 w-10 items-center justify-center rounded-xl">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="typeIcons[interaction.type] || typeIcons.other" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <!-- Edit mode -->
                            <div v-if="editingId === interaction.id" class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                                <div class="flex gap-2 mb-2">
                                    <select
                                        v-model="editForm.type"
                                        class="rounded-lg border-0 py-1 text-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600"
                                    >
                                        <option v-for="type in interactionTypes" :key="type" :value="type">
                                            {{ t(`crm.type_${type}`) }}
                                        </option>
                                    </select>
                                    <input
                                        v-model="editForm.contacted_at"
                                        type="datetime-local"
                                        class="rounded-lg border-0 py-1 text-xs ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600"
                                    />
                                </div>
                                <RichTextEditor v-model="editForm.content" />
                                <div class="mt-2 flex justify-end gap-2">
                                    <button @click="cancelEdit" class="rounded-lg px-2 py-1 text-xs text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">
                                        {{ t('cancel') }}
                                    </button>
                                    <button @click="saveEdit(interaction)" class="rounded-lg bg-primary-500 px-2 py-1 text-xs text-white hover:bg-primary-600">
                                        {{ t('save') }}
                                    </button>
                                </div>
                            </div>
                            <!-- View mode -->
                            <div v-else>
                                <div class="flex items-center justify-between">
                                    <div class="text-sm">
                                        <span class="font-medium text-slate-900 dark:text-white">{{ t(`crm.type_${interaction.type}`) }}</span>
                                        <span class="ml-2 text-slate-500 dark:text-slate-400">{{ formatDate(interaction.contacted_at) }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button
                                            @click="startEdit(interaction)"
                                            class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-primary-600 dark:hover:bg-slate-700"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="deleteInteraction(interaction)"
                                            class="rounded-lg p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/20"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.519.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="mt-1 text-sm">
                                    <div
                                        :ref="(el) => setContentRef(interaction.id, el)"
                                        :class="[
                                            'overflow-hidden transition-all duration-200',
                                            isExpanded(interaction.id) ? 'max-h-none' : 'max-h-[4.5em]'
                                        ]"
                                    >
                                        <RichTextDisplay :content="interaction.content" />
                                    </div>
                                    <button
                                        v-if="isOverflowing(interaction.id) || isExpanded(interaction.id)"
                                        @click="toggleExpand(interaction.id)"
                                        class="mt-1 text-xs font-medium text-primary-500 hover:text-primary-600 dark:text-primary-400 dark:hover:text-primary-300"
                                    >
                                        {{ isExpanded(interaction.id) ? t('crm.see_less') : t('crm.see_more') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Empty state -->
        <div v-else class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ t('crm.no_interactions') }}</p>
        </div>
    </div>
</template>
