<script setup lang="ts">
import EventController from '@/actions/App/Http/Controllers/EventController';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import ActionModal from '@/components/modals/ActionModal.vue';
import EventCard from '@/components/events/EventCard.vue';
import type { Event } from '@/types/Event';
import type { Action } from '@/types/Button';
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
        cell: ({ row }) =>
            h('img', {
                src: row.original.capybara.avatar.src,
                alt: row.original.capybara.avatar.alt,
                class: 'h-10 w-10 rounded-md',
            }),
    },
    {
        accessorKey: 'title',
        header: 'Název',
        cell: ({ row }) => row.original.title,
    },
    {
        id: 'date',
        header: 'Datum',
        cell: ({ row }) => {
            return h('span', row.original.date.label);
        },
    },
    {
        id: 'time',
        header: 'Čas',
        cell: ({ row }) => {
            if (row.original.date.is_all_day) {
                return h('span', { class: 'text-gray-600 dark:text-gray-400' }, 'Celý den');
            }
            const timeText = row.original.date.end_time 
                ? `${row.original.date.start_time} - ${row.original.date.end_time}`
                : row.original.date.start_time;
            return h('span', timeText);
        },
    },
    {
        id: 'actions',
        header: 'Obnovit',
        cell: ({ row }) => {
            return h(ActionModal, {
                action: getRestoreAction(row.original),
            }, {
                body: () => h(EventCard, { event: row.original }),
            });
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
                <UTable
                    :data="props.deletedEvents"
                    :columns="columns"
                    class="w-full"
                />
            </UCard>
        </div>
    </AuthenticatedLayout>
</template>
