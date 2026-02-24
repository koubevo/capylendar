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
    form.post(TagController.store.url(), {
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
        <div class="mx-auto max-w-7xl py-6 md:py-8 lg:px-8">
            <header class="mb-8">
                <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                    Nastavení štítků
                </h1>
            </header>

            <section class="mb-8">
                <div class="rounded-2xl bg-white p-5 md:p-6 shadow-sm ring-1 ring-inset ring-gray-200 transition-all dark:bg-gray-900 dark:ring-gray-800">
                    <div class="mb-5 flex items-center gap-x-3 text-primary-600 dark:text-primary-400">
                        <UIcon name="i-lucide-plus-circle" class="size-6" />
                        <h2 class="m-0 text-xl font-bold">Přidat štítek</h2>
                    </div>
                    <TagForm :is-edit-mode="false" @submit="submit" :form="form" />
                </div>
            </section>

            <section>
                <div class="rounded-2xl bg-white p-5 md:p-6 shadow-sm ring-1 ring-inset ring-gray-200 transition-all dark:bg-gray-900 dark:ring-gray-800">
                    <div class="mb-5 flex items-center gap-x-3 text-gray-900 dark:text-gray-100">
                        <UIcon name="i-lucide-tags" class="size-6 text-primary-500" />
                        <h2 class="m-0 text-xl font-bold">Existující štítky</h2>
                    </div>
                    
                    <div v-if="props.tags.length > 0">
                        <TagsList :tags="props.tags" :can-delete="true" />
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-10 text-center">
                        <div class="mb-4 rounded-full bg-gray-50 p-4 ring-1 ring-gray-200 dark:bg-gray-800/50 dark:ring-gray-800">
                            <UIcon name="i-lucide-tag" class="size-8 text-gray-400 dark:text-gray-500" />
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Žádné štítky</h3>
                        <p class="mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">
                            Zatím nemáte žádné štítky.
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
