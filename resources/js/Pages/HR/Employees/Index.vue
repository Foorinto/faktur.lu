<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HRNav from "@/Components/HRNav.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, watch, computed, onMounted } from "vue";
import debounce from "lodash/debounce";
import { useTranslations } from "@/Composables/useTranslations";
import { useAvatarColor } from "@/Composables/useAvatarColor";
import { useTour } from "@/Composables/useTour";

const { t } = useTranslations();
const { getAvatarClasses } = useAvatarColor();
const { startTour } = useTour();

onMounted(() => setTimeout(() => startTour("hrEmployees"), 600));

const props = defineProps({
    employees: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusCounts: { type: Object, default: () => ({}) },
    departments: { type: Array, default: () => [] },
    contractTypes: { type: Array, required: true },
    statuses: { type: Array, required: true },
});

const search = ref(props.filters.search || "");
const departmentFilter = ref(props.filters.department || "");
const contractTypeFilter = ref(props.filters.contract_type || "");
const statusFilter = ref(props.filters.status || "");

const totalCount = computed(() => {
    return Object.values(props.statusCounts).reduce((sum, c) => sum + c, 0);
});

const statusTabs = computed(() => {
    return [
        { value: "", label: t("hr.all"), count: totalCount.value },
        {
            value: "active",
            label: t("hr.status_active"),
            count: props.statusCounts.active || 0,
        },
        {
            value: "long_leave",
            label: t("hr.status_long_leave"),
            count: props.statusCounts.long_leave || 0,
        },
        {
            value: "terminated",
            label: t("hr.status_terminated"),
            count: props.statusCounts.terminated || 0,
        },
    ];
});

