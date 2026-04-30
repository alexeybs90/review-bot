<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 font-sans antialiased text-gray-900 dark:text-gray-100">
        <!-- Шапка -->
        <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <!-- Логотип -->
                        <div class="flex-shrink-0 flex items-center cursor-pointer" @click="$router.push('/')">
                            <div class="bg-indigo-600 p-2 rounded-lg">
                                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1" />
                                </svg>
                            </div>
                            <span class="ml-3 text-xl font-bold tracking-tight text-gray-900 dark:text-white">CorpManager</span>
                        </div>

                        <!-- Навигация -->
                        <div class="hidden sm:ml-10 sm:flex sm:space-x-8">
                            <router-link
                                :to="{ name: 'companies.index' }"
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors"
                                :class="[$route.name === 'companies.index' ? 'border-indigo-500 text-gray-900 dark:text-gray-300'
                                 : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:hover:text-white']">
                                Компании
                            </router-link>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Основной контент -->
        <div class="py-10">
            <main class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Анимация перехода между страницами -->
                <router-view v-slot="{ Component }">
                    <transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 translate-y-4"
                        enter-to-class="opacity-100 translate-y-0"
                        mode="out-in">
                        <component :is="Component" />
                    </transition>
                </router-view>
            </main>
        </div>
    </div>
</template>
