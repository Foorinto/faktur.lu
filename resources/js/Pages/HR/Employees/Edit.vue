<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import HRNav from '@/Components/HRNav.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';
import UnsavedChangesBar from '@/Components/UnsavedChangesBar.vue';

const { t } = useTranslations();

const props = defineProps({
    employee: { type: Object, required: true },
    departments: { type: Array, default: () => [] },
    managers: { type: Array, default: () => [] },
    contractTypes: { type: Array, required: true },
    statuses: { type: Array, required: true },
    countries: { type: Array, default: () => [] },
    nationalities: { type: Array, default: () => [] },
});

const form = useForm({
    _method: 'PUT',
    first_name: props.employee.first_name,
    last_name: props.employee.last_name,
    email_pro: props.employee.email_pro,
    email_perso: props.employee.email_perso || '',
    phone: props.employee.phone || '',
    phone_perso: props.employee.phone_perso || '',
    birth_date: props.employee.birth_date || '',
    nationality: props.employee.nationality || '',
    address: props.employee.address || '',
    city: props.employee.city || '',
    postal_code: props.employee.postal_code || '',
    country: props.employee.country || 'LU',
    contract_type: props.employee.contract_type,
    contract_start: props.employee.contract_start,
    contract_end: props.employee.contract_end || '',
    trial_end_date: props.employee.trial_end_date || '',
    job_title: props.employee.job_title || '',
    department_id: props.employee.department_id || '',
    manager_id: props.employee.manager_id || '',
    work_location: props.employee.work_location || '',
    salary_gross: props.employee.salary_gross || '',
    salary_currency: props.employee.salary_currency || 'EUR',
    benefits: props.employee.benefits || [],
    bank_iban: props.employee.bank_iban || '',
    emergency_contact: props.employee.emergency_contact || { name: '', phone: '', relationship: '' },
    status: props.employee.status,
    termination_date: props.employee.termination_date || '',
    termination_reason: props.employee.termination_reason || '',
    photo: null,
});

const photoPreview = ref(
    props.employee.photo_path ? route('files.employee-photo', props.employee.id) : null
);

const handlePhotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.photo = file;
        const reader = new FileReader();
        reader.onload = (ev) => { photoPreview.value = ev.target.result; };
        reader.readAsDataURL(file);
    }
};

