<script setup lang="ts">
import EventController from '@/actions/App/Http/Controllers/EventController';
import EventCard from '@/components/events/EventCard.vue';
import ActionModal from '@/components/modals/ActionModal.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import type { Action } from '@/types/Button';
import type { Event } from '@/types/Event';
import { Head } from '@inertiajs/vue3';
import type { TableColumn } from '@nuxt/ui';
import { h } from 'vue';

const props = defineProps<{
    deletedEvents: Event[];
}>();

const getRestoreAction = (event: Event): Action => ({
    title: 'Obnovit event',
    titleShort: 'Obnovit',
    url: EventController.restore(event).url,
    method: 'post',
    icon: {
        name: 'i-lucide-undo-2',
        class: 'size-6',
    },
});

const columns: TableColumn<Event>[] = [
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
        id: 'date',
        header: 'Datum',
        size: 120,
        cell: ({ row }) => {
            return h(
                'div',
                { class: 'min-w-[120px]' },
                h('span', row.original.date.label),
            );
        },
    },
    {
        id: 'time',
        header: 'Čas',
        size: 100,
        cell: ({ row }) => {
            if (row.original.date.is_all_day) {
                return h(
                    'div',
                    { class: 'min-w-[100px]' },
                    h(
                        'span',
                        { class: 'text-gray-600 dark:text-gray-400' },
                        'Celý den',
                    ),
                );
            }
            const timeText = row.original.date.end_time
                ? `${row.original.date.start_time} - ${row.original.date.end_time}`
                : row.original.date.start_time;
            return h('div', { class: 'min-w-[100px]' }, h('span', timeText));
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
                                h(EventCard, {
                                    event: row.original,
                                    view: 'detail',
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
    <Head title="Smazané eventy" />

    <AuthenticatedLayout :display-floating-action-button="false">
        <div class="space-y-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                Smazané eventy
            </h2>

            <UCard :ui="{ body: { padding: '' } }">
                <div class="overflow-x-auto">
                    <UTable
                        :data="props.deletedEvents"
                        :columns="columns"
                        class="w-full"
                    />
                </div>
            </UCard>
        </div>
    </AuthenticatedLayout>
</template>
