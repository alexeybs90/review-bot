<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <!-- Полупрозрачный задний фон с эффектом размытия -->
            <div v-if="active" class="fixed inset-0 z-[100] flex flex-col items-center justify-center bg-gray-900/60 backdrop-blur-sm">

                <!-- Анимированный спиннер (Tailwind SVG) -->
                <div class="relative flex items-center justify-center">
                    <svg class="animate-spin h-14 w-14 text-indigo-500" xmlns="w3.org" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <!-- Маленькая иконка компании по центру спиннера для красоты -->
                    <div class="absolute text-indigo-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1"/></svg>
                    </div>
                </div>

                <!-- Текст загрузки -->
                <p class="mt-4 text-sm font-semibold tracking-wide text-gray-200 dark:text-gray-200 animate-pulse">
                    {{ message }}
                </p>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
defineProps({
    active: { type: Boolean, default: false },
    message: { type: String, default: 'Loading data, please wait...' }
});
</script>
