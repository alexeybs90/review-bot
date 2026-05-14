<template>
    <div>
        <label v-if="label" :for="id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
            {{ label }}
        </label>
        <select v-model="model" :id="id"
                class="block w-full h-[48px] px-4 py-2 rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                :class="{'border-red-400': error}">
            <template v-for="param in options">
                <option :value="param.value">{{ param.text }}</option>
            </template>
        </select>
        <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    </div>
</template>

<script setup>
const model = defineModel();

// Принимаем кастомные параметры (пропсы)
defineProps({
    label: { type: String, default: '' },
    error: { type: String, default: '' },
    options: { type: Array, default: () => [] },
    id: { type: String, default: () => `input-${Math.random().toString(36).substr(2, 9)}` }
});
</script>

<style scoped>

</style>
