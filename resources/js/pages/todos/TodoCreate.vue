<script setup lang="ts">
import TodoController from '@/actions/App/Http/Controllers/TodoController';
import TodoForm from '@/components/todos/TodoForm.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import { Capybara } from '@/types/Capybara';
import type { TodoFormData } from '@/types/TodoFormData';
import type { TodoPriority } from '@/types/Todo';
import { Tag } from '@/types/Tag';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();

const props = defineProps<{
    capybaraOptions: Capybara[];
    priorityOptions: TodoPriority[];
    availableTags: Tag[];
}>();

const form = useForm<TodoFormData>({
    title: '',
    capybara: page.props.auth.user.capybara,
    deadline: '',
    priority: 'medium',
    is_private: false,
    description: '',
    tags: [],
    image: null,
    remove_image: false,
});

function submit() {
    form.post(TodoController.store.url(), {
        forceFormData: true,
    });
}
</script>

<template>
    <Head title="Přidat todo" />
    <AuthenticatedLayout :display-floating-action-button="false">
        <h2>Přidat todo</h2>
        <TodoForm
            :form="form"
            :is-edit-mode="false"
            :capybara-options="props.capybaraOptions"
            :priority-options="props.priorityOptions"
            @submit="submit"
            :available-tags="props.availableTags"
        />
    </AuthenticatedLayout>
</template>
