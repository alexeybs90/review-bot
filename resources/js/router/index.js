import { createRouter, createWebHistory } from 'vue-router';
import CompanyIndex from '../components/companies/CompanyIndex.vue';
import CompanyEdit from '../components/companies/CompanyEdit.vue';

const routes = [
    { path: '/companies', name: 'companies.index', component: CompanyIndex },
    { path: '/companies/:id/edit', name: 'companies.edit', component: CompanyEdit, props: true },
];

export default createRouter({
    history: createWebHistory(),
    routes
});
