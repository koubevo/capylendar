<script setup lang="ts">
import { DeleteAction } from '@/types/Button';

const props = defineProps<{
    deleteAction: DeleteAction;
}>();
</script>

<template>
    <UModal
        :title="props.deleteAction.title"
        :ui="{ footer: 'justify-end', title: 'm-0' }"
    >
        <button class="cursor-pointer">
            <UIcon
                :name="props.deleteAction.icon ?? 'i-lucide-square-x'"
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
            <UButton
                label="Smazat"
                color="primary"
                :to="props.deleteAction.url"
            />
        </template>
    </UModal>
</template>
