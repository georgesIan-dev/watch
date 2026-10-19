import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue'
import { VitePWA } from "vite-plugin-pwa";

export default defineConfig({
    plugins: [
        vue(),

        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),

        VitePWA({
            injectRegister: null,
            registerType: 'autoUpdate',
            includeAssets: ['favicon.ico'],
            manifest: {
                id: '/',
                name: 'Monitoring System',
                short_name: 'Monitoring',
                description: 'Monitoring System with Watch Party',
                theme_color: '#000000',
                background_color: '#000000',
                display: 'standalone',
                // 'any' so videos can go landscape/fullscreen when the phone rotates
                orientation: 'any',
                categories: ['entertainment', 'productivity'],
                start_url: '/',
                scope: '/',
                icons: [
                    { src: '/pwa-192x192.png', sizes: '192x192', type: 'image/png' },
                    { src: '/pwa-512x512.png', sizes: '512x512', type: 'image/png' },
                    { src: '/pwa-512x512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
                ],
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg}'],
                // sw.js is served from the site root (see copy-pwa-files.js), but the
                // assets live in /build — so make every precache URL absolute.
                modifyURLPrefix: { '': '/build/' },
                // Laravel renders the pages; there is no index.html to fall back to.
                navigateFallback: null,
                // Bundle the Workbox runtime into sw.js so there's only one file to copy.
                inlineWorkboxRuntime: true,
            },
        }),
    ],

    define: {
        __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: false,
    },
});