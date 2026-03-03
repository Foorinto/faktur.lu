<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import HRNav from '@/Components/HRNav.vue';
import OrgNode from '@/Components/HR/OrgNode.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import debounce from 'lodash/debounce';
import { useTranslations } from '@/Composables/useTranslations';
import { useAvatarColor } from '@/Composables/useAvatarColor';

const { t } = useTranslations();
const { getAvatarClasses } = useAvatarColor();

const props = defineProps({
    employees: { type: Array, required: true },
    departments: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const viewMode = ref('grid');
const search = ref(props.filters.search || '');
const departmentFilter = ref(props.filters.department || '');

const updateFilters = debounce(() => {
    router.get(route('hr.trombinoscope'), {
        search: search.value || undefined,
        department: departmentFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, departmentFilter], updateFilters);

const downloadPdf = () => {
    const params = new URLSearchParams();
    if (departmentFilter.value) params.set('department', departmentFilter.value);
    const query = params.toString();
    window.open(route('hr.trombinoscope.pdf') + (query ? '?' + query : ''), '_blank');
};

// Build org tree from flat employee list
const orgTree = computed(() => {
    const map = {};
    const roots = [];

    props.employees.forEach(emp => {
        map[emp.id] = { ...emp, children: [] };
    });

    props.employees.forEach(emp => {
        if (emp.manager_id && map[emp.manager_id]) {
            map[emp.manager_id].children.push(map[emp.id]);
        } else {
            roots.push(map[emp.id]);
        }
    });

    return roots;
});
</script>

<template>
    <Head :title="t('hr.trombinoscope')" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('hr.trombinoscope') }}
            </h1>
        </template>
        <template #header-actions>
            <button
                @click="downloadPdf"
                class="inline-flex items-center rounded-xl bg-accent-rose px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-pink-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-pink-400"
            >
                <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                {{ t('hr.download_pdf') }}
            </button>
        </template>

        <HRNav class="mb-6" />

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="relative flex-1 max-w-md">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="t('hr.search_employee')"
                            class="block w-full rounded-xl border-0 py-1.5 pl-10 pr-3 text-slate-900 ring-1 ring-inset ring-gray-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-primary-500 dark:bg-surface-card dark:text-white dark:ring-slate-600 dark:placeholder:text-slate-500 sm:text-sm sm:leading-6"
                        />
                    </div>
                    <select
                        v-if="viewMode === 'grid'"
                        v-model="departmentFilter"
                        class="rounded-xl border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-surface-card dark:text-white dark:ring-slate-600 sm:text-sm sm:leading-6"
                    >
                        <option value="">{{ t('hr.all_departments') }}</option>
                        <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                            {{ dept.name }}
                        </option>
                    </select>

                    <!-- View toggle -->
                    <div class="inline-flex rounded-xl bg-slate-100 p-1 dark:bg-gray-800 sm:ml-auto">
                        <button
                            @click="viewMode = 'grid'"
                            :class="[
                                'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors',
                                viewMode === 'grid'
                                    ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-600 dark:text-white'
                                    : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'
                            ]"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.25 2A2.25 2.25 0 002 4.25v2.5A2.25 2.25 0 004.25 9h2.5A2.25 2.25 0 009 6.75v-2.5A2.25 2.25 0 006.75 2h-2.5zm0 9A2.25 2.25 0 002 13.25v2.5A2.25 2.25 0 004.25 18h2.5A2.25 2.25 0 009 15.75v-2.5A2.25 2.25 0 006.75 11h-2.5zm9-9A2.25 2.25 0 0011 4.25v2.5A2.25 2.25 0 0013.25 9h2.5A2.25 2.25 0 0018 6.75v-2.5A2.25 2.25 0 0015.75 2h-2.5zm0 9A2.25 2.25 0 0011 13.25v2.5A2.25 2.25 0 0013.25 18h2.5A2.25 2.25 0 0018 15.75v-2.5A2.25 2.25 0 0015.75 11h-2.5z" clip-rule="evenodd" />
                            </svg>
                            {{ t('hr.view_grid') }}
                        </button>
                        <button
                            @click="viewMode = 'tree'"
                            :class="[
                                'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors',
                                viewMode === 'tree'
                                    ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-600 dark:text-white'
                                    : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'
                            ]"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 2a.75.75 0 01.75.75v1.5a.75.75 0 01-1.5 0v-1.5A.75.75 0 0110 2zm0 13a.75.75 0 01.75.75v1.5a.75.75 0 01-1.5 0v-1.5A.75.75 0 0110 15zm-6.5-5a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5h-1.5A.75.75 0 013.5 10zm13 0a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5h-1.5a.75.75 0 01-.75-.75zM7.5 6a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zm-3 5.5a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zm9-1.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm-4.5 4a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0z" />
                            </svg>
                            {{ t('hr.view_org_chart') }}
                        </button>
                    </div>
                </div>

                <!-- GRID VIEW -->
                <template v-if="viewMode === 'grid'">
                    <!-- Employee count -->
                    <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
                        {{ employees.length }} {{ t('hr.employees').toLowerCase() }}
                    </p>

                    <!-- Grid of employee cards -->
                    <div v-if="employees.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                        <div
                            v-for="employee in employees"
                            :key="employee.id"
                            class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm text-center transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-surface-card"
                        >
                            <!-- Photo or initials -->
                            <div class="mx-auto mb-3 h-20 w-20">
                                <img
                                    v-if="employee.photo_path"
                                    :src="`/storage/${employee.photo_path}`"
                                    :alt="employee.full_name"
                                    class="h-20 w-20 rounded-full object-cover ring-2 ring-slate-100 dark:ring-gray-700"
                                />
                                <div v-else :class="['flex h-20 w-20 items-center justify-center rounded-full ring-2 ring-white/50 dark:ring-gray-700/50', getAvatarClasses(employee.first_name + ' ' + employee.last_name)]">
                                    <span class="text-lg font-bold">
                                        {{ employee.first_name?.charAt(0) }}{{ employee.last_name?.charAt(0) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Name -->
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                                {{ employee.full_name }}
                            </h3>

                            <!-- Job title -->
                            <p v-if="employee.job_title" class="mt-0.5 text-xs font-medium text-primary-600 dark:text-primary-400">
                                {{ employee.job_title }}
                            </p>

                            <!-- Department -->
                            <p v-if="employee.department" class="mt-1 flex items-center justify-center gap-1 text-xs text-slate-500 dark:text-slate-400">
                                <span
                                    v-if="employee.department.color"
                                    class="inline-block h-2 w-2 rounded-full"
                                    :style="{ backgroundColor: employee.department.color }"
                                ></span>
                                {{ employee.department.name }}
                            </p>

                            <!-- Phone -->
                            <p v-if="employee.phone" class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                <a :href="`tel:${employee.phone}`" class="hover:text-primary-600 dark:hover:text-primary-400">{{ employee.phone }}</a>
                            </p>

                            <!-- Email -->
                            <p v-if="employee.email_pro" class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 truncate">
                                <a :href="`mailto:${employee.email_pro}`" class="hover:text-primary-600 dark:hover:text-primary-400">{{ employee.email_pro }}</a>
                            </p>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-else class="rounded-2xl border border-gray-200 bg-white p-12 text-center dark:border-gray-700 dark:bg-surface-card">
                        <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                        </svg>
                        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">{{ t('hr.no_employees') }}</p>
                    </div>
                </template>

                <!-- TREE VIEW (Org Chart) -->
                <template v-if="viewMode === 'tree'">
                    <div v-if="orgTree.length > 0" class="overflow-x-auto pb-8">
                        <div class="inline-flex flex-col items-center gap-8 min-w-full">
                            <div v-for="root in orgTree" :key="root.id">
                                <OrgNode :node="root" :depth="0" />
                            </div>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-else class="rounded-2xl border border-gray-200 bg-white p-12 text-center dark:border-gray-700 dark:bg-surface-card">
                        <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                        </svg>
                        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">{{ t('hr.no_employees') }}</p>
                    </div>
                </template>
            </div>
        </div>
    </AppLayout>
</template>
