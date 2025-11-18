<script setup lang="ts">
import EventController from '@/actions/App/Http/Controllers/EventController';
import EventCard from '@/components/events/EventCard.vue';
import Nothing from '@/components/Nothing.vue';
import type { Event } from '@/types/Event';

interface Props {
    heading: string;
    events: Event[];
    createEventIfEmpty?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    createEventIfEmpty: false,
});

const button = props.createEventIfEmpty
    ? { to: EventController.create(), label: 'Přidat event' }
    : undefined;
</script>

<template>
    <h1 class="mt-4">{{ props.heading }}</h1>

    <!-- TODO: grouping by date -->
    <section class="grid grid-cols-1 gap-4">
        <EventCard
            v-for="event in props.events"
            :key="event.id"
            :event="event"
        />
    </section>

    <Nothing v-if="!props.events.length" :button="button" />
</template>

<style scoped></style>
