<script setup lang="ts">
import { historyIndex } from '@/actions/App/Http/Controllers/DashboardController';
import EventFilterForm from '@/components/events/EventFilterForm.vue';
import EventsList from '@/components/events/EventsList.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import { Capybara } from '@/types/Capybara';
import type { Event } from '@/types/Event';
import { EventFilters } from '@/types/Filters';
import { Tag } from '@/types/Tag';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    historyEvents: Event[];
    eventFilters: EventFilters;
    capybaraOptions: Capybara[];
    availableTags: Tag[];
}

const props = defineProps<Props>();

const handleFilterChange = (newFilters: typeof props.eventFilters) => {
    router.get(historyIndex().url, newFilters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['historyEvents', 'eventFilters'],
    });
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
</script>

<template>
    <Head title="Historické eventy" />
    <AuthenticatedLayout>
        <UCollapsible class="mb-4 flex w-full flex-col gap-2">
            <UButton
                :label="eventFiltersLabel"
                variant="soft"
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

        <EventsList heading="Historické eventy" :events="props.historyEvents" />
    </AuthenticatedLayout>
</template>
