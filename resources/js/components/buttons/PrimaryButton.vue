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
        color="primary"
        class="justify-center"
        v-bind="$attrs"
        :loading="loading"
    >
        <slot />
    </UButton>
</template>