const updateFilters = debounce(() => {
    router.get(
        route("hr.employees.index"),
        {
            search: search.value || undefined,
            department: departmentFilter.value || undefined,
            contract_type: contractTypeFilter.value || undefined,
            status: statusFilter.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}, 300);

watch([search, departmentFilter, contractTypeFilter], updateFilters);

const setStatus = (status) => {
    statusFilter.value = status;
    router.get(
        route("hr.employees.index"),
        {
            search: search.value || undefined,
            department: departmentFilter.value || undefined,
            contract_type: contractTypeFilter.value || undefined,
            status: status || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const deleteEmployee = (employee) => {
    if (
        confirm(t("hr.confirm_delete_employee", { name: employee.full_name }))
    ) {
        router.delete(route("hr.employees.destroy", employee.id));
    }
};

const getStatusBadgeClass = (status) => {
    const classes = {
        active: "bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400",
        long_leave:
            "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400",
        terminated:
            "bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-slate-300",
    };
    return classes[status] || classes.active;
};

const getContractBadgeClass = (type) => {
    const classes = {
        CDI: "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400",
        CDD: "bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400",
        Stage: "bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400",
        Freelance:
            "bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400",
    };
    return classes[type] || classes.CDI;
};
</script>

<template>
    <Head :title="t('hr.employees')" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t("hr.employees") }}
            </h1>
        </template>
        <template #header-actions>
            <Link
                data-tour="hr-employees-new"
                :href="route('hr.employees.create')"
                class="inline-flex items-center rounded-xl bg-accent-rose px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-pink-500"
            >
                <svg
                    class="-ml-0.5 mr-1.5 h-5 w-5"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path
                        d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z"
                    />
                </svg>
                {{ t("hr.new_employee") }}
            </Link>
        </template>

        <HRNav class="mb-6" />

        <!-- Status tabs -->
        <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
            <nav
                class="flex space-x-2 sm:space-x-4 overflow-x-auto"
                aria-label="Status tabs"
            >
                <button
                    v-for="tab in statusTabs"
                    :key="tab.value"
                    @click="setStatus(tab.value)"
                    :class="[
                        'whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors',
                        statusFilter === tab.value
                            ? 'border-accent-rose text-accent-rose dark:text-pink-400'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-gray-300 dark:text-slate-400 dark:hover:text-slate-300',
                    ]"
                >
                    {{ tab.label }}
                    <span
                        v-if="tab.count > 0"
                        :class="[
                            'ml-1.5 rounded-full px-2 py-0.5 text-xs',
                            statusFilter === tab.value
                                ? 'bg-pink-100 text-accent-rose dark:bg-pink-900/30 dark:text-pink-300'
                                : 'bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-slate-400',
                        ]"
                    >
                        {{ tab.count }}
                    </span>
                </button>
            </nav>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center">
            <div class="relative flex-1 max-w-md">
                <div
                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                >
                    <svg
                        class="h-5 w-5 text-slate-400"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>
                <input
                    v-model="search"
                    type="text"
                    :placeholder="t('hr.search_employee')"
                    class="block w-full rounded-xl border-0 py-1.5 pl-10 pr-3 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-surface-card dark:text-white dark:ring-slate-600 dark:placeholder:text-slate-500 sm:text-sm sm:leading-6"
                />
            </div>

            <select
                v-model="departmentFilter"
                class="rounded-xl border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-primary-600 dark:bg-surface-card dark:text-white dark:ring-slate-600 sm:text-sm"
            >
                <option value="">{{ t("hr.all_departments") }}</option>
                <option
                    v-for="dept in departments"
                    :key="dept.id"
                    :value="dept.id"
                >
                    {{ dept.name }}
                </option>
            </select>

            <select
                v-model="contractTypeFilter"
                class="rounded-xl border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-primary-600 dark:bg-surface-card dark:text-white dark:ring-slate-600 sm:text-sm"
            >
                <option value="">{{ t("hr.all_contract_types") }}</option>
                <option v-for="ct in contractTypes" :key="ct" :value="ct">
                    {{ t("hr.contract_" + ct.toLowerCase()) }}
                </option>
            </select>
        </div>

        <!-- Employees list -->
        <div
            data-tour="hr-employees-list"
            class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-surface-card"
        >
            <table
                class="min-w-full divide-y divide-slate-200 dark:divide-slate-700"
            >
                <thead class="bg-slate-50 dark:bg-gray-800">
                    <tr>
                        <th
                            scope="col"
                            class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 dark:text-white sm:pl-6"
                        >
                            {{ t("hr.employee") }}
                        </th>
                        <th
                            scope="col"
                            class="hidden px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white sm:table-cell"
                        >
                            {{ t("hr.status") }}
                        </th>
                        <th
                            scope="col"
                            class="hidden px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white md:table-cell"
                        >
                            {{ t("hr.department") }}
                        </th>
                        <th
                            scope="col"
                            class="hidden px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white lg:table-cell"
                        >
                            {{ t("hr.contract_type") }}
                        </th>
                        <th
                            scope="col"
                            class="hidden px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white xl:table-cell"
                        >
                            {{ t("hr.job_title") }}
                        </th>
                        <th
                            scope="col"
                            class="relative py-3.5 pl-3 pr-4 sm:pr-6"
                        >
                            <span class="sr-only">{{ t("actions") }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody
                    class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-surface-card"
                >
                    <tr v-if="employees.data.length === 0">
                        <td
                            colspan="6"
                            class="py-10 text-center text-sm text-slate-500 dark:text-slate-400"
                        >
                            <svg
                                class="mx-auto h-12 w-12 text-slate-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                            <p class="mt-2">{{ t("hr.no_employees") }}</p>
                            <Link
                                :href="route('hr.employees.create')"
                                class="mt-4 inline-flex items-center text-primary-600 hover:text-primary-500 dark:text-primary-400"
                            >
                                <svg
                                    class="mr-1 h-5 w-5"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z"
                                    />
                                </svg>
                                {{ t("hr.add_first_employee") }}
                            </Link>
                        </td>
                    </tr>
                    <tr
                        v-for="employee in employees.data"
                        :key="employee.id"
                        class="hover:bg-gray-50 dark:hover:bg-gray-800"
                    >
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-6">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0">
                                    <img
                                        v-if="employee.photo_path"
                                        :src="`/storage/${employee.photo_path}`"
                                        :alt="employee.full_name"
                                        class="h-10 w-10 rounded-xl object-cover"
                                    />
                                    <div
                                        v-else
                                        :class="[
                                            'flex h-10 w-10 items-center justify-center rounded-xl',
                                            getAvatarClasses(
                                                employee.first_name +
                                                    ' ' +
                                                    employee.last_name,
                                            ),
                                        ]"
                                    >
                                        <span class="text-sm font-bold">
                                            {{ employee.first_name.charAt(0)
                                            }}{{ employee.last_name.charAt(0) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <Link
                                        :href="
                                            route(
                                                'hr.employees.show',
                                                employee.id,
                                            )
                                        "
                                        class="font-medium text-slate-900 hover:text-primary-600 dark:text-white dark:hover:text-primary-400"
                                    >
                                        {{ employee.full_name }}
                                    </Link>
                                    <div
                                        class="text-sm text-slate-500 dark:text-slate-400 lg:hidden"
                                    >
                                        {{ employee.email_pro }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td
                            class="hidden whitespace-nowrap px-3 py-4 text-sm sm:table-cell"
                        >
                            <span
                                :class="getStatusBadgeClass(employee.status)"
                                class="inline-flex items-center rounded-xl px-2.5 py-0.5 text-xs font-medium"
                            >
                                {{ t("hr.status_" + employee.status) }}
                            </span>
                        </td>
                        <td
                            class="hidden whitespace-nowrap px-3 py-4 text-sm md:table-cell"
                        >
                            <span
                                v-if="employee.department"
                                class="inline-flex items-center gap-1.5"
                            >
                                <span
                                    v-if="employee.department.color"
                                    class="h-2 w-2 rounded-full"
                                    :style="{
                                        backgroundColor:
                                            employee.department.color,
                                    }"
                                ></span>
                                <span
                                    class="text-slate-700 dark:text-slate-300"
                                    >{{ employee.department.name }}</span
                                >
                            </span>
                            <span
                                v-else
                                class="text-slate-400 dark:text-slate-500"
                                >—</span
                            >
                        </td>
                        <td
                            class="hidden whitespace-nowrap px-3 py-4 text-sm lg:table-cell"
                        >
                            <span
                                :class="
                                    getContractBadgeClass(
                                        employee.contract_type,
                                    )
                                "
                                class="inline-flex items-center rounded-xl px-2.5 py-0.5 text-xs font-medium"
                            >
                                {{ employee.contract_type }}
                            </span>
                        </td>
                        <td
                            class="hidden whitespace-nowrap px-3 py-4 text-sm text-slate-500 dark:text-slate-400 xl:table-cell"
                        >
                            {{ employee.job_title || "—" }}
                        </td>
                        <td
                            class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6"
                        >
                            <div
                                class="flex items-center justify-end space-x-1"
                            >
                                <Link
                                    :href="
                                        route('hr.employees.edit', employee.id)
                                    "
                                    class="rounded-lg p-2 text-slate-400 hover:bg-gray-50 hover:text-primary-600 dark:hover:bg-gray-800 dark:hover:text-primary-400"
                                    :title="t('edit')"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z"
                                        />
                                    </svg>
                                </Link>
                                <button
                                    @click="deleteEmployee(employee)"
                                    class="rounded-lg p-2 text-slate-400 hover:bg-pink-50 hover:text-pink-600 dark:hover:bg-pink-900/20 dark:hover:text-pink-400"
                                    :title="t('delete')"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.519.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div
            v-if="employees.links && employees.links.length > 3"
            class="mt-6 flex items-center justify-between"
        >
            <div class="text-sm text-slate-700 dark:text-slate-400">
                {{
                    t("showing_x_to_y_of_z", {
                        from: employees.from,
                        to: employees.to,
                        count: employees.total,
                        items: t("hr.employees").toLowerCase(),
                    })
                }}
            </div>
            <nav class="isolate inline-flex -space-x-px rounded-xl shadow-sm">
                <template v-for="(link, index) in employees.links" :key="index">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            link.active
                                ? 'z-10 bg-accent-rose text-white'
                                : 'text-slate-900 ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:text-slate-300 dark:ring-slate-600 dark:hover:bg-gray-800',
                            'relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20',
                            index === 0 ? 'rounded-l-xl' : '',
                            index === employees.links.length - 1
                                ? 'rounded-r-xl'
                                : '',
                        ]"
                        v-html="link.label"
                        preserve-scroll
                    />
                    <span
                        v-else
                        :class="[
                            'relative inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-400 dark:text-slate-500',
                            index === 0 ? 'rounded-l-xl' : '',
                            index === employees.links.length - 1
                                ? 'rounded-r-xl'
                                : '',
                        ]"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </div>
    </AppLayout>
</template>
