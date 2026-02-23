<script setup lang="ts">
import EventController from '@/actions/App/Http/Controllers/EventController';
import TodoController from '@/actions/App/Http/Controllers/TodoController';
import { router } from '@inertiajs/vue3';

const addMenuItems = [
    [
        {
            label: 'Přidat event',
            icon: 'i-lucide-calendar-plus',
            onSelect: () => router.visit(EventController.create.url()),
        },
        {
            label: 'Přidat todo',
            icon: 'i-lucide-list-todo',
            onSelect: () => router.visit(TodoController.create.url()),
        },
    ],
];
</script>

<template>
    <UDropdownMenu :items="addMenuItems" :content="{ side: 'top' }" :ui="{ itemLeadingIcon: 'size-5', item: 'text-base py-3 px-4 gap-3' }">
        <UButton
            icon="i-lucide-plus"
            size="xl"
            color="primary"
            class="fixed right-6 bottom-6 z-50 flex h-14 w-14 items-center justify-center rounded-lg shadow-xl md:hidden"
            aria-label="Přidat"
        />
    </UDropdownMenu>
</template>
