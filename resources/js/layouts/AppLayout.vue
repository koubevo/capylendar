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

        if (flash.success) {
            toast.add({
                title: 'Jupí',
                description: flash.success,
                color: 'primary',
                icon: 'i-lucide-check-circle',
            });
        }

        if (flash.error) {
            toast.add({
                title: 'Chyba',
                description: flash.error,
                color: 'error',
                icon: 'i-lucide-alert-circle',
            });
        }
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <UApp>
        <slot />
    </UApp>
</template>
