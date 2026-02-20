<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    countryCode: {
        type: String,
        default: 'LU',
    },
    isB2B: {
        type: Boolean,
        default: true,
    },
    placeholder: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue', 'company-selected']);

const query = ref(props.modelValue);
const results = ref([]);
const isLoading = ref(false);
const showDropdown = ref(false);
const highlightedIndex = ref(-1);
const inputRef = ref(null);
const wrapperRef = ref(null);
let debounceTimer = null;

const supportsNameSearch = computed(() => props.countryCode === 'FR');

// Sync external model changes
watch(() => props.modelValue, (val) => {
    if (val !== query.value) {
        query.value = val;
    }
});

// Search on query change
watch(query, (val) => {
    emit('update:modelValue', val);

    if (!supportsNameSearch.value || !props.isB2B || val.length < 2) {
        results.value = [];
        showDropdown.value = false;
        return;
    }

    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => searchCompanies(val), 300);
});

// Reset on country change
watch(() => props.countryCode, () => {
    results.value = [];
    showDropdown.value = false;
});

async function searchCompanies(searchQuery) {
    if (searchQuery.length < 2) return;

    isLoading.value = true;
    highlightedIndex.value = -1;

    try {
        const params = new URLSearchParams({
            query: searchQuery,
            country_code: props.countryCode,
        });

        const response = await fetch(route('company-lookup.search') + '?' + params, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            const data = await response.json();
            results.value = data.results || [];
            showDropdown.value = results.value.length > 0;
        }
    } catch (error) {
        results.value = [];
    } finally {
        isLoading.value = false;
    }
}

function selectCompany(company) {
    query.value = company.name;
    showDropdown.value = false;
    results.value = [];
    emit('update:modelValue', company.name);
    emit('company-selected', company);
}

function onKeydown(event) {
    if (!showDropdown.value) return;

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        highlightedIndex.value = Math.min(highlightedIndex.value + 1, results.value.length - 1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0);
    } else if (event.key === 'Enter' && highlightedIndex.value >= 0) {
        event.preventDefault();
        selectCompany(results.value[highlightedIndex.value]);
    } else if (event.key === 'Escape') {
        showDropdown.value = false;
    }
}

function onClickOutside(event) {
    if (wrapperRef.value && !wrapperRef.value.contains(event.target)) {
        showDropdown.value = false;
    }
}

onMounted(() => document.addEventListener('click', onClickOutside));
onUnmounted(() => {
    document.removeEventListener('click', onClickOutside);
    clearTimeout(debounceTimer);
});
</script>

<template>
    <div ref="wrapperRef" class="relative">
        <div class="relative">
            <input
                ref="inputRef"
                v-model="query"
                type="text"
                class="rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white dark:focus:border-primary-500 dark:focus:ring-primary-500 placeholder-slate-400 block w-full"
                :class="{ 'pr-8': isLoading }"
                :placeholder="placeholder"
                @keydown="onKeydown"
                @focus="showDropdown = results.length > 0"
                required
                autocomplete="off"
            />
            <!-- Loading spinner -->
            <div v-if="isLoading" class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <svg class="animate-spin h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
            </div>
            <!-- Search icon for FR -->
            <div v-else-if="supportsNameSearch && isB2B" class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>
        </div>

        <!-- Hint for name search -->
        <p v-if="supportsNameSearch && isB2B && !showDropdown && !isLoading && query.length < 2"
           class="mt-1 text-xs text-slate-400 dark:text-slate-500">
            {{ t('company_search_fr_hint') }}
        </p>

        <!-- Hint for VAT-only countries -->
        <p v-if="!supportsNameSearch && isB2B && ['BE', 'LU', 'DE', 'NL', 'AT', 'IT', 'ES'].includes(countryCode)"
           class="mt-1 text-xs text-slate-400 dark:text-slate-500">
            {{ t('company_search_vat_hint') }}
        </p>

        <!-- Results dropdown -->
        <div
            v-if="showDropdown"
            class="absolute z-50 mt-1 w-full rounded-xl bg-white shadow-xl shadow-slate-200/50 ring-1 ring-slate-200 dark:bg-slate-800 dark:ring-slate-700 dark:shadow-slate-900/50 max-h-64 overflow-y-auto"
        >
            <ul class="py-1">
                <li
                    v-for="(company, index) in results"
                    :key="index"
                    class="px-4 py-3 cursor-pointer transition-colors"
                    :class="[
                        index === highlightedIndex
                            ? 'bg-primary-50 dark:bg-primary-900/20'
                            : 'hover:bg-slate-50 dark:hover:bg-slate-700/50'
                    ]"
                    @click="selectCompany(company)"
                    @mouseenter="highlightedIndex = index"
                >
                    <div class="font-medium text-sm text-slate-900 dark:text-white">
                        {{ company.name }}
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-2">
                        <span v-if="company.postal_code || company.city">
                            {{ [company.postal_code, company.city].filter(Boolean).join(' ') }}
                        </span>
                        <span v-if="company.vat_number" class="font-mono">
                            {{ company.vat_number }}
                        </span>
                        <span v-if="company.registration_number" class="font-mono text-slate-400">
                            {{ company.registration_number }}
                        </span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</template>
