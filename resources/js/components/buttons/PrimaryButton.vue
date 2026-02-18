<script setup lang="ts">
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
