<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useMatomo } from '@/Composables/useMatomo';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();
const { checklist: trackChecklist } = useMatomo();

const props = defineProps({
    checklist: {
        type: Object,
        required: true,
    },
});

const dismissed = ref(false);

const dismiss = async () => {
    if (!confirm(t('onboarding_checklist.dismiss_confirm'))) {
        return;
    }
    try {
        await fetch(route('onboarding.dismiss-checklist'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });
        trackChecklist.dismissed();
        dismissed.value = true;
    } catch (e) {
        console.error(e);
    }
};

const onTaskClick = (task) => {
    if (!task.completed) {
        trackChecklist.taskClicked(task.key);
    }
};
</script>

<template>
    <div v-if="!dismissed && checklist" class="bg-white dark:bg-surface-card rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-primary-500/10">
                    <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white">{{ t('onboarding_checklist.first_steps') }}</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ t('onboarding_checklist.steps_completed', { completed: checklist.completed, total: checklist.total }) }}</p>
                </div>
            </div>
            <button @click="dismiss" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" :title="t('onboarding_checklist.dismiss_title')">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Progress bar -->
        <div class="mb-4">
            <div class="h-2 bg-slate-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full bg-primary-500 transition-all duration-500" :style="{ width: checklist.percentage + '%' }"></div>
            </div>
        </div>

        <!-- Tasks -->
        <ul class="space-y-2">
            <li v-for="task in checklist.tasks" :key="task.key">
                <Link
                    :href="task.completed ? '#' : (route(task.route) + (task.hash ? '#' + task.hash : ''))"
                    @click="onTaskClick(task)"
                    :class="[
                        'flex items-center gap-3 p-2 rounded-lg transition-colors',
                        task.completed
                            ? 'text-slate-500 dark:text-slate-400'
                            : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-gray-800 cursor-pointer'
                    ]"
                >
                    <div :class="[
                        'flex items-center justify-center w-5 h-5 rounded-full flex-shrink-0',
                        task.completed ? 'bg-emerald-500' : 'border-2 border-slate-300 dark:border-gray-600'
                    ]">
                        <svg v-if="task.completed" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span :class="['text-sm', task.completed && 'line-through']">{{ task.label }}</span>
                </Link>
            </li>
        </ul>
    </div>
</template>
