<script setup>
import Modal from '@/Components/Modal.vue';

const emit = defineEmits(['close']);

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const close = () => {
    emit('close');
};
</script>

<template>
    <Modal
        :show="show"
        :max-width="maxWidth"
        :closeable="closeable"
        @close="close"
    >
        <div class="px-6 py-4">
            <div class="text-lg font-medium text-slate-900 dark:text-slate-100">
                <slot name="title" />
            </div>

            <div class="mt-4 text-sm text-slate-600 dark:text-slate-400">
                <slot name="content" />
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end px-6 py-4 bg-slate-100 dark:bg-surface-card text-end">
            <slot name="footer" />
        </div>
    </Modal>
</template>
