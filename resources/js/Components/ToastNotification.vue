<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const toasts = ref([]);
let nextId = 0;

const addToast = (type, message) => {
    const id = nextId++;
    toasts.value.push({ id, type, message });
    setTimeout(() => removeToast(id), 5000);
};

const removeToast = (id) => {
    toasts.value = toasts.value.filter(t => t.id !== id);
};

watch(() => page.props.flash, (flash) => {
    if (!flash) return;
    if (flash.success) addToast('success', flash.success);
    if (flash.error) addToast('error', flash.error);
    if (flash.info) addToast('info', flash.info);
}, { immediate: true, deep: true });

const typeConfig = {
    success: {
        bg: 'bg-emerald-50 dark:bg-emerald-900/90 dark:border-emerald-700',
        border: 'border-emerald-200',
        icon: 'text-emerald-500 dark:text-emerald-300',
        text: 'text-emerald-800 dark:text-emerald-50',
    },
    error: {
        bg: 'bg-rose-50 dark:bg-rose-900/90 dark:border-rose-700',
        border: 'border-rose-200',
        icon: 'text-rose-500 dark:text-rose-300',
        text: 'text-rose-800 dark:text-rose-50',
    },
    info: {
        bg: 'bg-blue-50 dark:bg-blue-900/90 dark:border-blue-700',
        border: 'border-blue-200',
        icon: 'text-blue-500 dark:text-blue-300',
        text: 'text-blue-800 dark:text-blue-50',
    },
};
</script>

<template>
    <div class="fixed top-4 right-4 z-[100] flex flex-col gap-2 pointer-events-none w-full max-w-sm px-4 sm:px-0">
        <transition-group
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 translate-x-4"
            enter-to-class="opacity-100 translate-x-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0 translate-x-4"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                :class="[typeConfig[toast.type].bg, typeConfig[toast.type].border]"
                class="pointer-events-auto flex items-start gap-3 rounded-xl border p-4 shadow-lg"
            >
                <!-- Success icon -->
                <svg v-if="toast.type === 'success'" :class="typeConfig[toast.type].icon" class="h-5 w-5 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
                <!-- Error icon -->
                <svg v-else-if="toast.type === 'error'" :class="typeConfig[toast.type].icon" class="h-5 w-5 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
                <!-- Info icon -->
                <svg v-else :class="typeConfig[toast.type].icon" class="h-5 w-5 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                </svg>

                <p :class="typeConfig[toast.type].text" class="text-sm flex-1 leading-relaxed">
                    {{ toast.message }}
                </p>

                <button
                    @click="removeToast(toast.id)"
                    :class="typeConfig[toast.type].icon"
                    class="flex-shrink-0 hover:opacity-70 transition-opacity"
                    aria-label="Fermer"
                >
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </transition-group>
    </div>
</template>
