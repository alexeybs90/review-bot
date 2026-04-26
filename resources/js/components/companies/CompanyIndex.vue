<template>
    <div class="p-6">
        <router-link :to="{ name: 'companies.create' }" class="btn-primary">Add Company</router-link>
        <table class="min-w-full mt-4">
            <thead><tr><th>Name</th><th>Email</th><th>Actions</th></tr></thead>
            <tbody>
            <tr v-for="company in companies" :key="company.id">
                <td>{{ company.name }}</td>
                <td>{{ company.email }}</td>
                <td>
                    <router-link :to="{ name: 'companies.edit', params: { id: company.id } }">Edit</router-link>
                    <button @click="deleteCompany(company.id)">Delete</button>
                </td>
            </tr>
            </tbody>
        </table>
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
