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
    
    // ===== DEVELOPMENT SERVER CONFIG =====
    // Untuk testing di jaringan lokal (HP, tablet, dll)
    server: {
        host: "0.0.0.0", // Allow access from network
        port: 5173,
        strictPort: false,
        hmr: {
            host: "192.168.0.104", // Your computer's IP address
        },
    },

    // ===== PRODUCTION CONFIG =====
    // Untuk production/hosting, uncomment config dibawah dan comment config diatas
    // server: {
    //     host: "127.0.0.1",
    //     port: 5173,
    //     strictPort: false,
    //     hmr: {
    //         host: "localhost",
    //     },
    // },
});
