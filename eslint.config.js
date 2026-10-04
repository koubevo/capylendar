import prettier from 'eslint-config-prettier/flat';
import vue from 'eslint-plugin-vue';
import { defineConfig } from 'eslint/config';
import tseslint from 'typescript-eslint';

export default defineConfig(
    {
        ignores: [
            'vendor',
            'node_modules',
            'public',
            'bootstrap/ssr',
            'tailwind.config.js',
            'resources/js/components/ui/*',
        ],
    },
    ...tseslint.configs.recommended.map((config) =>
        config.files
            ? { ...config, files: [...config.files, '**/*.vue'] }
            : config,
    ),
    vue.configs['flat/essential'],
    {
        files: ['**/*.vue'],
        rules: {
            'vue/block-lang': [
                'error',
                { script: { lang: ['ts'], allowNoLang: false } },
            ],
        },
        languageOptions: {
            parserOptions: {
                parser: {
                    js: 'espree',
                    jsx: 'espree',
                    ts: tseslint.parser,
                    tsx: tseslint.parser,
                },
                ecmaVersion: 2024,
                ecmaFeatures: { jsx: false },
                extraFileExtensions: ['.vue'],
            },
        },
    },
    {
        rules: {
            'vue/multi-word-component-names': 'off',
            '@typescript-eslint/no-explicit-any': 'off',
        },
    },
    prettier,
);
