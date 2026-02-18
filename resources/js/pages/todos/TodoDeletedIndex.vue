<script setup lang="ts">
import TodoController from '@/actions/App/Http/Controllers/TodoController';
import TodoCard from '@/components/todos/TodoCard.vue';
import ActionModal from '@/components/modals/ActionModal.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import type { Action } from '@/types/Button';
import type { Todo } from '@/types/Todo';
import { Head } from '@inertiajs/vue3';
import type { TableColumn } from '@nuxt/ui';
import { h } from 'vue';

const props = defineProps<{
    deletedTodos: Todo[];
}>();

const getRestoreAction = (todo: Todo): Action => ({
    title: 'Obnovit todo',
    titleShort: 'Obnovit',
    url: TodoController.restore(todo).url,
    method: 'post',
    icon: {
        name: 'i-lucide-undo-2',
        class: 'size-6',
    },
});

const columns: TableColumn<Todo>[] = [
    {
        id: 'capybara',
        header: 'Kdo',
        size: 60,
        cell: ({ row }) =>
            h(
                'div',
                { class: 'flex items-center justify-center min-w-[60px]' },
                [
                    h('img', {
                        src: row.original.capybara.avatar.src,
                        alt: row.original.capybara.avatar.alt,
                        class: 'h-10 w-10 rounded-md object-cover object-center shrink-0',
                    }),
                ],
            ),
    },
    {
        accessorKey: 'title',
        header: 'Název',
        size: 150,
        cell: ({ row }) =>
            h('div', { class: 'min-w-[150px]' }, row.original.title),
    },
    {
        id: 'deadline',
        header: 'Deadline',
        size: 120,
        cell: ({ row }) => {
            return h(
                'div',
                { class: 'min-w-[120px]' },
                h('span', row.original.deadline.label),
            );
        },
    },
    {
        id: 'priority',
        header: 'Priorita',
        size: 100,
        cell: ({ row }) => {
            return h('div', { class: 'min-w-[100px] flex items-center gap-1' }, [
                h('span', {
                    class: row.original.priority.icon_color,
                    innerHTML: '●',
                }),
                h('span', row.original.priority.label),
            ]);
        },
    },
    {
        id: 'actions',
        header: 'Obnovit',
        size: 80,
        cell: ({ row }) => {
            return h(
                'div',
                { class: 'flex items-center justify-center min-w-[80px]' },
                [
                    h(
                        ActionModal,
                        {
                            action: getRestoreAction(row.original),
                        },
                        {
                            body: () =>
                                h(TodoCard, {
                                    todo: row.original,
                                }),
                        },
                    ),
                ],
            );
        },
    },
];
</script>

<template>
    <Head title="Smazaná todos" />

    <AuthenticatedLayout :display-floating-action-button="false">
        <div class="space-y-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                Smazaná todos
            </h2>

            <UCard :ui="{ body: { padding: '' } }">
                <div class="overflow-x-auto">
                    <UTable
                        :data="props.deletedTodos"
                        :columns="columns"
                        class="w-full"
                    />
                </div>
            </UCard>
        </div>
    </AuthenticatedLayout>
</template>
