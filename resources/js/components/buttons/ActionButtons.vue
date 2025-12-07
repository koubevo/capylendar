<script setup lang="ts">
import DeleteModal from '@/components/modals/DeleteModal.vue';
import type { DeleteAction, DuplicateAction, EditAction } from '@/types/Button';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    duplicateAction?: DuplicateAction;
    editAction?: EditAction;
    deleteAction?: DeleteAction;
    class: string;
}>();
</script>

<template>
    <UCard :class="props.class">
        <div class="flex flex-row items-center justify-around">
            <Link
                v-if="props.duplicateAction"
                :href="props.duplicateAction.url"
            >
                <UIcon
                    :name="props.duplicateAction.icon ?? 'i-lucide-copy-plus'"
                    class="size-6"
                />
            </Link>
            <Link v-if="props.editAction" :href="props.editAction.url">
                <UIcon
                    :name="props.editAction.icon ?? 'i-lucide-square-pen'"
                    class="size-6"
                />
            </Link>
            <DeleteModal :delete="props.deleteAction">
                <template #body>
                    <slot name="delete-modal-body" />
                </template>
            </DeleteModal>
        </div>
    </UCard>
</template>
