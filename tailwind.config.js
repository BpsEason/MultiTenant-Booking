/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.blade.php",
        "./resources/views/**/*.blade.php", // 確保包含所有子目錄
        "./app/Livewire/**/*.php", // 確保包含 Livewire 的 PHP 檔案
    ],
    theme: {
        extend: {},
    },
    plugins: [],
};
