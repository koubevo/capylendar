<script setup lang="ts">
import DocumentController from '@/actions/App/Http/Controllers/DocumentController';
import DocumentForm from '@/components/documents/DocumentForm.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import type { DocumentFormData } from '@/types/DocumentFormData';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm<DocumentFormData>({
    title: '',
    body: '',
});

function submit() {
    form.post(DocumentController.store.url());
}
</script>

<template>
    <Head title="Pridat dokument" />
    <AuthenticatedLayout :display-floating-action-button="false">
        <div class="flex flex-col gap-y-5">
            <h2>Pridat dokument</h2>
            <DocumentForm :form="form" :is-edit-mode="false" @submit="submit" />
        </div>
    </AuthenticatedLayout>
</template>
