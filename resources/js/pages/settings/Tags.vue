<script setup lang="ts">
import TagController from '@/actions/App/Http/Controllers/TagController';
import TagForm from '@/components/tags/TagForm.vue';
import TagsList from '@/components/tags/TagsList.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import { Tag } from '@/types/Tag';
import { TagFormData } from '@/types/TagFormData';
import { Head, useForm } from '@inertiajs/vue3';

interface Props {
    tags: Tag[];
}

const toast = useToast();
const props = defineProps<Props>();

const form = useForm<TagFormData>({
    label: '',
    color: '',
});

function submit() {
    form.post(TagController.store(), {
        onSuccess: () => {
            form.reset();

            toast.add({
                title: 'Jupí',
                description: 'Štítek byl úspěšně vytvořen',
                color: 'primary',
                icon: 'i-heroicons-check-circle',
            });
        },
    });
}
</script>

<template>
    <Head title="Správa štítků" />

    <AuthenticatedLayout :display-footer="true">
        <section>
            <h2>Přidat štítek</h2>
            <TagForm :is-edit-mode="false" @submit="submit" :form="form" />
        </section>

        <section class="mt-8">
            <h2>Existující štítky</h2>
            <TagsList :tags="props.tags" />
        </section>
    </AuthenticatedLayout>
</template>
