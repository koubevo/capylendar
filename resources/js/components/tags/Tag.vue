<script setup lang="ts">
import { Tag } from '@/types/Tag';

interface Props {
    tag: Tag;
}

const props = defineProps<Props>();
</script>

<template>
    <UBadge :style="{ backgroundColor: props.tag.color }" size="sm">
        {{ props.tag.label }}
    </UBadge>
</template>
