<script setup lang="ts">
import TodoController from '@/actions/App/Http/Controllers/TodoController';
import TodoForm from '@/components/todos/TodoForm.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import { Capybara } from '@/types/Capybara';
import { Tag } from '@/types/Tag';
import type { Todo, TodoPriority } from '@/types/Todo';
import type { TodoFormData } from '@/types/TodoFormData';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    capybaraOptions: Capybara[];
    priorityOptions: TodoPriority[];
    todo: Todo;
    availableTags: Tag[];
}>();

const form = useForm<TodoFormData>({
    title: props.todo.title,
    capybara: props.todo.capybara.value,
    deadline: props.todo.deadline.key,
    priority: props.todo.priority.value,
    is_private: props.todo.is_private,
    description: props.todo.description || '',
    tags: props.todo.tags ? props.todo.tags.map((t) => t.id) : [],
});

function submit() {
    form.put(TodoController.update.url(props.todo));
}
</script>

<template>
    <Head title="Upravit todo" />
    <AuthenticatedLayout :display-floating-action-button="false">
        <h2>Upravit todo</h2>
        <TodoForm
            :form="form"
            :is-edit-mode="true"
            :capybara-options="props.capybaraOptions"
            :priority-options="props.priorityOptions"
            @submit="submit"
            :available-tags="props.availableTags"
        />
    </AuthenticatedLayout>
</template>
