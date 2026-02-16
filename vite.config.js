import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    build: {
        outDir: 'public/build', // Output nella directory pubblica
        emptyOutDir: true, // Pulisce la directory di output prima di ogni build
        rollupOptions: {
            output: {
                manualChunks: {
                    alpine: ['alpinejs'], // Separazione dei moduli per l'ottimizzazione
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
