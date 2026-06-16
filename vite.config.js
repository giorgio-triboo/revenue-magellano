import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
        rollupOptions: {
            output: {
                manualChunks: {
                    alpine: ['alpinejs'],
                },
            },
        },
    },
    resolve: {
        alias: {
            '@': '/resources/js',
            'alpine': 'alpinejs',
        },
    },
    optimizeDeps: {
        include: ['alpinejs', 'lucide'],
    },
});
