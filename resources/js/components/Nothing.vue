<script setup lang="ts">
import PrimaryButton from '@/components/buttons/PrimaryButton.vue';
import type { Button } from '@/types/Button';

const props = defineProps<{
    button: Button | undefined;
}>();
</script>

<template>
    <div class="flex w-full flex-col items-center justify-center">
        <img
            src="/images/capys/nothing.jpg"
            alt="Nothing here"
            class="w-full md:w-100"
        />
        <span class="component-alert-text"> Nic tady není! </span>
        <PrimaryButton v-if="props.button" :to="props.button.to" class="mt-4">{{
            props.button.label
        }}</PrimaryButton>
    </div>
</template>
