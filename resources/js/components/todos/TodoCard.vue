<script setup lang="ts">
import { finish } from '@/actions/App/Http/Controllers/TodoController';
import TagsList from '@/components/tags/TagsList.vue';
import { isOnlyLinks as checkOnlyLinks } from '@/composables/useLinkify';
import type { Todo } from '@/types/Todo';
import axios from 'axios';
import { computed } from 'vue';

interface Props {
    todo: Todo;
    showFinishButton?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showFinishButton: false,
});

const emit = defineEmits<{
    (e: 'toggled', todoId: number): void;
}>();

const descriptionIsOnlyLinks = computed(() =>
    checkOnlyLinks(props.todo.description_without_meta ?? ''),
);

function handleFinish(event: MouseEvent) {
    event.preventDefault();
    event.stopPropagation();

    emit('toggled', props.todo.id);

    axios.post(finish(props.todo).url);
}
</script>

<template>
    <UCard
        class="todo-card overflow-hidden p-0 transition-all duration-300"
        :class="[
            props.todo.capybara.classes,
            props.todo.is_finished ? 'opacity-75' : '',
        ]"
    >
        <div class="flex h-full items-stretch">
            <!-- Large interactive left section (Finish Button) -->
            <button
                v-if="props.showFinishButton"
                type="button"
                class="group relative flex w-12 shrink-0 items-center justify-center border-r transition-all duration-300 focus-visible:ring-2 focus-visible:outline-none focus-visible:ring-inset sm:w-16"
                :class="[
                    props.todo.priority.checkbox_color,
                    props.todo.is_finished
                        ? 'checked bg-black/10 opacity-60 dark:bg-white/10'
                        : 'hover:bg-black/5 dark:hover:bg-white/5',
                    todo.has_hearts
                        ? 'border-pink-200 focus-visible:ring-pink-400 dark:border-pink-900/30'
                        : 'border-gray-100 focus-visible:ring-gray-300 dark:border-gray-800',
                ]"
                @click="handleFinish"
                aria-label="Dokončit todo"
            >
                <!-- Subtle indicator when incomplete to show it's interactive - shows priority color -->
                <div
                    v-if="!props.todo.is_finished"
                    class="absolute top-0 bottom-0 left-0 w-1.5 opacity-40 transition-all duration-300 group-hover:opacity-100"
                    :class="
                        props.todo.priority.checkbox_color.replace(
                            'todo-button-',
                            'bg-',
                        )
                    "
                ></div>

                <!-- Clean, massive checkmark icon for obvious affordance without a "box" -->
                <UIcon
                    name="i-lucide-check"
                    class="relative z-10 size-6 text-current transition-all duration-300 sm:size-8"
                    :class="[
                        props.todo.is_finished
                            ? 'scale-100 opacity-80 drop-shadow-sm'
                            : 'scale-75 opacity-30 group-hover:scale-100 group-hover:opacity-100',
                    ]"
                />
            </button>

            <!-- Main Content Area -->
            <div
                class="flex flex-1 items-center justify-between p-4"
                :class="todo.has_hearts ? 'bg-hearts' : ''"
            >
                <div class="min-w-0 flex-1 pr-4">
                    <h3
                        class="truncate font-bold transition-all duration-300"
                        :class="
                            props.todo.is_finished
                                ? 'line-through decoration-2 opacity-60'
                                : ''
                        "
                        :title="props.todo.title"
                    >
                        {{ props.todo.title }}
                    </h3>
                    <div class="mt-0.5 flex items-center gap-x-1">
                        <UIcon
                            name="i-lucide-user-lock"
                            v-if="props.todo.is_private"
                            class="size-3 text-gray-500"
                            title="Soukromé"
                        />
                        <UIcon
                            :name="props.todo.priority.icon"
                            :class="props.todo.priority.icon_color"
                            class="size-3"
                            :title="'Priorita: ' + props.todo.priority.label"
                        />
                        <UIcon
                            name="i-lucide-notepad-text"
                            v-if="
                                props.todo.description_without_meta &&
                                !descriptionIsOnlyLinks
                            "
                            class="size-3 text-gray-500"
                            title="Obsahuje popis"
                        />
                        <UIcon
                            name="i-lucide-map-pinned"
                            v-if="props.todo.has_map_meta"
                            class="size-3 text-gray-500"
                            title="Obsahuje lokaci"
                        />
                        <UIcon
                            name="i-lucide-link"
                            v-if="descriptionIsOnlyLinks"
                            class="size-3 text-gray-500"
                            title="Obsahuje odkaz"
                        />
                        <p
                            class="truncate pl-0.5 text-xs text-gray-600 dark:text-gray-300"
                        >
                            {{ props.todo.capybara.label }} |
                            {{ props.todo.deadline.label }}
                        </p>
                    </div>
                    <TagsList
                        :tags="props.todo.tags"
                        :can-delete="false"
                        class="mt-1"
                        v-if="props.todo.tags && props.todo.tags.length > 0"
                    />
                </div>

                <!-- Avatar Column -->
                <div class="shrink-0 pl-2">
                    <img
                        class="h-14 w-14 rounded-md object-cover ring-1 ring-black/5 transition-all duration-300 dark:ring-white/10"
                        :class="
                            props.todo.is_finished
                                ? 'opacity-50 grayscale'
                                : 'opacity-100 shadow-sm'
                        "
                        :src="props.todo.capybara.avatar.src"
                        :alt="props.todo.capybara.avatar.alt"
                    />
                </div>
            </div>
        </div>
    </UCard>
</template>

<style scoped>
/* Scoped button colors for the new full-height accent bar style */

/* Low Priority (Teal) */
.todo-button-teal:hover {
    background-color: color-mix(
        in oklab,
        var(--color-teal-500, #14b8a6) 8%,
        transparent
    );
}
.todo-button-teal.checked {
    background-color: var(--color-teal-500, #14b8a6);
}

/* Medium Priority (Yellow) */
.todo-button-yellow:hover {
    background-color: rgba(234, 179, 8, 0.08);
}
.todo-button-yellow.checked {
    background-color: #eab308;
}

/* High Priority (Red) */
.todo-button-red:hover {
    background-color: rgba(239, 68, 68, 0.08);
}
.todo-button-red.checked {
    background-color: #ef4444;
}
</style>
