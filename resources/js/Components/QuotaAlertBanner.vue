<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    alerts: { type: Array, default: () => [] },
});

// Un quota déjà atteint est bloquant : il prime sur un simple avertissement.
const hasReached = computed(() => props.alerts.some((a) => a.reached));

const labelFor = (type) => t(`quota_alert.types.${type}`);

const lineFor = (alert) =>
    alert.reached
        ? t('quota_alert.reached', { item: labelFor(alert.type), limit: alert.limit })
        : t('quota_alert.approaching', { item: labelFor(alert.type), remaining: alert.remaining });
</script>

<template>
    <div
        v-if="alerts.length"
        class="mb-6 rounded-xl border px-4 py-3"
        :class="hasReached
            ? 'border-rose-300 bg-rose-50 dark:border-rose-800 dark:bg-rose-900/30'
            : 'border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-900/30'"
    >
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p
                    class="text-sm font-semibold"
                    :class="hasReached
                        ? 'text-rose-900 dark:text-rose-100'
                        : 'text-amber-900 dark:text-amber-100'"
                >
                    {{ hasReached ? t('quota_alert.title_reached') : t('quota_alert.title_approaching') }}
                </p>
                <ul
                    class="mt-1 space-y-0.5 text-sm"
                    :class="hasReached
                        ? 'text-rose-800 dark:text-rose-200'
                        : 'text-amber-800 dark:text-amber-200'"
                >
                    <li v-for="alert in alerts" :key="alert.type">
                        {{ lineFor(alert) }}
                    </li>
                </ul>
            </div>
            <Link
                :href="route('subscription.index')"
                class="w-full shrink-0 rounded-lg px-4 py-1.5 text-center text-sm font-semibold text-white transition-colors sm:w-auto"
                :class="hasReached
                    ? 'bg-rose-600 hover:bg-rose-700'
                    : 'bg-amber-600 hover:bg-amber-700'"
            >
                {{ t('quota_alert.cta') }}
            </Link>
        </div>
    </div>
</template>
