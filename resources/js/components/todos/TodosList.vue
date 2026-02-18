<script setup lang="ts">
import TodoController from '@/actions/App/Http/Controllers/TodoController';
import TodoCard from '@/components/todos/TodoCard.vue';
import Nothing from '@/components/Nothing.vue';
import type { Todo } from '@/types/Todo';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    heading: string;
    todos: Todo[];
    createTodoIfEmpty?: boolean;
    showFinishButton?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    createTodoIfEmpty: false,
    showFinishButton: false,
});

const button = props.createTodoIfEmpty
    ? { to: TodoController.create(), label: 'Přidat todo' }
    : undefined;

type GroupedTodos = Record<string, Todo[]>;

const groupedTodos = computed(() => {
    return props.todos.reduce((acc, todo) => {
        const key = todo.deadline.key;

        if (!acc[key]) {
            acc[key] = [];
        }
        acc[key].push(todo);

        return acc;
    }, {} as GroupedTodos);
});
</script>

<template>
    <h2 class="mt-4">{{ props.heading }}</h2>

    <div v-if="props.todos.length" class="space-y-4">
        <section
            v-for="(todos, deadlineKey) in groupedTodos"
            :key="deadlineKey"
        >
            <h3 class="lowercase">
                {{ todos[0].deadline.label }}
            </h3>

            <div class="grid grid-cols-1 gap-4">
                <Link
                    :href="TodoController.show(todo)"
                    v-for="todo in todos"
                    :key="todo.id"
                >
                    <TodoCard
                        :todo="todo"
                        :show-finish-button="props.showFinishButton"
                    />
                </Link>
            </div>
        </section>
    </div>

    <Nothing v-if="!props.todos.length" :button="button" />
</template>
