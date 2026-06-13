<script setup lang="ts">
import DocumentController from '@/actions/App/Http/Controllers/DocumentController';
import ActionButtons from '@/components/buttons/ActionButtons.vue';
import DocumentCard from '@/components/documents/DocumentCard.vue';
import MarkdownPreview from '@/components/documents/MarkdownPreview.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import type { Document } from '@/types/Document';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    document: Document;
}>();
</script>

<template>
    <Head :title="props.document.title" />
    <AuthenticatedLayout>
        <div class="flex flex-col gap-y-4">
            <section class="flex flex-col gap-y-2">
                <div class="flex items-start justify-between gap-3">
                    <h2>{{ props.document.title }}</h2>
                    <UIcon
                        name="i-lucide-file-text"
                        class="mt-1 size-6 shrink-0 text-primary"
                    />
                </div>
                <div
                    class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-neutral-500 dark:text-neutral-400"
                >
                    <span>{{ props.document.author.name }}</span>
                    <span>{{ props.document.updated_at_human }}</span>
                </div>
            </section>

            <ActionButtons
                :edit-action="{
                    url: DocumentController.edit.url(props.document),
                }"
                :delete-action="{
                    url: DocumentController.destroy.url(props.document),
                    title: 'Smazat dokument',
                    titleShort: 'Smazat',
                }"
                :share-url="DocumentController.show.url(props.document)"
                class="border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900"
            >
                <template #event-modal-body>
                    <DocumentCard :document="props.document" />
                </template>
            </ActionButtons>

            <section
                class="rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900"
            >
                <MarkdownPreview :content="props.document.body" />
            </section>
        </div>
    </AuthenticatedLayout>
</template>
