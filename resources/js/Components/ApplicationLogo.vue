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

// Logo source : 1518x374 px (ratio ~4.06). On declare ces dimensions
// pour eviter le CLS et permettre au browser de reserver l'espace.
const logoNaturalWidth = 1518;
const logoNaturalHeight = 374;
</script>

<template>
    <img
        src="/images/logo.png"
        alt="faktur.lu"
        :width="logoNaturalWidth"
        :height="logoNaturalHeight"
        :class="['w-auto', heightClasses[size]]"
        decoding="async"
        fetchpriority="high"
    />
</template>
