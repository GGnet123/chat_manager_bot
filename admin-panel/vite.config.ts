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
            "/v1": {
                target: process.env.API_PROXY_TARGET || "http://nginx:80",
                changeOrigin: true,
            },
            "/webhook": {
                target: process.env.API_PROXY_TARGET || "http://nginx:80",
                changeOrigin: true,
            },
            "/health": {
                target: process.env.API_PROXY_TARGET || "http://nginx:80",
                changeOrigin: true,
            },
        },
        watch: {
            usePolling: true,
        },
    },
    preview: {
        port: 3000,
        host: "0.0.0.0",
        allowedHosts: ["aibotchat.xyz", "localhost"],
        proxy: {
            "/v1": {
                target: process.env.API_PROXY_TARGET || "http://web:80",
                changeOrigin: true,
            },
            "/webhook": {
                target: process.env.API_PROXY_TARGET || "http://web:80",
                changeOrigin: true,
            },
            "/health": {
                target: process.env.API_PROXY_TARGET || "http://web:80",
                changeOrigin: true,
            },
        },
    },
});
