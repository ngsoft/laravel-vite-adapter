import { defineConfig } from 'vite';
import { fileURLToPath, URL } from 'node:url';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

// https://vite.dev/config/
export default defineConfig({
    plugins: [
        laravel({
            input: ['example/app.ts'],
            publicDirectory: 'public',
            buildDirectory: 'build',
            refresh: true,
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./example', import.meta.url)),
            $lib: fileURLToPath(new URL('./example/lib', import.meta.url)),
        },
        conditions: ['browser'],
    },
    server: { cors: true },
});
