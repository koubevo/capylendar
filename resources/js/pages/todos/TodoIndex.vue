<script setup lang="ts">
import TodosList from '@/components/todos/TodosList.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import type { Todo } from '@/types/Todo';
import { Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface Props {
    unfinishedTodos: Todo[];
    finishedTodos: Todo[];
}

const props = defineProps<Props>();

const localUnfinished = ref<Todo[]>([...props.unfinishedTodos]);
const localFinished = ref<Todo[]>([...props.finishedTodos]);

// Sync when Inertia refreshes props (page navigation)
watch(
    () => props.unfinishedTodos,
    (newVal) => {
        localUnfinished.value = [...newVal];
    },
);
watch(
    () => props.finishedTodos,
    (newVal) => {
        localFinished.value = [...newVal];
    },
);

function handleToggled(todoId: number) {
    // Check if it's in unfinished
    const unfinishedIdx = localUnfinished.value.findIndex(
        (t) => t.id === todoId,
    );
    if (unfinishedIdx !== -1) {
        const todo = localUnfinished.value[unfinishedIdx];
        todo.is_finished = true;
        return;
    }

    // Check if it's in finished
    const finishedIdx = localFinished.value.findIndex((t) => t.id === todoId);
    if (finishedIdx !== -1) {
        const todo = localFinished.value[finishedIdx];
        todo.is_finished = false;
        return;
    }
}

function handleTabChange() {
    // Move finished items from unfinished list to finished list
    const nowFinished = localUnfinished.value.filter((t) => t.is_finished);
    if (nowFinished.length > 0) {
        localUnfinished.value = localUnfinished.value.filter(
            (t) => !t.is_finished,
        );
        localFinished.value = [...nowFinished, ...localFinished.value];
    }

    // Move unfinished items from finished list to unfinished list
    const nowUnfinished = localFinished.value.filter((t) => !t.is_finished);
    if (nowUnfinished.length > 0) {
        localFinished.value = localFinished.value.filter((t) => t.is_finished);
        localUnfinished.value = [...localUnfinished.value, ...nowUnfinished];
    }
}

const items = [
    {
        label: 'Nesplněné',
        icon: 'i-lucide-circle-dashed',
        slot: 'unfinished',
    },
    {
        label: 'Splněné',
        icon: 'i-lucide-circle-check-big',
        slot: 'finished',
    },
];
</script>

<template>
    <Head title="Todos" />
    <AuthenticatedLayout>
        <UTabs :items="items" @update:model-value="handleTabChange">
            <template #unfinished>
                <TodosList
                    heading="Nesplněné"
                    :todos="localUnfinished"
                    :create-todo-if-empty="true"
                    :show-finish-button="true"
                    @toggled="handleToggled"
                />
            </template>

            <template #finished>
                <TodosList
                    heading="Splněné"
                    :todos="localFinished"
                    :show-finish-button="true"
                    @toggled="handleToggled"
                />
            </template>
        </UTabs>
    </AuthenticatedLayout>
</template>
