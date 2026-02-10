<script setup>
import { computed } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    scenario: {
        type: Object,
        required: true,
    },
    size: {
        type: String,
        default: 'md', // sm, md, lg
    },
    showDescription: {
        type: Boolean,
        default: true,
    },
});

const colorClasses = computed(() => {
    const colors = {
        blue: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        green: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        purple: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
        gray: 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300',
        yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
    };
    return colors[props.scenario.color] || colors.gray;
});

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'px-2 py-1 text-xs',
        md: 'px-3 py-1.5 text-sm',
        lg: 'px-4 py-2 text-base',
    };
    return sizes[props.size] || sizes.md;
});

const vatRateText = computed(() => {
    if (props.scenario.rate === 0) {
        return `${t('vat')} 0%`;
    }
    return `${t('vat')} ${props.scenario.rate}%`;
});
</script>

<template>
    <div class="flex items-start gap-3">
        <span
            :class="[colorClasses, sizeClasses]"
            class="inline-flex items-center rounded-full font-medium"
        >
            <svg
                v-if="scenario.rate === 0"
                class="mr-1.5 h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
            </svg>
            {{ scenario.label }}
        </span>
        <div v-if="showDescription" class="flex flex-col">
            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                {{ vatRateText }}
            </span>
            <span class="text-xs text-slate-500 dark:text-slate-400">
                {{ scenario.description }}
            </span>
        </div>
    </div>
</template>
