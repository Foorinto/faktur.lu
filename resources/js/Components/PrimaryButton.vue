<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
});

const sizeClasses = {
    sm: 'px-3 py-1.5 text-xs rounded-lg',
    md: 'px-5 py-2.5 text-sm rounded-xl',
    lg: 'px-6 py-3 text-base rounded-xl',
};
</script>

<template>
    <button
        :type="type"
        :class="[
            'inline-flex items-center justify-center font-semibold transition-all duration-200',
            'bg-primary-500 hover:bg-primary-600 text-white',
            'focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
            'disabled:opacity-50 disabled:cursor-not-allowed',
            'dark:hover:bg-primary-400 dark:focus:ring-offset-slate-800',
            sizeClasses[size],
        ]"
    >
        <slot />
    </button>
</template>
