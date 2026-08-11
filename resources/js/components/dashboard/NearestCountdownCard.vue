<script setup lang="ts">
import { show } from '@/actions/App/Http/Controllers/EventController';
import CountdownCard from '@/components/events/CountdownCard.vue';
import type { Event } from '@/types/Event';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    event: Event;
}>();
</script>

<template>
    <Link :href="show(props.event)" class="mb-4 block">
        <CountdownCard
            v-if="props.event.countdown"
            :title="props.event.title"
            :label="props.event.countdown.label"
            :color-classes="props.event.capybara.classes"
            class="transition-transform hover:-translate-y-0.5"
        />
    </Link>
</template>
