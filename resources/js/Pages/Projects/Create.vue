<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductivityNav from '@/Components/ProductivityNav.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useTour } from '@/Composables/useTour';
import ProjectForm from '@/Components/ProjectForm.vue';

const { t } = useTranslations();
const { startTour } = useTour();

onMounted(() => setTimeout(() => startTour('projectCreate'), 600));

const props = defineProps({
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
    title: '',
    description: '',
    client_id: '',
    status: 'backlog',
    color: '#7c3aed',
    due_date: '',
    budget_hours: '',
});

const submit = () => {
    form.post(route('projects.store'));
};
</script>

<template>
    <Head :title="t('new_project')" />

    <AppLayout>
        <template #header>
            <Link
                :href="route('projects.index')"
                class="rounded-lg p-2 text-slate-400 hover:bg-gray-50 hover:text-slate-600 dark:hover:bg-gray-800 dark:hover:text-slate-300"
            >
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                </svg>
            </Link>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('new_project') }}
            </h1>
        </template>

        <ProductivityNav class="mb-6" />

        <div class="mx-auto max-w-2xl">
            <ProjectForm
                :form="form"
                :clients="clients"
                :statuses="statuses"
                :colors="colors"
                :submit-label="t('create')"
                @submit="submit"
            />
        </div>
    </AppLayout>
</template>
