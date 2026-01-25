<script setup lang="ts">
import { Action } from '@/types/Button';
import { router } from '@inertiajs/vue3';

const props = defineProps<{
    action: Action;
}>();

const handleAction = (close: () => void) => {
    const method = props.action.method || 'delete';
    router[method](
        props.action.url,
        {},
        {
            onSuccess: () => {
                close();
            },
        },
    );
};
</script>

<template>
    <UModal
        :title="props.action.title"
        :ui="{ footer: 'justify-end', title: 'm-0' }"
    >
        <button class="cursor-pointer">
            <UIcon
                :name="props.action.icon?.name ?? 'i-lucide-square-x'"
                :class="props.action.icon?.class ?? 'size-6'"
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
                :label="props.action.titleShort ?? 'Smazat'"
                color="primary"
                @click="handleAction(close)"
            />
        </template>
    </UModal>
</template>
