<script setup lang="ts">
import CountdownCapybaras from '@/components/events/CountdownCapybaras.vue';

withDefaults(
    defineProps<{
        label: string;
        title?: string;
        colorClasses?: string;
    }>(),
    {
        title: undefined,
        colorClasses: undefined,
    },
);
</script>

<template>
    <UCard :class="colorClasses" :ui="{ body: 'p-0 sm:p-0' }">
        <div class="flex items-center justify-between gap-4 px-3 py-2">
            <div class="min-w-0">
                <p v-if="title" class="truncate text-sm font-bold">
                    {{ title }}
                </p>
                <p
                    class="font-bold tabular-nums"
                    :class="title ? 'mt-0.5 text-xs opacity-70' : 'text-lg'"
                >
                    {{ label }}
                </p>
            </div>
            <CountdownCapybaras />
        </div>
    </UCard>
</template>
