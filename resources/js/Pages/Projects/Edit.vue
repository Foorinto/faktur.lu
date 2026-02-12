<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';
import ProjectForm from '@/Components/ProjectForm.vue';

const { t } = useTranslations();

const props = defineProps({
    project: {
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
});

const form = useForm({
    title: props.project.title,
    description: props.project.description || '',
    client_id: props.project.client_id || '',
    status: props.project.status,
    color: props.project.color || '#9b5de5',
    due_date: props.project.due_date || '',
    budget_hours: props.project.budget_hours || '',
});

const submit = () => {
    form.put(route('projects.update', props.project.id));
};
</script>

<template>
    <Head :title="t('edit_project') + ' - ' + project.title" />

    <AppLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link
                    :href="route('projects.show', project.id)"
                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-300"
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                    </svg>
                </Link>
                <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ t('edit_project') }}
                </h1>
            </div>
        </template>

        <div class="mx-auto max-w-2xl">
            <ProjectForm
                :form="form"
                :clients="clients"
                :statuses="statuses"
                :colors="colors"
                :submit-label="t('save')"
                :cancel-route="route('projects.show', project.id)"
                @submit="submit"
            />
        </div>
    </AppLayout>
</template>
