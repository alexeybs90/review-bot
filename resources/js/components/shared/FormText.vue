<template>
    <div>
        <label v-if="label" :for="id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
            {{ label }}
        </label>
        <input v-model="model"
               :id="id"
               :type="type"
               :placeholder="placeholder"
               class="block w-full px-4 py-3 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 transition-shadow"
               :class="{'border-red-400': error}">
        <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    </div>
</template>

<script setup>
const model = defineModel();

defineProps({
    label: { type: String, default: '' },
    type: { type: String, default: 'text' },
    placeholder: { type: String, default: '' },
    error: { type: String, default: '' },
    id: { type: String, default: () => `input-${Math.random().toString(36).substr(2, 9)}` }
});
</script>

<style scoped>

</style>
