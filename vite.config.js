import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/filament/admin/theme.css',
                'resources/js/app.jsx',
                'resources/css/app.css',
            ],
            ssr: 'resources/js/ssr.jsx',
            /* refresh: true, */
        }),
        react(),
    ],
});
