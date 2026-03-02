<script setup>
defineProps({
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
});

const heightClasses = {
    sm: 'h-7',
    md: 'h-9',
    lg: 'h-11',
};
</script>

<template>
    <img
        src="/images/logo.png"
        alt="faktur.lu"
        :class="['w-auto', heightClasses[size]]"
    />
</template>
