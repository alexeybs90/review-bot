<template>
    <div class="max-w-3xl mx-auto">
        <!-- Кнопка назад -->
        <button @click="$router.back()" class="mb-6 flex items-center text-sm text-gray-500 hover:text-indigo-600 transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to list
        </button>

        <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-100">
            <div class="p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ (id ? 'Edit' : 'Create') }} Company</h2>

                <form @submit.prevent="updateCompany" class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Company Name</label>
                        <input v-model="company.name" type="text" placeholder="e.g. Acme Corp"
                               class="block w-full px-4 py-3 rounded-lg border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 transition-shadow shadow-sm"
                               :class="{'border-red-400': errors.name}">
                        <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name[0] }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
                        <input v-model="company.email" type="email" placeholder="contact@company.com"
                               class="block w-full px-4 py-3 rounded-lg border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 transition-shadow shadow-sm"
                               :class="{'border-red-400': errors.email}">
                        <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email[0] }}</p>
                    </div>

                    <div class="pt-4 flex items-center justify-end space-x-3 border-t border-gray-50">
                        <button type="submit"
                                class="px-6 py-2.5 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-200 transition-all shadow-md shadow-indigo-100">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Секция отзывов (Review List) -->
            <div class="bg-gray-50 border-t border-gray-100 p-8">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    Latest Reviews
                </h3>

                <div v-if="company.reviews && company.reviews.length" class="space-y-4">
                    <div v-for="review in company.reviews" :key="review.id" class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                        <p class="text-gray-600 italic">"{{ review }}"</p>
                    </div>
                </div>
                <p v-else class="text-gray-400 text-sm italic">No reviews yet for this company.</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter, useRoute } from 'vue-router';

const router = useRouter();
const route = useRoute(); // Получаем доступ к параметрам URL (id)

let id = ref(0);
const company = ref({
    name: '',
    email: '',
    reviews: []
});
const errors = ref({});

// 1. Загружаем данные компании при монтировании
const getCompany = async () => {
    id = parseInt(route.params.id)
    if (!id) {
        company.value.name = ''
        company.value.email = ''
        return;
    }
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
    id = parseInt(route.params.id)
    errors.value = {};
    try {
        if (!id) {
            await axios.post('/api/companies', company.value);
        } else {
            await axios.put(`/api/companies/${route.params.id}`, company.value);
        }
        await router.push({ name: 'companies.index' });
    } catch (e) {
        if (e.response.status === 422) {
            errors.value = e.response.data.errors;
        }
    }
};

onMounted(getCompany);
</script>
