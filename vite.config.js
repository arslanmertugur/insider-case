import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite'; // 1. Satır: Eklentiyi içe aktar

export default defineConfig({
    plugins: [
        tailwindcss(), // 2. Satır: Laravel plugininden ÖNCE ekle
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
    ],
});