<template>
    <div class="p-6">
        <h2 class="text-xl mb-4">Edit Company</h2>

        <form @submit.prevent="updateCompany" class="space-y-4">
            <div>
                <label>Name</label>
                <input v-model="company.name" type="text" class="border p-2 w-full">
                <span v-if="errors.name" class="text-red-500 text-sm">{{ errors.name[0] }}</span>
            </div>

            <div>
                <label>Email</label>
                <input v-model="company.email" type="email" class="border p-2 w-full">
                <span v-if="errors.email" class="text-red-500 text-sm">{{ errors.email[0] }}</span>
            </div>

            <div class="space-x-2">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Update</button>
                <router-link :to="{ name: 'companies.index' }" class="bg-gray-500 text-white px-4 py-2 rounded">Cancel</router-link>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter, useRoute } from 'vue-router';

const router = useRouter();
const route = useRoute(); // Получаем доступ к параметрам URL (id)

const company = ref({
    name: '',
    email: ''
});
const errors = ref({});

// 1. Загружаем данные компании при монтировании
const getCompany = async () => {
    try {
        // id берется из :id в роутере
        let response = await axios.get(`/api/companies/${route.params.id}`);
        company.value = response.data;
    } catch (e) {
        console.error('Could not fetch company');
    }
};

// 2. Метод обновления
const updateCompany = async () => {
    errors.value = {};
    try {
        await axios.put(`/api/companies/${route.params.id}`, company.value);
        await router.push({ name: 'companies.index' });
    } catch (e) {
        if (e.response.status === 422) {
            errors.value = e.response.data.errors;
        }
    }
};

onMounted(getCompany);
</script>
