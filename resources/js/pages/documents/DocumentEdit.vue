<script setup lang="ts">
import DocumentController from '@/actions/App/Http/Controllers/DocumentController';
import DocumentForm from '@/components/documents/DocumentForm.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import type { Document } from '@/types/Document';
import type { DocumentFormData } from '@/types/DocumentFormData';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    document: Document;
}>();

const form = useForm<DocumentFormData>({
    title: props.document.title,
    body: props.document.body,
});

function submit() {
    form.put(DocumentController.update.url(props.document));
}
</script>

<template>
    <Head :title="`Upravit ${props.document.title}`" />
    <AuthenticatedLayout :display-floating-action-button="false">
        <div class="flex flex-col gap-y-5">
            <h2>Upravit dokument</h2>
            <DocumentForm :form="form" :is-edit-mode="true" @submit="submit" />
        </div>
    </AuthenticatedLayout>
</template>
