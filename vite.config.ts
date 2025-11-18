import ui from '@nuxt/ui/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        ui({
            inertia: true,
            ui: {
                colors: {
                    primary: 'pink',
                    secondary: 'sky',
                    neutral: 'slate',
                },

                button: {
                    base: 'cursor-pointer',
                },

                switch: {
                    defaultVariants: {
                        size: 'xl',
                    },
                },

                input: {
                    defaultVariants: {
                        size: 'xl',
                    },
                },

                select: {
                    defaultVariants: {
                        size: 'xl',
                    },
                },
            },
            colorMode: false,
        }),
    ],
});
