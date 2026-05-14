<template>
    <div>
        <div class="flex items-center space-x-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-700/30 border border-transparent hover:border-indigo-500/30 transition-all">
            <div class="flex items-center h-5">
                <input :id="id"
                       v-model="model"
                       type="checkbox"
                       class="h-5 w-5 text-indigo-600 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500 dark:bg-gray-800 transition-colors cursor-pointer"
                >
            </div>
            <div class="text-sm leading-6">
                <label :for="id" class="font-medium text-gray-700 dark:text-gray-200 cursor-pointer select-none">
                    {{ label }}
                </label>
                <!--                            <p class="text-xs text-gray-500 dark:text-gray-400">Company has rights to distribute products</p>-->
            </div>
        </div>
        <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    </div>
</template>

<script setup>
const model = defineModel();

defineProps({
    label: { type: String, default: '' },
    error: { type: String, default: '' },
    id: { type: String, default: () => `input-${Math.random().toString(36).substr(2, 9)}` }
});
</script>

<style scoped>

</style>