const submit = () => {
    form.post(route('hr.employees.update', props.employee.id), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head :title="`${t('edit')} - ${employee.full_name}`" />

    <AppLayout>
        <template #header>
            <Link
                :href="route('hr.employees.show', employee.id)"
                class="text-slate-400 hover:text-slate-500 dark:text-slate-500 dark:hover:text-slate-400"
            >
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                </svg>
            </Link>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('edit') }} - {{ employee.full_name }}
            </h1>
        </template>

        <HRNav class="mb-6" />

        <div class="mx-auto max-w-3xl py-6 px-4 sm:px-6 lg:px-8">
            <form @submit.prevent="submit" class="space-y-8">
                <!-- Photo -->
                <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr.photo') }}</h2>
                    </div>
                    <div class="px-6 py-4 flex items-center gap-6">
                        <div class="h-20 w-20 flex-shrink-0">
                            <!-- Un chemin enregistré ne garantit pas que le fichier soit encore sur le
                                 disque. Sans cet écouteur, la condition restait vraie, l'image
                                 échouait, et le repli en silhouette n'était jamais atteint. -->
                            <img v-if="photoPreview" :src="photoPreview" alt="Photo preview" class="h-20 w-20 rounded-xl object-cover" @error="photoPreview = null" />
                            <div v-else class="flex h-20 w-20 items-center justify-center rounded-xl bg-slate-100 dark:bg-gray-800">
                                <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <label class="cursor-pointer rounded-xl bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-slate-300 dark:ring-slate-600 dark:hover:bg-gray-800">
                                {{ t('hr.upload_photo') }}
                                <input type="file" accept="image/*" class="hidden" @change="handlePhotoChange" />
                            </label>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">JPG, PNG. Max 2 Mo.</p>
                        </div>
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr.personal_info') }}</h2>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.first_name') }} *</label>
                            <input v-model="form.first_name" type="text" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            <p v-if="form.errors.first_name" class="mt-1 text-xs text-rose-600">{{ form.errors.first_name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.last_name') }} *</label>
                            <input v-model="form.last_name" type="text" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            <p v-if="form.errors.last_name" class="mt-1 text-xs text-rose-600">{{ form.errors.last_name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.birth_date') }}</label>
                            <input v-model="form.birth_date" type="date" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.nationality') }}</label>
                            <select v-model="form.nationality" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm">
                                <option value="">-</option>
                                <option v-for="n in nationalities" :key="n.code" :value="n.code">{{ n.name }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Contact -->
                <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr.contact_info') }}</h2>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.email_pro') }} *</label>
                            <input v-model="form.email_pro" type="email" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            <p v-if="form.errors.email_pro" class="mt-1 text-xs text-rose-600">{{ form.errors.email_pro }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.email_perso') }}</label>
                            <input v-model="form.email_perso" type="email" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.phone') }}</label>
                            <input v-model="form.phone" type="tel" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.phone_perso') }}</label>
                            <input v-model="form.phone_perso" type="tel" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.address') }}</label>
                            <input v-model="form.address" type="text" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.postal_code') }}</label>
                            <input v-model="form.postal_code" type="text" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.city') }}</label>
                            <input v-model="form.city" type="text" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.country') }}</label>
                            <select v-model="form.country" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm">
                                <option value="">-</option>
                                <option v-for="c in countries" :key="c.code" :value="c.code">{{ c.name }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Contract -->
                <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr.contract_details') }}</h2>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.contract_type') }} *</label>
                            <select v-model="form.contract_type" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm">
                                <option v-for="ct in contractTypes" :key="ct" :value="ct">{{ t('hr.contract_' + ct.toLowerCase()) }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.status') }}</label>
                            <select v-model="form.status" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm">
                                <option v-for="s in statuses" :key="s" :value="s">{{ t('hr.status_' + s) }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.contract_start') }} *</label>
                            <input v-model="form.contract_start" type="date" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.contract_end') }}</label>
                            <input v-model="form.contract_end" type="date" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.trial_end_date') }}</label>
                            <input v-model="form.trial_end_date" type="date" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.job_title') }}</label>
                            <input v-model="form.job_title" type="text" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.department') }}</label>
                            <select v-model="form.department_id" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm">
                                <option value="">-</option>
                                <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.manager') }}</label>
                            <select v-model="form.manager_id" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm">
                                <option value="">-</option>
                                <option v-for="mgr in managers" :key="mgr.id" :value="mgr.id">{{ mgr.last_name }} {{ mgr.first_name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.work_location') }}</label>
                            <input v-model="form.work_location" type="text" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                        </div>
                    </div>
                </div>

                <!-- Termination (visible only if terminated) -->
                <div v-if="form.status === 'terminated'" class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-rose-200 dark:bg-surface-card dark:border-rose-700 dark:shadow-gray-900/50">
                    <div class="px-6 py-4 border-b border-rose-200 dark:border-rose-700">
                        <h2 class="text-lg font-medium text-rose-700 dark:text-rose-400">{{ t('hr.termination') }}</h2>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.termination_date') }}</label>
                            <input v-model="form.termination_date" type="date" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.termination_reason') }}</label>
                            <textarea v-model="form.termination_reason" rows="3" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Salary & Banking -->
                <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr.salary_banking') }}</h2>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.salary_gross') }}</label>
                            <div class="mt-1 flex rounded-xl ring-1 ring-inset ring-gray-200 dark:ring-slate-600">
                                <input v-model="form.salary_gross" type="number" step="0.01" class="block w-full border-0 py-1.5 text-slate-900 bg-transparent focus:ring-0 dark:text-white sm:text-sm rounded-l-xl" />
                                <select v-model="form.salary_currency" class="border-0 py-1.5 text-slate-500 bg-transparent focus:ring-0 dark:text-slate-400 sm:text-sm rounded-r-xl">
                                    <option value="EUR">EUR</option>
                                    <option value="USD">USD</option>
                                    <option value="GBP">GBP</option>
                                    <option value="CHF">CHF</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.bank_iban') }}</label>
                            <input v-model="form.bank_iban" type="text" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm font-mono" />
                        </div>
                    </div>
                </div>

                <!-- Emergency Contact -->
                <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr.emergency_contact') }}</h2>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.contact_name') }}</label>
                            <input v-model="form.emergency_contact.name" type="text" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.contact_phone') }}</label>
                            <input v-model="form.emergency_contact.phone" type="tel" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.contact_relationship') }}</label>
                            <input v-model="form.emergency_contact.relationship" type="text" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Link
                        :href="route('hr.employees.show', employee.id)"
                        class="inline-flex items-center justify-center w-full sm:w-auto rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-slate-300 dark:ring-slate-600 dark:hover:bg-gray-800"
                    >
                        {{ t('cancel') }}
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full sm:w-auto justify-center rounded-xl bg-accent-rose px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-pink-500 disabled:opacity-50"
                    >
                        {{ t('save') }}
                    </button>
                </div>
                        <UnsavedChangesBar :form="form" @submit="submit" />

            </form>
        </div>
    </AppLayout>
</template>
