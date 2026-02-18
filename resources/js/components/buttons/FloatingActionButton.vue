<script setup lang="ts">
import PrimaryButton from '@/components/buttons/PrimaryButton.vue';
import { To } from '@/types/Button';

interface Props {
    to: To;
    ariaLabel: string;
}

const props = defineProps<Props>();
</script>

<template>
    <PrimaryButton
        :to="props.to"
        icon="i-lucide-plus"
        size="xl"
        class="fixed right-6 bottom-6 z-50 flex h-14 w-14 items-center justify-center rounded-lg shadow-xl md:hidden"
        :aria-label="props.ariaLabel"
    />
</template>
