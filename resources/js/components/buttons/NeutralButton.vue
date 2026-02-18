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
        color="neutral"
        variant="outline"
        v-bind="$attrs"
        :loading="loading"
    >
        <slot />
    </UButton>
</template>
