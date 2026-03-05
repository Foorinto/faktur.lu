<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useTranslations } from '@/Composables/useTranslations';
import debounce from 'lodash/debounce';

const { t } = useTranslations();

const props = defineProps({
    results: Object,
    filters: Object,
    categoryCounts: Object,
    totalResults: Number,
});

const search = ref(props.filters.q || '');
const activeCategory = ref(props.filters.category || null);

const categoryIcons = {
    clients: 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
    invoices: 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
    quotes: 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z',
    projects: 'M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z',
    tasks: 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    expenses: 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z',
    time_entries: 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
    employees: 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z',
};

const statusColorClasses = {
    green: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    blue: 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
    gray: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
    red: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    yellow: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    purple: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
};

const totalCount = computed(() => {
    return Object.values(props.categoryCounts).reduce((sum, c) => sum + c, 0);
});

const categories = computed(() => {
    return Object.entries(props.categoryCounts)
        .filter(([, count]) => count > 0)
        .map(([key, count]) => ({ key, count }));
});

const showPagination = computed(() => {
    return props.results?.links && props.results.links.length > 3;
});

const updateFilters = debounce(() => {
    if (search.value.length < 2) return;

    router.get(route('search.results'), {
        q: search.value,
        category: activeCategory.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

const setCategory = (category) => {
    activeCategory.value = category;
    router.get(route('search.results'), {
        q: search.value,
        category: category || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

watch(search, updateFilters);
</script>

<template>
    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200 truncate">
                {{ t('search_results_title') }}
            </h2>
        </template>

        <div class="space-y-6">
            <!-- Search bar -->
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </div>
                <input
                    v-model="search"
                    type="text"
                    :placeholder="t('search_placeholder')"
                    class="w-full rounded-xl border border-gray-200 bg-white py-3 pl-12 pr-4 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-surface-card dark:text-white dark:focus:border-primary-500"
                />
            </div>

            <!-- Results header -->
            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    {{ t('search_results_for', { query: filters.q }) }}
                    <span class="font-medium text-slate-700 dark:text-slate-300">— {{ totalResults }} {{ totalResults > 1 ? t('search_category_all').toLowerCase() : '' }}</span>
                </p>
            </div>

            <!-- Category tabs -->
            <div class="flex flex-wrap gap-2">
                <button
                    :class="[
                        'rounded-lg px-3 py-1.5 text-sm font-medium transition-colors',
                        !activeCategory
                            ? 'bg-primary-500 text-white'
                            : 'bg-gray-100 text-slate-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-slate-400 dark:hover:bg-gray-700'
                    ]"
                    @click="setCategory(null)"
                >
                    {{ t('search_category_all') }}
                    <span class="ml-1 text-xs opacity-75">({{ totalCount }})</span>
                </button>
                <button
                    v-for="cat in categories"
                    :key="cat.key"
                    :class="[
                        'rounded-lg px-3 py-1.5 text-sm font-medium transition-colors',
                        activeCategory === cat.key
                            ? 'bg-primary-500 text-white'
                            : 'bg-gray-100 text-slate-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-slate-400 dark:hover:bg-gray-700'
                    ]"
                    @click="setCategory(cat.key)"
                >
                    {{ t('search_category_' + cat.key) }}
                    <span class="ml-1 text-xs opacity-75">({{ cat.count }})</span>
                </button>
            </div>

            <!-- Results list -->
            <div class="space-y-2">
                <template v-if="results?.data?.length > 0">
                    <Link
                        v-for="item in results.data"
                        :key="item.type + '-' + item.id"
                        :href="item.url"
                        class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition-colors hover:border-primary-300 hover:bg-primary-50/50 dark:border-gray-700 dark:bg-surface-card dark:hover:border-primary-700 dark:hover:bg-primary-900/10"
                    >
                        <!-- Icon -->
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800">
                            <svg class="h-5 w-5 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="categoryIcons[item.category || item.type]" />
                            </svg>
                        </div>

                        <!-- Content -->
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ item.title }}</span>
                                <span v-if="item.category || item.type" class="flex-shrink-0 rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500 dark:bg-gray-700 dark:text-slate-400">
                                    {{ t('search_category_' + (item.category || item.type)) }}
                                </span>
                            </div>
                            <div v-if="item.subtitle" class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">{{ item.subtitle }}</div>
                        </div>

                        <!-- Status -->
                        <span
                            v-if="item.status"
                            class="flex-shrink-0 rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="statusColorClasses[item.status_color] || statusColorClasses.gray"
                        >
                            {{ item.status }}
                        </span>
                    </Link>
                </template>

                <!-- Empty state -->
                <div v-else class="rounded-xl border border-gray-200 bg-white py-12 text-center dark:border-gray-700 dark:bg-surface-card">
                    <svg class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                        {{ t('search_no_results', { query: filters.q }) }}
                    </p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="showPagination" class="flex items-center justify-between">
                <div class="text-sm text-slate-600 dark:text-slate-400">
                    {{ results.from }}–{{ results.to }} / {{ results.total }}
                </div>
                <nav class="flex">
                    <template v-for="(link, index) in results.links" :key="index">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                link.active
                                    ? 'z-10 bg-primary-500 text-white'
                                    : 'text-slate-700 bg-white ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:text-slate-300 dark:bg-surface-card dark:ring-gray-700 dark:hover:bg-gray-800',
                                'relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20 transition-colors',
                                index === 0 ? 'rounded-l-xl' : '',
                                index === results.links.length - 1 ? 'rounded-r-xl' : '',
                            ]"
                            v-html="link.label"
                            preserve-scroll
                        />
                        <span
                            v-else
                            :class="[
                                'relative inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-300 dark:text-slate-600',
                                index === 0 ? 'rounded-l-xl' : '',
                                index === results.links.length - 1 ? 'rounded-r-xl' : '',
                            ]"
                            v-html="link.label"
                        />
                    </template>
                </nav>
            </div>
        </div>
    </AppLayout>
</template>
