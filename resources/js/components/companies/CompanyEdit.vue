<template>
    <div class="max-w-3x2 mx-auto">
        <div v-if="pageLoading" class="animate-pulse space-y-6 p-8 bg-white dark:bg-gray-800 rounded-xl">
            <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
            <div class="grid grid-cols-2 gap-6">
                <div class="h-12 bg-gray-200 dark:bg-gray-700 rounded"></div>
                <div class="h-12 bg-gray-200 dark:bg-gray-700 rounded"></div>
            </div>
            <div class="h-32 bg-gray-200 dark:bg-gray-700 rounded"></div>
        </div>
        <template v-else>

        <button @click="$router.back()" class="mb-6 flex items-center text-sm text-gray-500 hover:text-indigo-600 transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Назад
        </button>

        <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-100 dark:bg-gray-800 dark:border-gray-600">
            <div class="p-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-300 mb-6">{{ (id ? 'Редактировать' : 'Создать') }} компанию</h2>

                <form @submit.prevent="updateCompany" class="space-y-6">
                    <form-text id="name" label="Наименование" v-model="company.name"
                               placeholder="e.g. Acme Corp" :error="errors.name?.[0]"></form-text>

                    <form-text id="email" label="Email" v-model="company.email"
                               placeholder="contact@company.com" :error="errors.email?.[0]"></form-text>

                    <form-text id="phone" label="Телефон" v-model="company.phone"
                               placeholder="+7 (___) ___ __ __" :error="errors.phone?.[0]"></form-text>

                    <form-text id="address" label="Адрес" v-model="company.address"
                               placeholder="город, улица, дом" :error="errors.address?.[0]"></form-text>

                    <form-text id="website" label="Сайт" v-model="company.website"
                               placeholder="https://example.com" :error="errors.website?.[0]"></form-text>

                    <form-textarea id="description" label="Описание" v-model="company.description"
                                   placeholder="" :error="errors.description?.[0]"></form-textarea>

                    <form-checkbox id="is_dealer" label="Дилер" v-model="company.is_dealer"
                                   :error="errors.is_dealer?.[0]"></form-checkbox>

                    <form-text id="inn" label="ИНН" v-model="company.inn"
                               placeholder="https://example.com" :error="errors.inn?.[0]"></form-text>

                    <form-select id="company_type" label="Тип деятельности" v-model="company.company_type"
                                 :error="errors.company_type?.[0]"
                                 :options="companyTypeOptions"></form-select>

                    <div v-if="company.images && company.images.length" class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Изображения</label>
                        <div class="grid grid-cols-4 gap-4">
                            <div v-for="image in company.images" :key="image.id" class="relative group">
                                <img :src="image.url" class="h-75 w-full object-cover rounded-lg border shadow-sm cursor-pointer"
                                     @click="openImageModal(image.url)">
                                <button type="button" @click="deleteExistingImage(image.id)"
                                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity shadow-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="border-2 border-dashed border-gray-200 dark:border-gray-700 dark:text-gray-300 p-6 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Загрузить</label>
                        <input type="file" multiple @change="handleFilesUpload" ref="fileInput"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">

                        <div v-if="newPreviewUrls.length" class="flex flex-wrap gap-3 mt-4">
                            <div v-for="(url, index) in newPreviewUrls" :key="index" class="relative">
                                <img :src="url" class="h-25 w-20 object-cover rounded-lg border-2 border-indigo-400">
                                <span class="absolute bottom-0 right-0 bg-indigo-500 text-white text-[10px] px-1 rounded-tl-md">New</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 flex items-center justify-end space-x-3 border-t border-gray-50/20">
                        <button type="submit"
                                class="px-6 py-2.5 bg-indigo-600 text-white font-semibold rounded-lg
                                 hover:bg-indigo-700 text-sm font-medium rounded-lg transition-colors duration-200 shadow-sm dark:text-gray-300 cursor-pointer">
                            Сохранить
                        </button>
                    </div>
                </form>
            </div>

            <Modal v-model="isImageModalOpen" maxWidth="max">
                <div class="p-2 bg-gray-900 flex items-center justify-center">
                    <img :src="activeImageUrl" class="max-h-[85vh] max-w-full object-contain rounded-lg shadow-md">
                </div>
            </Modal>

            <!-- Секция отзывов (Review List) -->
            <div class="bg-gray-50 dark:bg-gray-900 border-t border-gray-100 dark:border-gray-700 p-8">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-300 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    Отзывы
                </h3>

                <div v-if="company.reviews && company.reviews.length" class="space-y-4">
                    <div v-for="review in company.reviews" :key="review.id" class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 shadow-sm">
                        <p class="text-gray-400 italic">"{{ review }}"</p>
                    </div>
                </div>
                <p v-else class="text-gray-400 dark:text-gray-400 text-sm italic">No reviews yet for this company.</p>
            </div>
        </div>
        </template>

        <Loading :active="pageLoading" message="Processing request..." />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter, useRoute } from 'vue-router';
