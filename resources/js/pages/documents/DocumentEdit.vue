<script setup lang="ts">
import DocumentController from '@/actions/App/Http/Controllers/DocumentController';
import DocumentForm from '@/components/documents/DocumentForm.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import type { Document } from '@/types/Document';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    document: Document;
}>();
</script>

<template>
    <Head :title="`Upravit ${props.document.title}`" />
    <AuthenticatedLayout :display-floating-action-button="false">
        <div class="flex flex-col gap-y-5">
            <h2>Upravit dokument</h2>
            <DocumentForm
                :initial-title="props.document.title"
                :initial-body="props.document.body"
                :is-edit-mode="true"
                :submit-url="DocumentController.update.url(props.document)"
                submit-method="put"
            />
        </div>
    </AuthenticatedLayout>
</template>
