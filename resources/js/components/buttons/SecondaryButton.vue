<script setup lang="ts">
defineOptions({
    inheritAttrs: false,
});

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
