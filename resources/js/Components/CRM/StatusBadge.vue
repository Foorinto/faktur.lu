<script setup>
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
});

const statusConfig = {
    prospect: { color: 'bg-violet-100 text-violet-700 dark:bg-violet-900 dark:text-violet-300' },
    contacted: { color: 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' },
    discussing: { color: 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300' },
    active: { color: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300' },
    inactive: { color: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300' },
    lost: { color: 'bg-rose-100 text-rose-700 dark:bg-rose-900 dark:text-rose-300' },
};

const config = statusConfig[props.status] || statusConfig.active;
</script>

<template>
    <span
        :class="config.color"
        class="inline-flex items-center rounded-xl px-2.5 py-0.5 text-xs font-medium"
    >
        {{ t(`crm.status_${status}`) }}
    </span>
</template>
