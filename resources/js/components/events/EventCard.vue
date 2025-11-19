<script setup lang="ts">
import type { Event } from '@/types/Event';
interface Props {
    event: Event;
}

const props = defineProps<Props>();
</script>

<template>
    <UCard :class="props.event.capybara.classes">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-bold">{{ props.event.title }}</h3>
                <p class="text-xs">
                    {{ props.event.capybara.label }} |
                    <span v-if="props.event.date.is_all_day"> Celý den </span>
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
            <div>
                <img
                    class="h-14 w-14 rounded-sm"
                    :src="props.event.capybara.avatar.src"
                    :alt="props.event.capybara.avatar.alt"
                />
            </div>
        </div>
    </UCard>
</template>
