<script setup lang="ts">
import { getContrastingTextColor } from '@/lib/colors';
import { Tag } from '@/types/Tag';
import { computed } from 'vue';

interface Props {
    tag: Tag;
}

const props = defineProps<Props>();
const textColor = computed(() => getContrastingTextColor(props.tag.color));
</script>

<template>
    <UBadge
        :style="{
            backgroundColor: props.tag.color,
            color: textColor,
        }"
        size="sm"
        class="relative overflow-hidden transition-all"
        :class="$slots['delete-button'] ? 'cursor-pointer pr-7' : ''"
    >
        {{ props.tag.label }}

        <UIcon
            v-if="$slots['delete-button']"
            name="i-lucide-x"
            class="absolute top-1/2 right-1.5 size-3.5 -translate-y-1/2 opacity-70"
        />

        <div v-if="$slots['delete-button']" class="absolute inset-0 z-10">
            <slot name="delete-button" />
        </div>
    </UBadge>
</template>

<style scoped>
:deep(button) {
    width: 100%;
    height: 100%;
    display: block;
    opacity: 0;
    cursor: pointer;
    margin: 0;
    border: none;
}
</style>
