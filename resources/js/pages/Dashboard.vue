<script setup lang="ts">
import Construction from '@/components/Construction.vue';
import EventsList from '@/components/events/EventsList.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import type { Event } from '@/types/Event';
import { Head } from '@inertiajs/vue3';

//TODO: pinia
interface Props {
    upcomingEvents: Event[];
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
    <Head title="Domů" />
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
                <section class="mt-4">
                    <h2>Historické</h2>
                    <Construction />
                </section>
            </template>
        </UTabs>
    </AuthenticatedLayout>
</template>
