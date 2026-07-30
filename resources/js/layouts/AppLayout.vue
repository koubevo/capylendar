<script lang="ts">
let lastSuccessMessage: string | undefined;
let lastErrorMessage: string | undefined;
</script>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { useToast } from '@nuxt/ui/composables';
import { watch } from 'vue';

const toast = useToast();
interface FlashMessages {
    success?: string;
    error?: string;
}

const page = usePage();

watch(
    () => page.props.flash as FlashMessages | undefined,
    (flash) => {
        if (!flash) return;

        if (flash.success && flash.success !== lastSuccessMessage) {
            toast.add({
                title: 'Jupí',
                description: flash.success,
                color: 'primary',
                icon: 'i-lucide-check-circle',
            });
        }

        if (flash.error && flash.error !== lastErrorMessage) {
            toast.add({
                title: 'Chyba',
                description: flash.error,
                color: 'error',
                icon: 'i-lucide-alert-circle',
            });
        }

        lastSuccessMessage = flash.success;
        lastErrorMessage = flash.error;
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <UApp>
        <slot />
    </UApp>
</template>
