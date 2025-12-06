<script setup lang="ts">
import EventController from '@/actions/App/Http/Controllers/EventController';
import EventCard from '@/components/events/EventCard.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import type { Event } from '@/types/Event';
import { Head, Link } from '@inertiajs/vue3';

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
            <UCard :class="props.event.capybara.classes">
                <div class="flex flex-row items-center justify-around">
                    <Link
                        :href="
                            EventController.create({
                                query: {
                                    duplicate_event_id: props.event.id,
                                },
                            })
                        "
                    >
                        <UIcon name="i-lucide-copy-plus" class="size-6" />
                    </Link>
                    <Link :href="EventController.edit(props.event)">
                        <UIcon name="i-lucide-square-pen" class="size-6" />
                    </Link>
                    <Link disabled>
                        <!-- wip: name="i-lucide-square-x" -->
                        <UIcon name="i-lucide-construction" class="size-6" />
                    </Link>
                </div>
            </UCard>
        </div>
    </AuthenticatedLayout>
</template>
