<script setup lang="ts">
import DashboardController from '@/actions/App/Http/Controllers/DashboardController';
import EventFilterForm from '@/components/events/EventFilterForm.vue';
import EventsList from '@/components/events/EventsList.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import { Capybara } from '@/types/Capybara';
import type { Event } from '@/types/Event';
import { EventFilters } from '@/types/Filters';
import { Tag } from '@/types/Tag';
import { router } from '@inertiajs/vue3';

//TODO: pinia
interface Props {
    upcomingEvents: Event[];
    historyEvents: Event[];
    eventFilters: EventFilters;
    capybaraOptions: Capybara[];
    availableTags: Tag[];
}

const props = defineProps<Props>();

const handleFilterChange = (newFilters: typeof props.filters) => {
    router.get(DashboardController(), newFilters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['upcomingEvents', 'historyEvents', 'filters'],
    });
};

const items = [
    {
        label: 'Nadcházející',
        icon: 'i-lucide-rocket',
        slot: 'upcoming',
    },
    {
        label: 'Historické',
        icon: 'i-lucide-history',
        slot: 'history',
    },
];
</script>

<template>
    <AuthenticatedLayout :display-footer="true">
        <UCollapsible class="mb-4 flex w-full flex-col gap-2">
            <UButton
                label="Filtrování"
                variant="soft"
                trailing-icon="i-lucide-chevron-down"
                block
            />

            <template #content>
                <EventFilterForm
                    :filters="props.filters"
                    :capybara-options="props.capybaraOptions"
                    :available-tags="props.availableTags"
                    @change="handleFilterChange"
                />
            </template>
        </UCollapsible>

        <UTabs :items="items">
            <template #upcoming>
                <EventsList
                    heading="Nadcházející"
                    :events="props.upcomingEvents"
                    :create-event-if-empty="true"
                />
            </template>

            <template #history>
                <EventsList
                    heading="Historické"
                    :events="props.historyEvents"
                    :create-event-if-empty="true"
                />
            </template>
        </UTabs>
    </AuthenticatedLayout>
</template>
