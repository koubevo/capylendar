<script setup lang="ts">
import { create, show } from '@/actions/App/Http/Controllers/TodoController';
import Nothing from '@/components/Nothing.vue';
import TodoCard from '@/components/todos/TodoCard.vue';
import type { Todo } from '@/types/Todo';
import { computed } from 'vue';

interface Props {
    heading: string;
    todos: Todo[];
    createTodoIfEmpty?: boolean;
    showFinishButton?: boolean;
    scrollToDate?: string;
    highlightTodo?: number;
    isScrolled?: boolean;
    interactive?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    createTodoIfEmpty: false,
    showFinishButton: false,
    interactive: true,
});

const emit = defineEmits<{
    (e: 'toggled', todoId: number): void;
}>();

const button = props.createTodoIfEmpty
    ? { to: create(), label: 'Přidat todo' }
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
    <div>
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
                    <TodoCard
                        v-for="todo in todos"
                        :key="todo.id"
                        :todo="todo"
                        :href="show.url(todo)"
                        :show-finish-button="props.showFinishButton"
                        :interactive="props.interactive"
                        :class="{
                            'card-highlight':
                                props.isScrolled &&
                                props.highlightTodo === todo.id,
                        }"
                        @toggled="emit('toggled', $event)"
                    />
                </div>
            </section>
        </div>

        <Nothing v-else :button="button" />
    </div>
</template>
