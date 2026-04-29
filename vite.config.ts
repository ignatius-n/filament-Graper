import { defineConfig } from 'vite';

export default defineConfig({
    build: {
        lib: {
            entry: 'resources/js/index.ts',
            name: 'GraperEditor',
            fileName: 'index',
            formats: ['iife'],
        },
        rollupOptions: {
            external: [],
            output: {
                dir: 'dist/grapesjs',
                entryFileNames: '[name].js',
                chunkFileNames: '[name].js',
            },
        },
    },
});
