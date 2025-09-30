import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    build: {
        manifest: true,          // ⚡ obligatorio en producción
        outDir: 'public/build',  // ⚡ donde Laravel lo espera
        rollupOptions: {
            input: [
                'resources/js/app.js',
                'resources/css/app.css'  // asegúrate de compilar el CSS
            ],
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
                'resources/css/app.css'
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
})
