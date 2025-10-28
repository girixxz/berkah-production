import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
    ],
    server: {
        host: "127.0.0.1", // Force IPv4 untuk menghindari CORS issue
        port: 5173,
        strictPort: false,
        hmr: {
            host: "localhost",
        },
    },
});
