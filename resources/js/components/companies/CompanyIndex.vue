<template>
    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Companies</h2>
                <router-link :to="{ name: 'companies.edit', params: { id: 0 }}"
                             class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 shadow-sm">
                    <svg xmlns="http://w3.org" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Company
                </router-link>
            </div>

            <div class="overflow-x-auto border border-gray-100 rounded-xl">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                    <tr v-for="company in companies" :key="company.id" class="hover:bg-gray-50/50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ company.name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ company.email }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <router-link
                                :to="{ name: 'companies.edit', params: { id: company.id } }"
                                class="text-indigo-600 hover:text-indigo-900 mr-4 bg-indigo-50 px-3 py-1.5 rounded-md transition-colors">
                                Edit
                            </router-link>
                            <button
                                @click="deleteCompany(company.id)"
                                class="text-red-600 hover:text-red-900 bg-red-50 px-3 py-1.5 rounded-md transition-colors">
                                Delete
                            </button>
                        </td>
                    </tr>
                    <!-- Если компаний нет -->
                    <tr v-if="companies.length === 0">
                        <td colspan="3" class="px-6 py-10 text-center text-gray-400">
                            No companies found.
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const companies = ref([]);

const getCompanies = async () => {
    let response = await axios.get('/api/companies');
    companies.value = response.data;
};

const deleteCompany = async (id) => {
    if (!confirm('Are you sure?')) return;
    await axios.delete(`/api/companies/${id}`);
    await getCompanies();
};

onMounted(getCompanies);
</script>
