<script setup lang="ts">
import EventController from '@/actions/App/Http/Controllers/EventController';
import ActionButtons from '@/components/buttons/ActionButtons.vue';
import EventCard from '@/components/events/EventCard.vue';
import InfoCard from '@/components/ui/InfoCard.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import type { Event } from '@/types/Event';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    event: Event;
}>();
</script>

<template>
    <Head :title="props.event.title" />
    <AuthenticatedLayout>
        <div class="flex flex-col gap-y-4">
            <EventCard :event="props.event" view="detail" />
            <InfoCard
                :author="props.event.author"
                :created_at_human="props.event.created_at_human"
                :updated_at_human="props.event.updated_at_human"
                :description="props.event.description"
                :classes="props.event.capybara.classes"
            />
            <ActionButtons
                :duplicate-action="{
                    url: EventController.create({
                        query: {
                            duplicate_event_id: props.event.id,
                        },
                    }),
                }"
                :edit-action="{ url: EventController.edit(props.event) }"
                :delete-action="{
                    url: EventController.destroy(props.event),
                    title: 'Smazat event',
                }"
                :class="props.event.capybara.classes"
            >
                <template #delete-modal-body>
                    <EventCard :event="props.event" />
                </template>
            </ActionButtons>
        </div>
    </AuthenticatedLayout>
</template>
