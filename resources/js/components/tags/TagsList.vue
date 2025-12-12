<script setup lang="ts">
import Tag from '@/components/tags/Tag.vue';

interface Props {
    tags: Tag[];
}

const props = defineProps<Props>();
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <Tag :tag="tag" v-for="tag in props.tags" :key="tag.id" />
    </div>
</template>
