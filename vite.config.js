import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                    optimizedFallbacks: false,
                }),
            ],
        }),
        tailwindcss(),
    ],
    build: {
        rollupOptions: {
            output: {
                assetFileNames: (assetInfo) => assetInfo.name === 'fonts.css'
                    ? 'assets/fonts.css'
                    : 'assets/[name]-[hash][extname]',
            },
        },
    },
    server: {
        host: '127.0.0.1',
    },
});
