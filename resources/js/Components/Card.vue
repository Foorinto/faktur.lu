<script setup>
defineProps({
    rounded: {
        type: String,
        default: 'rounded-3xl',
    },
    shadow: {
        type: Boolean,
        default: true,
    },
    border: {
        type: Boolean,
        default: true,
    },
    padding: {
        type: String,
        default: 'p-6',
    },
    withBlur: {
        type: Boolean,
        default: false,
    },
    blurColor: {
        type: String,
        default: 'bg-primary-500/10',
    },
});
</script>

<template>
    <div
        :class="[
            'relative bg-white dark:bg-slate-800 overflow-hidden',
            rounded,
            shadow ? 'shadow-xl shadow-slate-200/50 dark:shadow-slate-900/50' : '',
            border ? 'border border-slate-200 dark:border-slate-700' : '',
            padding,
        ]"
    >
        <!-- Optional blur decoration -->
        <div
            v-if="withBlur"
            :class="['absolute -top-8 -right-8 w-32 h-32 rounded-full blur-3xl', blurColor]"
        ></div>
        <div :class="{ relative: withBlur }">
            <slot />
        </div>
    </div>
</template>
