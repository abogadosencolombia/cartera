<script setup>
defineProps({
    title: String,
    subtitle: String,
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg">
        <div class="mb-4">
            <h3 class="font-bold text-lg text-gray-900 dark:text-white">{{ title }}</h3>
            <p v-if="subtitle" class="text-sm text-gray-500 dark:text-gray-400">{{ subtitle }}</p>
        </div>
        <div class="h-80">
            <slot />
        </div>
    </div>
</template>