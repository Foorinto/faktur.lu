<script setup>
import { useTranslations } from '@/Composables/useTranslations';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import { Link } from '@inertiajs/vue3';

const { t } = useTranslations();

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    clients: {
        type: Array,
        required: true,
    },
    statuses: {
        type: Object,
        required: true,
    },
    colors: {
        type: Object,
        required: true,
    },
    submitLabel: {
        type: String,
        default: 'Save',
    },
    cancelRoute: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['submit']);

const submit = () => {
    emit('submit');
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <!-- Basic info -->
        <div class="rounded-2xl bg-white p-6 border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">Informations</h3>

            <div class="space-y-4">
                <div>
                    <InputLabel for="title" value="Titre *" />
                    <TextInput
                        id="title"
                        v-model="form.title"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autofocus
                    />
                    <InputError :message="form.errors.title" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="description" value="Description" />
                    <div class="mt-1">
                        <RichTextEditor v-model="form.description" />
                    </div>
                    <InputError :message="form.errors.description" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="status" value="Statut" />
                    <select
                        id="status"
                        v-model="form.status"
                        class="mt-1 block w-full rounded-xl border-0 py-2 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-800 dark:text-white dark:ring-slate-600 sm:text-sm"
                    >
                        <option v-for="(label, value) in statuses" :key="value" :value="value">
                            {{ label }}
                        </option>
                    </select>
                    <InputError :message="form.errors.status" class="mt-2" />
                </div>

                <div>
                    <InputLabel :value="t('project_color')" />
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button
                            v-for="(name, hex) in colors"
                            :key="hex"
                            type="button"
                            @click="form.color = hex"
                            :class="[
                                'h-8 w-8 rounded-full transition-all',
                                form.color === hex ? 'ring-2 ring-offset-2 ring-slate-400 dark:ring-offset-slate-800' : 'hover:scale-110'
                            ]"
                            :style="{ backgroundColor: hex }"
                            :title="name"
                        />
                    </div>
                    <InputError :message="form.errors.color" class="mt-2" />
                </div>
            </div>
        </div>

        <!-- Client & Dates -->
        <div class="rounded-2xl bg-white p-6 border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">Client & Dates</h3>

            <div class="space-y-4">
                <div>
                    <InputLabel for="client_id" value="Client" />
                    <select
                        id="client_id"
                        v-model="form.client_id"
                        class="mt-1 block w-full rounded-xl border-0 py-2 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-800 dark:text-white dark:ring-slate-600 sm:text-sm"
                    >
                        <option value="">Aucun client</option>
                        <option v-for="client in clients" :key="client.id" :value="client.id">
                            {{ client.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.client_id" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="due_date" :value="t('due_date')" />
                    <TextInput
                        id="due_date"
                        v-model="form.due_date"
                        type="date"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.due_date" class="mt-2" />
                </div>
            </div>
        </div>

        <!-- Budget -->
        <div class="rounded-2xl bg-white p-6 border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">Budget</h3>

            <div>
                <InputLabel for="budget_hours" :value="t('budget_hours')" />
                <div class="relative mt-1">
                    <TextInput
                        id="budget_hours"
                        v-model="form.budget_hours"
                        type="number"
                        step="0.5"
                        min="0"
                        class="block w-full pr-12"
                    />
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                        <span class="text-slate-500 sm:text-sm">heures</span>
                    </div>
                </div>
                <InputError :message="form.errors.budget_hours" class="mt-2" />
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-4">
            <Link
                v-if="cancelRoute"
                :href="cancelRoute"
                class="text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white"
            >
                {{ t('cancel') }}
            </Link>
            <Link
                v-else
                :href="route('projects.index')"
                class="text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white"
            >
                {{ t('cancel') }}
            </Link>
            <PrimaryButton :disabled="form.processing">
                {{ submitLabel }}
            </PrimaryButton>
        </div>
    </form>
</template>
