<script setup lang="ts">
import DocumentController from '@/actions/App/Http/Controllers/DocumentController';
import DocumentForm from '@/components/documents/DocumentForm.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Pridat dokument" />
    <AuthenticatedLayout :display-floating-action-button="false">
        <div class="flex flex-col gap-y-5">
            <h2>Pridat dokument</h2>
            <DocumentForm
                :is-edit-mode="false"
                :submit-url="DocumentController.store.url()"
            />
        </div>
    </AuthenticatedLayout>
</template>
