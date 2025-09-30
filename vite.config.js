import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  build: {
    manifest: true,
    outDir: 'public/build',
    rollupOptions: {
      input: [
        'resources/js/app.js',
        'resources/css/app.css',
      ],
    },
  },
  plugins: [
    laravel({
      input: [
        'resources/js/app.js',
        'resources/css/app.css',
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
