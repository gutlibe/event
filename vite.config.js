import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [tailwindcss()],
    publicDir: false,
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: 'resources/js/app.js',
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        allowedHosts: true,
        watch: {
            ignored: ['**/storage/**', '**/public/build/**', '**/node_modules/**'],
        },
    },
});
