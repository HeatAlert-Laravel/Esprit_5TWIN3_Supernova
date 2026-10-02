import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    server: { port: 5174, strictPort: true },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/heatalert-theme.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
