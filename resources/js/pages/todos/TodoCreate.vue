<script setup lang="ts">
import TodoController from '@/actions/App/Http/Controllers/TodoController';
import TodoForm from '@/components/todos/TodoForm.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import { Capybara } from '@/types/Capybara';
import { Tag } from '@/types/Tag';
import type { Todo, TodoPriority } from '@/types/Todo';
import type { TodoFormData } from '@/types/TodoFormData';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const props = defineProps<{
    capybaraOptions: Capybara[];
    priorityOptions: TodoPriority[];
    availableTags: Tag[];
    todo?: Todo;
}>();

const form = useForm<TodoFormData>({
    title: props.todo?.title || '',
    capybara: props.todo?.capybara.value || page.props.auth.user.capybara,
    deadline: props.todo?.deadline.key || '',
    priority: props.todo?.priority.value || 'medium',
    is_private: props.todo?.is_private || false,
    description: props.todo?.description || '',
    tags: props.todo?.tags ? props.todo.tags.map((t) => t.id) : [],
});

const title = computed(() => {
    return props.todo ? 'Duplikovat todo' : 'Přidat todo';
});

function submit() {
    form.post(TodoController.store.url());
}
</script>

<template>
    <Head :title="title" />
    <AuthenticatedLayout :display-floating-action-button="false">
        <h2>{{ title }}</h2>
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
