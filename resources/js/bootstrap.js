import axios from 'axios';
window.axios = axios;

// Этот заголовок сообщает Laravel, что запрос является AJAX (XHR)
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
