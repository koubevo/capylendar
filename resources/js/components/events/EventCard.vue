<script setup lang="ts">
import type { Event, View } from '@/types/Event';
interface Props {
    event: Event;
    view: View;
}

const props = withDefaults(defineProps<Props>(), {
    view: 'list',
});

const showListItems = props.view === 'list';
const showDetailItems = props.view === 'detail';
</script>

<template>
    <UCard :class="props.event.capybara.classes" class="event-card">
        <div
            class="flex items-center justify-between p-4"
            :class="event.has_hearts ? 'bg-hearts' : ''"
        >
            <div>
                <h3 class="font-bold">{{ props.event.title }}</h3>
                <div class="flex items-center gap-x-1">
                    <UIcon
                        name="i-lucide-user-lock"
                        v-if="props.event.is_private"
                        class="size-3"
                    />
                    <UIcon
                        name="i-lucide-notepad-text"
                        v-if="props.event.description && showListItems"
                        class="size-3"
                    />
                    <p class="text-xs">
                        {{ props.event.capybara.label }} |
                        <span v-if="showDetailItems">
                            {{ props.event.date.label }} |
                        </span>
                        <span v-if="props.event.date.is_all_day">
                            Celý den
                        </span>
                        <span v-else>
                            <span>
                                {{ props.event.date.start_time }}
                            </span>
                            <span v-if="props.event.date.end_time">
                                - {{ props.event.date.end_time }}
                            </span>
                        </span>
                    </p>
                </div>
            </div>
            <div>
                <img
                    class="h-14 w-14 rounded-md"
                    :src="props.event.capybara.avatar.src"
                    :alt="props.event.capybara.avatar.alt"
                />
            </div>
        </div>
    </UCard>
</template>
