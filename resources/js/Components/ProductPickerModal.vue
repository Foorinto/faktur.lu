<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import debounce from 'lodash/debounce';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const emit = defineEmits(['close', 'select']);

const term = ref('');
const results = ref([]);
const loading = ref(false);

const load = async (q) => {
    loading.value = true;
    try {
        const response = await fetch(route('products.search', q ? { q } : {}), {
            headers: { Accept: 'application/json' },
        });
        const data = await response.json();
        results.value = data.products || [];
    } catch (e) {
        results.value = [];
    } finally {
        loading.value = false;
    }
};

const search = debounce((q) => load(q), 250);
watch(term, (q) => search(q));

const choose = (product) => {
    emit('select', product);
    emit('close');
};

const onKey = (e) => {
    if (e.key === 'Escape') {
        emit('close');
    }
};

onMounted(() => {
    load('');
    window.addEventListener('keydown', onKey);
});
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-50 flex items-start justify-center bg-black/40 p-4 pt-16 sm:pt-24"
            @click.self="emit('close')"
        >
            <div class="flex max-h-[75vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-gray-900">
                <!-- Search -->
                <div class="border-b border-gray-100 p-4 dark:border-gray-800">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">{{ t('products.title') }}</h2>
                        <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" @click="emit('close')">✕</button>
                    </div>
                    <input
                        v-model="term"
                        type="text"
                        autofocus
                        :placeholder="t('products.search_placeholder')"
                        class="mt-3 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                </div>

                <!-- List -->
                <div class="flex-1 overflow-y-auto">
                    <p v-if="!loading && results.length === 0" class="p-6 text-center text-sm text-slate-500">
                        {{ t('products.none_found') }}
                    </p>
                    <button
                        v-for="p in results"
                        :key="p.id"
                        type="button"
                        class="block w-full border-b border-gray-50 px-4 py-3 text-left hover:bg-primary-50 dark:border-gray-800/60 dark:hover:bg-gray-800"
                        @click="choose(p)"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium text-slate-800 dark:text-white">{{ p.designation }}</span>
                            <span class="whitespace-nowrap text-xs tabular-nums text-slate-500">{{ Number(p.unit_price_ht).toFixed(2) }} € · {{ Number(p.vat_rate) }}%</span>
                        </div>
                        <div v-if="p.reference || p.description" class="mt-0.5 truncate text-xs text-slate-400">
                            <span v-if="p.reference">{{ p.reference }}</span>
                            <span v-if="p.reference && p.description"> — </span>
                            <span v-if="p.description">{{ p.description }}</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
