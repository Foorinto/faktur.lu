<script setup>
defineProps({
    variant: {
        type: String,
        default: 'default',
        validator: (value) =>
            ['default', 'primary', 'success', 'warning', 'danger', 'info', 'turquoise'].includes(value),
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
});

const sizeClasses = {
    sm: 'px-2 py-0.5 text-xs rounded-lg',
    md: 'px-3 py-1 text-sm rounded-xl',
    lg: 'px-4 py-1.5 text-base rounded-xl',
};

const variantClasses = {
    default: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
    primary: 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400',
    success: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    warning: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    danger: 'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400',
    info: 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
    turquoise: 'bg-[#00f5d4]/10 text-[#00a896] dark:bg-[#00f5d4]/20 dark:text-[#00f5d4]',
};
</script>

<template>
    <span
        :class="[
            'inline-flex items-center font-medium',
            sizeClasses[size],
            variantClasses[variant],
        ]"
    >
        <slot />
    </span>
</template>
