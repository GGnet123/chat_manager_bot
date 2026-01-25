import react from "@vitejs/plugin-react";
import { defineConfig } from "vite";
import { resolve } from "path";

export default defineConfig({
    plugins: [react()],
    resolve: {
        alias: {
            "@": resolve(__dirname, "./src"),
        },
    },
    server: {
        port: 3000,
        host: "0.0.0.0",
        proxy: {
            "/api": {
                target: "http://nginx:80",
                changeOrigin: true,
            },
        },
        watch: {
            usePolling: true,
        },
    },
});
