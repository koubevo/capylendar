<script setup lang="ts">
import ActionModal from '@/components/modals/ActionModal.vue';
import type { Action, DuplicateAction, EditAction } from '@/types/Button';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    duplicateAction?: DuplicateAction;
    postponeAction?: { url: string };
    editAction?: EditAction;
    deleteAction?: Action;
    shareUrl?: string;
    class: string;
}>();

const toast = useToast();

async function handleShare(): Promise<void> {
    if (!props.shareUrl) {
        return;
    }

    const url = new URL(props.shareUrl, window.location.origin).href;

    if (navigator.share) {
        try {
            await navigator.share({ url });
            return;
        } catch (error: unknown) {
            if (error instanceof DOMException && error.name === 'AbortError') {
                return;
            }
        }
    }

    try {
        await navigator.clipboard.writeText(url);
        toast.add({
            title: 'Odkaz zkopírován',
            description: 'Odkaz byl zkopírován do schránky.',
            color: 'primary',
            icon: 'i-lucide-check-circle',
        });
    } catch {
        toast.add({
            title: 'Chyba',
            description: 'Nepodařilo se zkopírovat odkaz.',
            color: 'error',
            icon: 'i-lucide-alert-circle',
        });
    }
}
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
            <Link
                v-if="props.postponeAction"
                :href="props.postponeAction.url"
                method="post"
                as="button"
                aria-label="Přesunout na další den"
                class="cursor-pointer"
            >
                <UIcon name="i-lucide-calendar-arrow-down" class="size-6" />
            </Link>
            <Link v-if="props.editAction" :href="props.editAction.url">
                <UIcon
                    :name="props.editAction.icon ?? 'i-lucide-square-pen'"
                    class="size-6"
                />
            </Link>
            <ActionModal v-if="props.deleteAction" :action="props.deleteAction">
                <template #body>
                    <slot name="event-modal-body" />
                </template>
            </ActionModal>
            <button
                v-if="props.shareUrl"
                type="button"
                aria-label="Sdílet"
                class="cursor-pointer"
                @click="handleShare"
            >
                <UIcon name="i-lucide-share-2" class="size-6" />
            </button>
        </div>
    </UCard>
</template>
