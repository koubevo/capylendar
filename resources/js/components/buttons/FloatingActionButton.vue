<script setup lang="ts">
import PrimaryButton from '@/components/buttons/PrimaryButton.vue';
import { To } from '@/types/Button';
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
            icon: 'i-lucide-list-plus',
            onSelect: () => router.visit(TodoController.create.url()),
        },
    ],
];
</script>

<template>
    <UDropdownMenu :items="addMenuItems" :content="{ side: 'top' }">
        <UButton
            icon="i-lucide-plus"
            size="xl"
            color="primary"
            class="fixed right-6 bottom-6 z-50 flex h-14 w-14 items-center justify-center rounded-lg shadow-xl md:hidden"
            aria-label="Přidat"
        />
    </UDropdownMenu>
</template>
