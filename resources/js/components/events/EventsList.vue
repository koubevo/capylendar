<script setup lang="ts">
import EventController from '@/actions/App/Http/Controllers/EventController';
import EventCard from '@/components/events/EventCard.vue';
import Nothing from '@/components/Nothing.vue';
import type { Event } from '@/types/Event';
import { computed } from 'vue';

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

type GroupedEvents = Record<string, Event[]>;

const groupedEvents = computed(() => {
    return props.events.reduce((acc, event) => {
        const key = event.date.key;

        if (!acc[key]) {
            acc[key] = [];
        }
        acc[key].push(event);

        return acc;
    }, {} as GroupedEvents);
});
</script>

<template>
    <h2 class="mt-4">{{ props.heading }}</h2>

    <div v-if="props.events.length" class="space-y-4">
        <section v-for="(events, dateKey) in groupedEvents" :key="dateKey">
            <h3>
                {{ events[0].date.label }}
            </h3>

            <div class="grid grid-cols-1 gap-4">
                <EventCard
                    v-for="event in events"
                    :key="event.id"
                    :event="event"
                />
            </div>
        </section>
    </div>

    <Nothing v-if="!props.events.length" :button="button" />
</template>

<style scoped></style>
