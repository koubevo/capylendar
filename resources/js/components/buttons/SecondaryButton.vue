<script setup lang="ts">
defineProps<{
    loading?: boolean;
}>();
</script>

<template>
    <UButton
        color="secondary"
        variant="subtle"
        v-bind="$attrs"
        :loading="loading"
    >
        <slot />
    </UButton>
</template>
