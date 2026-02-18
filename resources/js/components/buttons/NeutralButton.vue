<script setup lang="ts">
defineProps<{
    loading?: boolean;
}>();
</script>

<template>
    <UButton
        color="neutral"
        variant="outline"
        v-bind="$attrs"
        :loading="loading"
    >
        <slot />
    </UButton>
</template>
