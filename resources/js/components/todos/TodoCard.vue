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
        class="todo-card p-0 overflow-hidden transition-all duration-300"
        :class="[
            props.todo.capybara.classes,
            props.todo.is_finished ? 'opacity-75' : ''
        ]"
    >
        <div class="flex items-stretch h-full">
            <!-- Large interactive left section (Finish Button) -->
            <button
                v-if="props.showFinishButton"
                type="button"
                class="group relative flex shrink-0 items-center justify-center w-12 sm:w-16 transition-all duration-300 border-r focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset"
                :class="[
                    props.todo.is_finished ? 'checked opacity-60 bg-black/10 dark:bg-white/10' : 'hover:bg-black/5 dark:hover:bg-white/5',
                    todo.has_hearts ? 'border-pink-200 dark:border-pink-900/30 focus-visible:ring-pink-400' : 'border-gray-100 dark:border-gray-800 focus-visible:ring-gray-300'
                ]"
                @click="handleFinish"
                aria-label="Dokončit todo"
            >
                <!-- Subtle indicator when incomplete to show it's interactive - shows priority color -->
                <div
                    v-if="!props.todo.is_finished"
                    class="absolute left-0 top-0 bottom-0 w-1.5 opacity-40 transition-all duration-300 group-hover:opacity-100"
                    :class="props.todo.priority.checkbox_color.replace('todo-button-', 'bg-')"
                ></div>

                <!-- Clean, massive checkmark icon for obvious affordance without a "box" -->
                <UIcon
                    name="i-lucide-check"
                    class="relative z-10 size-6 sm:size-8 transition-all duration-300 text-current"
                    :class="[
                        props.todo.is_finished
                            ? 'scale-100 opacity-80 drop-shadow-sm'
                            : 'scale-75 opacity-30 group-hover:scale-100 group-hover:opacity-100'
                    ]"
                />
            </button>

            <!-- Main Content Area -->
            <div
                class="flex flex-1 items-center justify-between p-4"
                :class="todo.has_hearts ? 'bg-hearts' : ''"
            >
                <div class="flex-1 min-w-0 pr-4">
                    <h3
                        class="font-bold truncate transition-all duration-300"
                        :class="
                            props.todo.is_finished
                                ? 'line-through decoration-2 opacity-60'
                                : ''
                        "
                        :title="props.todo.title"
                    >
                        {{ props.todo.title }}
                    </h3>
                    <div class="flex items-center gap-x-1 mt-0.5">
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
                        <p class="text-xs text-gray-600 dark:text-gray-300 truncate pl-0.5">
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
                        class="h-14 w-14 rounded-md object-cover transition-all duration-300 ring-1 ring-black/5 dark:ring-white/10"
                        :class="props.todo.is_finished ? 'opacity-50 grayscale' : 'opacity-100 shadow-sm'"
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

/* Low Priority (Green) */
.todo-button-green:hover {
    background-color: rgba(34, 197, 94, 0.08);
}
.todo-button-green.checked {
    background-color: #22c55e;
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
