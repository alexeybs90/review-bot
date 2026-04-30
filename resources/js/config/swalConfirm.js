export default {
    title: 'Удалить?',
    text: "Действие необратимо!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#4f46e5', // Indigo-600
    cancelButtonColor: '#ef4444',  // Red-500
    confirmButtonText: 'Удалить',
    cancelButtonText: 'Отмена',
    background: '#1f2937', // Соответствует bg-gray-800
    color: '#f3f4f6',      // Соответствует text-gray-100
    // Стилизация под Tailwind
    customClass: {
        popup: 'rounded-2xl',
        confirmButton: 'px-4 py-2 rounded-lg font-medium text-white',
        cancelButton: 'px-4 py-2 rounded-lg font-medium text-white'
    }
}
