<script setup lang="ts">
import TagController from '@/actions/App/Http/Controllers/TagController';
import ActionModal from '@/components/modals/ActionModal.vue';
import Tag from '@/components/tags/Tag.vue';
import type { Tag as TagType } from '@/types/Tag';

interface Props {
    tags: TagType[];
    canDelete?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    canDelete: false,
});
</script>

<template>
    <div class="flex flex-wrap gap-1">
        <div v-for="tag in props.tags" :key="tag.id">
            <Tag :tag="tag">
                <template #delete-button v-if="canDelete">
                    <ActionModal
                        :action="{
                            url: TagController.destroy(tag).url,
                            title: 'Smazat štítek',
                            icon: {
                                name: 'i-lucide-square-x',
                                class: 'size-3',
                            },
                        }"
                    >
                        <template #body>
                            <Tag :tag="tag" />
                        </template>
                    </ActionModal>
                </template>
            </Tag>
        </div>
    </div>
</template>
