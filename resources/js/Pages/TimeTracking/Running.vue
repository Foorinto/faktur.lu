<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    timer: Object,
});

const currentTimerSeconds = ref(0);
let timerInterval = null;

onMounted(() => {
    if (props.timer) {
        currentTimerSeconds.value = props.timer.current_duration_seconds;
        timerInterval = setInterval(() => {
            currentTimerSeconds.value++;
        }, 1000);
    }
});

onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
});

const formattedTimerDuration = computed(() => {
    const hours = Math.floor(currentTimerSeconds.value / 3600);
    const minutes = Math.floor((currentTimerSeconds.value % 3600) / 60);
    const seconds = currentTimerSeconds.value % 60;
    return `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
});

const stopTimer = () => {
    if (props.timer) {
        router.post(route('time-entries.stop', props.timer.id), {}, {
            onSuccess: () => {
                if (timerInterval) {
                    clearInterval(timerInterval);
                    timerInterval = null;
                }
            },
        });
    }
};
</script>

<template>
    <Head title="Timer en cours" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                Timer en cours
            </h1>
        </template>

        <div v-if="timer" class="max-w-2xl mx-auto">
            <div class="overflow-hidden rounded-lg bg-gradient-to-r from-indigo-500 to-purple-600 shadow-lg">
                <div class="px-8 py-12 text-center">
                    <p class="text-sm font-medium text-indigo-100">En cours</p>
                    <p class="mt-4 text-6xl font-bold text-white font-mono">{{ formattedTimerDuration }}</p>
                    <p class="mt-4 text-lg text-indigo-200">
                        {{ timer.client?.name }}
                    </p>
                    <p v-if="timer.project_name" class="text-indigo-300">
                        {{ timer.project_name }}
                    </p>
                    <p v-if="timer.description" class="mt-2 text-sm text-indigo-200">
                        {{ timer.description }}
                    </p>

                    <button
                        @click="stopTimer"
                        class="mt-8 inline-flex items-center rounded-full bg-white px-8 py-4 text-lg font-semibold text-indigo-600 shadow-sm hover:bg-indigo-50"
                    >
                        <svg class="mr-3 h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.5 3.5A1.5 1.5 0 017 5v10a1.5 1.5 0 01-3 0V5a1.5 1.5 0 011.5-1.5zm8 0A1.5 1.5 0 0115 5v10a1.5 1.5 0 01-3 0V5a1.5 1.5 0 011.5-1.5z" clip-rule="evenodd" />
                        </svg>
                        Arrêter le timer
                    </button>
                </div>
            </div>

            <div class="mt-6 text-center">
                <Link
                    :href="route('time-entries.index')"
                    class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                >
                    Retour à la liste des entrées
                </Link>
            </div>
        </div>

        <div v-else class="max-w-2xl mx-auto text-center py-12">
            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Aucun timer en cours</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Démarrez un nouveau timer depuis la page de suivi du temps.
            </p>
            <Link
                :href="route('time-entries.index')"
                class="mt-6 inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
            >
                Démarrer un timer
            </Link>
        </div>
    </AppLayout>
</template>
