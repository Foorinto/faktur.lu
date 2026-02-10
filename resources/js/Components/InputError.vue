<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>

<template>
    <div v-show="message">
        <p class="text-sm text-pink-600 dark:text-pink-400">
            {{ message }}
        </p>
    </div>
</template>