import Swal from 'sweetalert2';
import swalConfirm from "../../config/swalConfirm";
import swalToastSuccess from "../../config/swalToastSuccess";
import FormText from "../shared/FormText.vue";
import FormSelect from "../shared/FormSelect.vue";
import FormCheckbox from "../shared/FormCheckbox.vue";
import FormTextarea from "../shared/FormTextarea.vue";
import Modal from "../shared/Modal.vue";
import Loading from "../shared/Loading.vue";

const pageLoading = ref(true);

const isImageModalOpen = ref(false);
const activeImageUrl = ref('');

// Функция для открытия картинки в модалке
const openImageModal = (url) => {
    activeImageUrl.value = url;
    isImageModalOpen.value = true;
};

const companyTypeOptions = [
    {value: 0, text: '-'},
    {value: 1, text: 'Продуктовый магазин'},
    {value: 2, text: 'Общепит'},
    {value: 3, text: 'Производство пива'},
    {value: 4, text: 'it услуги'},
    {value: 5, text: 'Ремонт авто'},
];

const fileInput = ref(null);

const router = useRouter();
const route = useRoute(); // Получаем доступ к параметрам URL (id)

let id = ref(0);
const company = ref({
    name: '',
    email: '',
    phone: '',
    address: '',
    website: '',
    description: '',
    is_dealer: 0,
    inn: '',
    company_type: 0,
    reviews: [],
    images: [],
});
const errors = ref({});

const newFiles = ref([]);
const newPreviewUrls = ref([]);

const handleFilesUpload = (event) => {
    const files = Array.from(event.target.files);
    newFiles.value = files;
    newPreviewUrls.value = files.map(file => URL.createObjectURL(file));
};

const getCompany = async () => {
    pageLoading.value = true;
    id = parseInt(route.params.id)
    if (!id) {
        company.value.name = ''
        company.value.email = ''
        return;
    }
    try {
        // id берется из :id в роутере
        let response = await axios.get(`/api/companies/${id}`);
        company.value = response.data;
    } catch (e) {
        console.error('Could not fetch company');
    } finally {
        pageLoading.value = false;
    }
};

const updateCompany = async () => {
    pageLoading.value = true;
    id = parseInt(route.params.id)
    errors.value = {};

    const formData = new FormData();
    if (id) {
        formData.append('_method', 'PUT'); // Имитация PUT для Laravel
    }

    Object.keys(company.value).forEach(key => {
        let value = company.value[key];

        if (value === null) value = '';

        if (typeof value === 'boolean') value = value ? 1 : 0;

        if (key === 'images' || key === 'reviews') return;

        formData.append(key, value);
    });
    // formData.append('name', company.value.name);
    // formData.append('email', company.value.email);

    newFiles.value.forEach((file, index) => {
        formData.append(`images[]`, file);
    });

    try {
        let path = '/api/companies'
        if (id) path += '/' + id
        let item = await axios.post(path, formData);

        newFiles.value = []
        newPreviewUrls.value = []
        if (fileInput.value) {
            fileInput.value.value = '';
        }

        if (!id && item.data.id) {
            await router.push({ name: 'companies.edit', params: { id: item.data.id } })
        } else {
            await getCompany(id)
        }

        const toastOptions = swalToastSuccess
        const Toast = Swal.mixin(toastOptions);

        await Toast.fire({
            icon: 'success',
            title: 'Company updated successfully'
        });
        // await router.push({ name: 'companies.index' });
    } catch (e) {
        if (e.response.status === 422) {
            errors.value = e.response.data.errors;
        }
    } finally {
        pageLoading.value = false;
    }
};

const deleteExistingImage = async (imageId) => {
    const confirmOptions = swalConfirm
    confirmOptions.title = 'Удалить изображение?'
    const result = await Swal.fire(confirmOptions);
    if (!result.isConfirmed) return;

    pageLoading.value = true;
    try {
        await axios.delete(`/api/company-images/${imageId}`);
        // Удаляем из локального массива, чтобы картинка исчезла
        company.value.images = company.value.images.filter(img => img.id !== imageId);
    } catch (e) {
        alert('Could not delete image');
    } finally {
        pageLoading.value = false;
    }
};

onMounted(getCompany);
</script>
