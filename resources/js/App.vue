<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Навигация (общая для всех страниц) -->
        <nav class="bg-white shadow mb-8 p-4">
            <div class="container mx-auto space-x-4">
                <router-link
                    :to="{ name: 'companies.index' }"
                    class="text-blue-600 font-bold"
                >
                    Companies CRUD
                </router-link>

                <router-link
                    :to="{ name: 'companies.create' }"
                    class="text-gray-600 hover:text-blue-500"
                >
                    Add New
                </router-link>
            </div>
        </nav>

        <!-- Место, куда Vue Router будет вставлять ваши компоненты (Index, Create, Edit) -->
        <main class="container mx-auto bg-white p-6 rounded shadow">
            <router-view></router-view>
        </main>
    </div>
</template>

<script setup>
// Здесь можно добавить общую логику для всего приложения, если нужно
</script>

<style>
/* Общие стили */
body { font-family: sans-serif; }
</style>
