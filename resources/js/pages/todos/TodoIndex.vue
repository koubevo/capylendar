<script setup lang="ts">
import TodosList from '@/components/todos/TodosList.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import type { Todo } from '@/types/Todo';
import { Head } from '@inertiajs/vue3';

interface Props {
    unfinishedTodos: Todo[];
    finishedTodos: Todo[];
}

const props = defineProps<Props>();

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
        <UTabs :items="items">
            <template #unfinished>
                <TodosList
                    heading="Nesplněné"
                    :todos="props.unfinishedTodos"
                    :create-todo-if-empty="true"
                    :show-finish-button="true"
                />
            </template>

            <template #finished>
                <TodosList
                    heading="Splněné"
                    :todos="props.finishedTodos"
                    :show-finish-button="true"
                />
            </template>
        </UTabs>
    </AuthenticatedLayout>
</template>
