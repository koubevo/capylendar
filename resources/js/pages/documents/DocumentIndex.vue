<script setup lang="ts">
import DocumentController from '@/actions/App/Http/Controllers/DocumentController';
import PrimaryButton from '@/components/buttons/PrimaryButton.vue';
import DocumentCard from '@/components/documents/DocumentCard.vue';
import Nothing from '@/components/Nothing.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import type { Document } from '@/types/Document';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps<{
    documents: Document[];
}>();
</script>

<template>
    <Head title="Dokumenty" />
    <AuthenticatedLayout>
        <div class="flex flex-col gap-y-5">
            <div class="flex items-center justify-between gap-3">
                <h2>Dokumenty</h2>
                <PrimaryButton
                    icon="i-lucide-file-plus"
                    @click="router.visit(DocumentController.create.url())"
                >
                    Pridat
                </PrimaryButton>
            </div>

            <Nothing
                v-if="props.documents.length === 0"
                :button="{
                    label: 'Pridat dokument',
                    to: DocumentController.create(),
                }"
            />

            <div v-else class="grid gap-3 md:grid-cols-2">
                <DocumentCard
                    v-for="document in props.documents"
                    :key="document.id"
                    :document="document"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
