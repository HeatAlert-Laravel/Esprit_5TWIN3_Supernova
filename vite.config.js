import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    server: {
        host: "0.0.0.0",
        port: 5173,
        strictPort: true,
        hmr: {
            host: "localhost",
        },
        watch: {
            usePolling: true,
        },
    },
    plugins: [
        laravel({
            // app.* = resident pages (layouts/app), admin.* = TailAdmin-based admin area (layouts/admin).
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/css/admin.css",
                "resources/js/admin.js",
                "resources/css/heatalert-theme.css",
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
