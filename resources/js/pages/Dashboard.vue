<script setup lang="ts">
import EventsList from '@/components/events/EventsList.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import type { Event } from '@/types/Event';

//TODO: pinia
interface Props {
    upcomingEvents: Event[];
    historyEvents: Event[];
}

const props = defineProps<Props>();

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
