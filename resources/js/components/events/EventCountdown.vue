<script setup lang="ts">
import CountdownCard from '@/components/events/CountdownCard.vue';
import type { EventCountdown } from '@/types/Event';

defineProps<{
    countdown: EventCountdown;
}>();
</script>

<template>
    <CountdownCard :label="countdown.label" />
</template>
