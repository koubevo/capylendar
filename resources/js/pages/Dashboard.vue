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
import { computed } from 'vue';

//TODO: pinia
interface Props {
    upcomingEvents: Event[];
    historyEvents: Event[];
    eventFilters: EventFilters;
    capybaraOptions: Capybara[];
    availableTags: Tag[];
    scrollToDate?: string;
}

const props = defineProps<Props>();

const handleFilterChange = (newFilters: typeof props.eventFilters) => {
    router.get(DashboardController(), newFilters as any, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['upcomingEvents', 'historyEvents', 'eventFilters'],
    });
};

const clearScrollToDate = () => {
    if (props.scrollToDate) {
        const url = new URL(window.location.href);
        url.searchParams.delete('scrollToDate');
        window.history.replaceState({}, '', url.toString());
    }
};

const activeEventFiltersCount = computed(() => {
    const count = Object.values(props.eventFilters).filter(Boolean).length;
    return count > 0 ? count : null;
});

const eventFiltersLabel = computed(() => {
    return activeEventFiltersCount.value
        ? 'Filtrování (' + activeEventFiltersCount.value + ')'
        : 'Filtrování';
});

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
                    :label="eventFiltersLabel"
                    color="primary"
                    variant="subtle"
                    trailing-icon="i-lucide-chevron-down"
                    block
                />

            <template #content>
                <EventFilterForm
                    :eventFilters="props.eventFilters"
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
                    :scroll-to-date="props.scrollToDate"
                    @scrolled="clearScrollToDate"
                />
            </template>

            <template #history>
                <EventsList
                    heading="Historické"
                    :events="props.historyEvents"
                    :create-event-if-empty="true"
                    :scroll-to-date="props.scrollToDate"
                    @scrolled="clearScrollToDate"
                />
            </template>
        </UTabs>
    </AuthenticatedLayout>
</template>
