<script setup lang="ts">
import EventCard from '@/components/events/EventCard.vue';
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
            <EventCard :event="props.event" />
            <UCard
                :class="props.event.capybara.classes"
                v-if="props.event.description"
            >
                <h4 class="text-[10px] font-bold">Poznámka</h4>
                {{ props.event.description }}
            </UCard>
        </div>
    </AuthenticatedLayout>
</template>
