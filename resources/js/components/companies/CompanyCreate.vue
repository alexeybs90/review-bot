<template>
    <form @submit.prevent="saveCompany" class="space-y-4">
        <input v-model="form.name" placeholder="Name" class="border p-2">
        <input v-model="form.email" placeholder="Email" class="border p-2">
        <button type="submit" class="bg-blue-500 text-white p-2">Save</button>
    </form>
</template>

<script setup>
import { reactive } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const form = reactive({ name: '', email: '' });
const router = useRouter();

const saveCompany = async () => {
    await axios.post('/api/companies', form);
    await router.push({ name: 'companies.index' });
};
</script>
