<script setup>
import { computed } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    password: {
        type: String,
        default: '',
    },
    minLength: {
        type: Number,
        default: 12,
    },
});

const checks = computed(() => [
    {
        key: 'length',
        label: t('password_check.length').replace(':min', String(props.minLength)),
        passed: props.password.length >= props.minLength,
    },
    {
        key: 'case',
        label: t('password_check.case'),
        passed: /[A-Z]/.test(props.password) && /[a-z]/.test(props.password),
    },
    {
        key: 'number',
        label: t('password_check.number'),
        passed: /\d/.test(props.password),
    },
    {
        key: 'symbol',
        label: t('password_check.symbol'),
        passed: /[^A-Za-z0-9]/.test(props.password),
    },
]);
</script>

<template>
    <ul class="mt-2 space-y-1 text-xs">
        <li
            v-for="check in checks"
            :key="check.key"
            class="flex items-center gap-2 transition-colors"
        >
            <!-- Check coché (vert) -->
            <svg
                v-if="check.passed"
                class="h-4 w-4 flex-shrink-0 text-emerald-600 dark:text-emerald-400"
                fill="currentColor"
                viewBox="0 0 20 20"
            >
                <path
                    fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd"
                />
            </svg>
            <!-- Cercle vide (gris) -->
            <svg
                v-else
                class="h-4 w-4 flex-shrink-0 text-slate-400 dark:text-slate-600"
                fill="none"
                viewBox="0 0 20 20"
                stroke="currentColor"
                stroke-width="1.5"
            >
                <circle cx="10" cy="10" r="8" />
            </svg>
            <span
                :class="
                    check.passed
                        ? 'text-emerald-700 dark:text-emerald-400 font-medium'
                        : 'text-slate-500 dark:text-slate-400'
                "
            >
                {{ check.label }}
            </span>
        </li>
    </ul>
</template>
