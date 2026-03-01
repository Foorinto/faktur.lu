<script setup>
import EmployeePortalLayout from '@/Layouts/EmployeePortalLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    employee: { type: Object, required: true },
});

const form = useForm({
    email_perso: props.employee.email_perso || '',
    phone: props.employee.phone || '',
    phone_perso: props.employee.phone_perso || '',
    address: props.employee.address || '',
    city: props.employee.city || '',
    postal_code: props.employee.postal_code || '',
    country: props.employee.country || '',
    emergency_contact: props.employee.emergency_contact || { name: '', phone: '', relationship: '' },
    bank_iban: props.employee.bank_iban || '',
});

const submitProfile = () => {
    form.put(route('employee-portal.profile.update'));
};
</script>

<template>
    <Head :title="t('employee_portal.my_profile')" />

    <EmployeePortalLayout>
        <div class="mb-6">
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ t('employee_portal.my_profile') }}</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ employee.first_name }} {{ employee.last_name }}
                <span v-if="employee.department"> — {{ employee.department.name }}</span>
            </p>
        </div>

        <!-- Read-only info -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800 mb-6">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white mb-4">{{ t('hr.general_info') }}</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 text-sm">
                <div>
                    <p class="text-slate-500 dark:text-slate-400">{{ t('hr.email_pro') }}</p>
                    <p class="font-medium text-slate-900 dark:text-white">{{ employee.email_pro || '—' }}</p>
                </div>
                <div>
                    <p class="text-slate-500 dark:text-slate-400">{{ t('hr.job_title') }}</p>
                    <p class="font-medium text-slate-900 dark:text-white">{{ employee.job_title || '—' }}</p>
                </div>
                <div>
                    <p class="text-slate-500 dark:text-slate-400">{{ t('hr.hire_date') }}</p>
                    <p class="font-medium text-slate-900 dark:text-white">{{ employee.hire_date ? new Date(employee.hire_date).toLocaleDateString('fr-FR') : '—' }}</p>
                </div>
            </div>
        </div>

        <!-- Editable form -->
        <form @submit.prevent="submitProfile" class="space-y-6">
            <!-- Personal Info -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white mb-4">{{ t('employee_portal.personal_info') }}</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('hr.email_perso') }}</label>
                        <input v-model="form.email_perso" type="email" class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" />
                        <p v-if="form.errors.email_perso" class="mt-1 text-xs text-rose-600">{{ form.errors.email_perso }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('hr.phone') }}</label>
                        <input v-model="form.phone" type="text" class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" />
                        <p v-if="form.errors.phone" class="mt-1 text-xs text-rose-600">{{ form.errors.phone }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('hr.phone_perso') }}</label>
                        <input v-model="form.phone_perso" type="text" class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" />
                        <p v-if="form.errors.phone_perso" class="mt-1 text-xs text-rose-600">{{ form.errors.phone_perso }}</p>
                    </div>
                </div>
            </div>

            <!-- Address -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white mb-4">{{ t('hr.address') }}</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('hr.address') }}</label>
                        <input v-model="form.address" type="text" class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" />
                        <p v-if="form.errors.address" class="mt-1 text-xs text-rose-600">{{ form.errors.address }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('hr.city') }}</label>
                        <input v-model="form.city" type="text" class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('hr.postal_code') }}</label>
                        <input v-model="form.postal_code" type="text" class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('hr.country') }}</label>
                        <input v-model="form.country" type="text" class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" />
                    </div>
                </div>
            </div>

            <!-- Emergency Contact -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white mb-4">{{ t('employee_portal.emergency_info') }}</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('hr.emergency_name') }}</label>
                        <input v-model="form.emergency_contact.name" type="text" class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('hr.emergency_phone') }}</label>
                        <input v-model="form.emergency_contact.phone" type="text" class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('hr.emergency_relationship') }}</label>
                        <input v-model="form.emergency_contact.relationship" type="text" class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" />
                    </div>
                </div>
            </div>

            <!-- Banking -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white mb-4">{{ t('employee_portal.banking_info') }}</h2>
                <div class="max-w-md">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">IBAN</label>
                    <input v-model="form.bank_iban" type="text" class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white font-mono text-sm" />
                    <p v-if="form.errors.bank_iban" class="mt-1 text-xs text-rose-600">{{ form.errors.bank_iban }}</p>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex justify-end">
                <button type="submit" :disabled="form.processing" class="rounded-xl bg-primary-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-600 disabled:opacity-50">
                    {{ t('save') }}
                </button>
            </div>
        </form>
    </EmployeePortalLayout>
</template>
