import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

const fixInvalidPureAnnotations = (): import('vite').Plugin => ({
    name: 'fix-invalid-pure-annotations',
    transform(code, id) {
        if (id.includes('@vueuse/core')) {
            return code.replace(/\/\* #__PURE__ \*\//g, '');
        }
    },
});

export default defineConfig({
    plugins: [
        fixInvalidPureAnnotations(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
    ],
    optimizeDeps: {
        exclude: ['reka-ui'],
    },
});
