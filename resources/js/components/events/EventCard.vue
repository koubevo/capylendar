<script setup lang="ts">
import TagsList from '@/components/tags/TagsList.vue';
import { isOnlyLinks as checkOnlyLinks } from '@/composables/useLinkify';
import type { Event, View } from '@/types/Event';
import { computed } from 'vue';
interface Props {
    event: Event;
    view: View;
}

const props = withDefaults(defineProps<Props>(), {
    view: 'list',
});

const showListItems = computed(() => props.view === 'list');
const showDetailItems = computed(() => props.view === 'detail');
const descriptionIsOnlyLinks = computed(() =>
    checkOnlyLinks(props.event.description_without_meta ?? ''),
);
</script>

<template>
    <UCard :class="props.event.capybara.classes" class="event-card">
        <div
            class="flex items-center justify-between p-4"
            :class="event.has_hearts ? 'bg-hearts' : ''"
        >
            <div class="min-w-0 flex-1">
                <h3 class="font-bold">{{ props.event.title }}</h3>
                <div class="flex items-center gap-x-1">
                    <UIcon
                        name="i-lucide-user-lock"
                        v-if="props.event.is_private"
                        class="size-3"
                    />
                    <UIcon
                        name="i-lucide-notepad-text"
                        v-if="
                            props.event.description_without_meta &&
                            !descriptionIsOnlyLinks &&
                            showListItems
                        "
                        class="size-3"
                    />
                    <UIcon
                        name="i-lucide-map-pinned"
                        v-if="props.event.has_map_meta && showListItems"
                        class="size-3"
                    />
                    <UIcon
                        name="i-lucide-link"
                        v-if="descriptionIsOnlyLinks && showListItems"
                        class="size-3"
                    />
                    <UIcon
                        name="i-lucide-image"
                        v-if="props.event.image_url && showListItems"
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
                <TagsList
                    :tags="props.event.tags"
                    :can-delete="false"
                    class="mt-1"
                    v-if="props.event.tags"
                />
            </div>
            <div class="ml-4 shrink-0">
                <img
                    class="h-14 w-14 rounded-md"
                    :src="props.event.capybara.avatar.src"
                    :alt="props.event.capybara.avatar.alt"
                />
            </div>
        </div>
    </UCard>
</template>
