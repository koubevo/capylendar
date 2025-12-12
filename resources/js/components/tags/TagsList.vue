<script setup lang="ts">
import TagController from '@/actions/App/Http/Controllers/TagController';
import DeleteModal from '@/components/modals/DeleteModal.vue';
import Tag from '@/components/tags/Tag.vue';

interface Props {
    tags: Tag[];
    canDelete: boolean;
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
                    <DeleteModal
                        :delete-action="{
                            url: TagController.destroy(tag),
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
                    </DeleteModal>
                </template>
            </Tag>
        </div>
    </div>
</template>
