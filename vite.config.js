import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        hmr: {
            host: 'localhost',
        },
        watch: {
            usePolling: false, // Evita il polling continuo
          interval: 300, // Aumenta l'intervallo tra le scansioni
        },
    },
    resolve: {
        alias: {
            '@': '/resources/js',
            'alpine': 'alpinejs'
        },
    },
    optimizeDeps: {
        include: ['alpinejs', 'lucide']
    },
    build: {
        rollupOptions: {
            input: {
                app: 'resources/views/layouts/app.blade.php',
                robots: 'public/robots.txt', // Aggiungi il percorso
            },
            output: {
                manualChunks: {
                    alpine: ['alpinejs']
                }
            }
        }
    }
    
});

import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
