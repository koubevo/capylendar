<script setup lang="ts">
const props = defineProps<{
    to: string | null;
}>();
</script>

<template>
    <div class="flex w-full flex-col items-center justify-center">
        <img
            src="/images/capys/nothing.jpg"
            alt="Construction"
            class="w-full md:w-100"
        />
        <span class="component-alert-text"> Nic tady není! </span>
        <UButton :to="props.to" class="mt-4">Přidat</UButton>
    </div>
</template>
