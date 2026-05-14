<template>
    <!-- Teleport переносит модалку в конец тега body -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <!-- Задний полупрозрачный фон (Overlay) -->
            <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" @click="close">

                <!-- Анимация появления контента -->
                <Transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95">
                    <!-- Контейнер окна (останавливаем всплытие клика, чтобы не закрывать при клике на контент) -->
                    <div
                        v-if="isOpen"
                        class="relative max-w-4xl max-h-[90vh] overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-2xl transition-all"
                        :class="maxWidthClass"
                        @click.stop>
                        <!-- Кнопка закрытия (крестик) -->
                        <button @click="close" class="absolute top-4 right-4 z-10 p-1.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>

                        <slot />
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue';

// Двустороннее связывание для открытия/закрытия
const isOpen = defineModel({ type: Boolean, default: false });

const props = defineProps({
    maxWidth: { type: String, default: 'md' }
});

const close = () => { isOpen.value = false; };

// Вычисляемый класс ширины окна
const maxWidthClass = computed(() => {
    return {
        'sm': 'sm:max-w-sm',
        'md': 'sm:max-w-md',
        'lg': 'sm:max-w-lg',
        'xl': 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
        'max': 'max-w-none'
    }[props.maxWidth];
});

// Закрытие по нажатию на Escape
const handleEscape = (e) => {
    if (e.key === 'Escape' && isOpen.value) close();
};

onMounted(() => window.addEventListener('keydown', handleEscape));
onUnmounted(() => window.removeEventListener('keydown', handleEscape));
</script>
