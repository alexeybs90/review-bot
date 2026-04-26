import { createRouter, createWebHistory } from 'vue-router';
import CompanyIndex from '../components/companies/CompanyIndex.vue';
import CompanyCreate from '../components/companies/CompanyCreate.vue';
import CompanyEdit from '../components/companies/CompanyEdit.vue';

const routes = [
    { path: '/dashboard', name: 'companies.index', component: CompanyIndex },
    { path: '/companies/create', name: 'companies.create', component: CompanyCreate },
    { path: '/companies/:id/edit', name: 'companies.edit', component: CompanyEdit, props: true },
];

export default createRouter({
    history: createWebHistory(),
    routes
});
