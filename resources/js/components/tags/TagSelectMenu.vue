<script setup lang="ts">
import TagComponent from '@/components/tags/Tag.vue';
import { Tag } from '@/types/Tag';
import { computed } from 'vue';

const props = defineProps<{
    modelValue: number[];
    items: Tag[];
    placeholder?: string;
    searchInputPlaceholder?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: number[]): void;
}>();

const selected = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const tagsMap = computed(
    () => new Map(props.items.map((tag) => [tag.id, tag])),
);
</script>

<template>
    <USelectMenu
        v-model="selected"
        :items="props.items"
        multiple
        value-key="id"
        :placeholder="props.placeholder"
        :search-input="{ placeholder: props.searchInputPlaceholder }"
        class="w-full"
        :ui="{ base: 'p-0' }"
    >
        <template #default>
            <div
                class="flex h-10 w-full cursor-pointer items-center justify-between gap-2 px-3 py-0"
            >
                <div v-if="selected.length" class="flex flex-wrap gap-1">
                    <TagComponent
                        v-for="tagId in selected"
                        :key="tagId"
                        size="xs"
                        :tag="tagsMap.get(tagId)!"
                    />
                </div>
                <span v-else class="truncate text-gray-500">{{
                    props.placeholder
                }}</span>
            </div>
        </template>

        <template #item="{ item }">
            <div
                class="flex flex-1 items-center justify-between gap-2 rounded px-2.5 py-1.5 transition-colors"
                :class="
                    selected.includes(item.id)
                        ? 'bg-primary-50 dark:bg-primary-900/20'
                        : ''
                "
            >
                <TagComponent :tag="item" />
                <UIcon
                    v-if="selected.includes(item.id)"
                    name="i-lucide-check"
                    class="size-4 text-primary-500"
                />
            </div>
        </template>
    </USelectMenu>
</template>
