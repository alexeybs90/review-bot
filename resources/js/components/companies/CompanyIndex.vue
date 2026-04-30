<template>
    <div class="overflow-hidden bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-300">Компании</h2>
                <router-link :to="{ name: 'companies.edit', params: { id: 0 }}"
                             class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700
                              text-white text-sm font-medium rounded-lg transition-colors duration-200 shadow-sm dark:text-gray-300">
                    <svg xmlns="http://w3.org" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Создать компанию
                </router-link>
            </div>

            <div class="overflow-x-auto border border-gray-100 dark:border-gray-600 rounded-xl">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Наименование</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider"> </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-600">
                    <tr v-for="company in companies" :key="company.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-300">{{ company.name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            {{ company.email }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <router-link
                                :to="{ name: 'companies.edit', params: { id: company.id } }"
                                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all bg-indigo-50 text-indigo-600 hover:bg-indigo-100 mr-4
                                       dark:bg-indigo-500/10 dark:text-indigo-400 dark:hover:bg-indigo-500/20 dark:border dark:border-indigo-500/20">
                                Ред.
                            </router-link>
                            <button
                                @click="deleteCompany(company.id)"
                                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all bg-red-50 text-red-600 hover:bg-red-100
                                       dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20 dark:border dark:border-red-500/20 cursor-pointer">
                                Удалить
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
import Swal from 'sweetalert2';
import swalConfirm from "../../config/swalConfirm.js";

const companies = ref([]);

const getCompanies = async () => {
    let response = await axios.get('/api/companies');
    companies.value = response.data;
};

const deleteCompany = async (id) => {
    // Вызываем окно подтверждения
    const confirmOptions = swalConfirm
    confirmOptions.title = 'Удалить компанию?'
    const result = await Swal.fire(confirmOptions);

    if (!result.isConfirmed) return;

    try {
        await axios.delete(`/api/companies/${id}`);
        await getCompanies();

        // Всплывающее уведомление об успешном удалении
        Swal.fire({
            title: 'Deleted!',
            text: 'Company has been deleted.',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false,
            customClass: {
                popup: 'rounded-2xl'
            }
        });
    } catch (e) {
        Swal.fire({
            title: 'Error!',
            text: 'Could not delete company.',
            icon: 'error',
            customClass: {
                popup: 'rounded-2xl'
            }
        });
    }
};

onMounted(getCompanies);
</script>
