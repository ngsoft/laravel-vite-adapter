import { defineConfig, type PluginOption, type UserConfig } from 'vite';
import { fileURLToPath, URL } from 'node:url';
import laravel from 'laravel-vite-plugin';

// https://vite.dev/config/
export default defineConfig({
    plugins: [
        laravel({
            input: ['example/app.ts'],
            publicDirectory: 'public',
            buildDirectory: 'public/build',
            refresh: true,
        }),
    ],
    // build: {
    //     target: 'esnext',
    //     chunkSizeWarningLimit: 1024,
    //     rollupOptions: { input: { app: './app/main.ts' } },
    // },
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./example', import.meta.url)),
            $lib: fileURLToPath(new URL('./example/lib', import.meta.url)),
        },
        conditions: ['browser'],
    },
    server: { cors: true },
    publicDir: 'public',
});
