<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    show: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const query = ref('');
const results = ref([]);
const loading = ref(false);
const highlightedIndex = ref(-1);
const searchInput = ref(null);
let debounceTimer = null;

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

// Flat list of all results for keyboard navigation
const flatResults = computed(() => {
    const flat = [];
    for (const cat of results.value) {
        for (const item of cat.items) {
            flat.push(item);
        }
    }
    return flat;
});

const close = () => {
    emit('close');
    query.value = '';
    results.value = [];
    highlightedIndex.value = -1;
};

const performSearch = () => {
    if (query.value.length < 2) {
        results.value = [];
        loading.value = false;
        return;
    }

    loading.value = true;

    fetch(route('search.api') + '?' + new URLSearchParams({ q: query.value }), {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
        .then(res => res.json())
        .then(data => {
            results.value = data.categories || [];
            highlightedIndex.value = -1;
        })
        .catch(() => {
            results.value = [];
        })
        .finally(() => {
            loading.value = false;
        });
};

const onInput = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(performSearch, 300);
};

const navigateTo = (url) => {
    close();
    router.visit(url);
};

const seeAll = (categoryKey) => {
    const q = query.value;
    close();
    router.visit(route('search.results', { q, category: categoryKey }));
};

const seeAllResults = () => {
    const q = query.value;
    close();
    router.visit(route('search.results', { q }));
};

const onKeydown = (e) => {
    const total = flatResults.value.length;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        highlightedIndex.value = (highlightedIndex.value + 1) % total;
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        highlightedIndex.value = (highlightedIndex.value - 1 + total) % total;
    } else if (e.key === 'Enter' && highlightedIndex.value >= 0) {
        e.preventDefault();
        const item = flatResults.value[highlightedIndex.value];
        if (item) {
            navigateTo(item.url);
        }
    } else if (e.key === 'Enter' && highlightedIndex.value === -1 && query.value.length >= 2) {
        e.preventDefault();
        seeAllResults();
    }
};

// Global Cmd+K / Ctrl+K listener
const onGlobalKeydown = (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        if (props.show) {
            close();
        } else {
            emit('open');
        }
    }
};

onMounted(() => {
    document.addEventListener('keydown', onGlobalKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', onGlobalKeydown);
    clearTimeout(debounceTimer);
});

watch(() => props.show, async (val) => {
    if (val) {
        await nextTick();
        searchInput.value?.focus();
    }
});

// Helper to get the flat index of an item
const getFlatIndex = (catIndex, itemIndex) => {
    let idx = 0;
    for (let c = 0; c < catIndex; c++) {
        idx += results.value[c].items.length;
    }
    return idx + itemIndex;
};
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="close">
        <!-- Search input -->
        <div class="border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center px-4">
                <svg class="h-5 w-5 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input
                    ref="searchInput"
                    v-model="query"
                    type="text"
                    :placeholder="t('search_placeholder')"
                    class="w-full border-0 bg-transparent py-4 pl-3 pr-4 text-sm text-slate-900 placeholder-slate-400 focus:ring-0 dark:text-white dark:placeholder-slate-500"
                    @input="onInput"
                    @keydown="onKeydown"
                />
                <div v-if="loading" class="flex-shrink-0">
                    <svg class="animate-spin h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Results -->
        <div class="max-h-96 overflow-y-auto">
            <!-- Min chars hint -->
            <div v-if="query.length > 0 && query.length < 2" class="px-4 py-8 text-center text-sm text-slate-400 dark:text-slate-500">
                {{ t('search_min_chars') }}
            </div>

            <!-- No results -->
            <div v-else-if="query.length >= 2 && !loading && results.length === 0" class="px-4 py-8 text-center text-sm text-slate-400 dark:text-slate-500">
                {{ t('search_no_results', { query: query }) }}
            </div>

            <!-- Results list -->
            <div v-else-if="results.length > 0" class="py-2">
                <div v-for="(category, catIndex) in results" :key="category.key" class="mb-1">
                    <!-- Category header -->
                    <div class="flex items-center justify-between px-4 py-1.5">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            {{ t('search_category_' + category.key) }}
                            <span class="ml-1 text-slate-300 dark:text-slate-600">({{ category.count }})</span>
                        </span>
                        <button
                            v-if="category.count > 5"
                            class="text-xs text-primary-500 hover:text-primary-600 dark:text-primary-400 dark:hover:text-primary-300 transition-colors"
                            @click="seeAll(category.key)"
                        >
                            {{ t('search_see_all') }}
                        </button>
                    </div>

                    <!-- Items -->
                    <button
                        v-for="(item, itemIndex) in category.items"
                        :key="item.id"
                        class="flex w-full items-center gap-3 px-4 py-2 text-left transition-colors"
                        :class="highlightedIndex === getFlatIndex(catIndex, itemIndex)
                            ? 'bg-primary-50 dark:bg-primary-900/20'
                            : 'hover:bg-gray-50 dark:hover:bg-gray-800/50'"
                        @click="navigateTo(item.url)"
                        @mouseenter="highlightedIndex = getFlatIndex(catIndex, itemIndex)"
                    >
                        <!-- Icon -->
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800">
                            <svg class="h-4 w-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="categoryIcons[category.key]" />
                            </svg>
                        </div>

                        <!-- Content -->
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ item.title }}</div>
                            <div v-if="item.subtitle" class="truncate text-xs text-slate-500 dark:text-slate-400">{{ item.subtitle }}</div>
                        </div>

                        <!-- Status badge -->
                        <span
                            v-if="item.status"
                            class="flex-shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium"
                            :class="statusColorClasses[item.status_color] || statusColorClasses.gray"
                        >
                            {{ item.status }}
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer shortcuts -->
        <div v-if="query.length >= 2 || results.length > 0" class="border-t border-gray-200 dark:border-gray-700 px-4 py-2.5 flex items-center justify-between">
            <div class="flex items-center gap-3 text-xs text-slate-400 dark:text-slate-500">
                <span>
                    <kbd class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[10px] dark:bg-gray-700">↑↓</kbd>
                    {{ t('search_navigate') }}
                </span>
                <span>
                    <kbd class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[10px] dark:bg-gray-700">↵</kbd>
                    {{ t('search_open') }}
                </span>
                <span>
                    <kbd class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[10px] dark:bg-gray-700">esc</kbd>
                    {{ t('search_close') }}
                </span>
            </div>
            <button
                v-if="query.length >= 2"
                class="text-xs text-primary-500 hover:text-primary-600 dark:text-primary-400 transition-colors"
                @click="seeAllResults"
            >
                {{ t('search_see_all') }}
            </button>
        </div>
    </Modal>
</template>
