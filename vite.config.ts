import ui from '@nuxt/ui/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import { defineConfig, loadEnv } from 'vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    return {
        resolve: {
            alias: {
                '@': path.resolve(__dirname, './resources/js'),
            },
        },
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
                        success: 'teal',
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
                    selectMenu: {
                        defaultVariants: {
                            size: 'xl',
                        },
                    },
                },
                colorMode: false,
            }),
        ],
        server: {
            host: '0.0.0.0',
            cors: true,
            hmr: {
                host: env.VITE_HMR_HOST || 'localhost',
            },
        },
    };
});
