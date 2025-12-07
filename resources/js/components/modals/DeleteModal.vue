<script setup lang="ts">
import { DeleteAction } from '@/types/Button';

const props = defineProps<{
    delete: DeleteAction;
}>();
</script>

<template>
    <UModal
        v-model:open="open"
        :title="props.delete.title"
        :ui="{ footer: 'justify-end', title: 'm-0' }"
    >
        <button class="cursor-pointer">
            <UIcon
                :name="props.delete.icon ?? 'i-lucide-square-x'"
                class="size-6"
            />
        </button>

        <template #body>
            <slot name="body" />
        </template>

        <template #footer="{ close }">
            <UButton
                label="Zrušit"
                color="neutral"
                variant="outline"
                @click="close"
            />
            <UButton label="Smazat" color="primary" :to="props.delete.url" />
        </template>
    </UModal>
</template>
