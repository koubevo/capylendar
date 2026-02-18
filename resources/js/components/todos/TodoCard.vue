<script setup lang="ts">
import { finish } from '@/actions/App/Http/Controllers/TodoController';
import TagsList from '@/components/tags/TagsList.vue';
import { isOnlyLinks as checkOnlyLinks } from '@/composables/useLinkify';
import type { Todo } from '@/types/Todo';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    todo: Todo;
    showFinishButton?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showFinishButton: false,
});

const descriptionIsOnlyLinks = computed(() =>
    checkOnlyLinks(props.todo.description_without_meta ?? ''),
);

function handleFinish(event: MouseEvent) {
    event.preventDefault();
    event.stopPropagation();
    router.post(finish(props.todo), {}, { preserveScroll: true });
}
</script>

<template>
    <UCard
        :class="[
            props.todo.capybara.classes,
            props.todo.priority.border_class,
        ]"
        class="todo-card border-l-4"
    >
        <div
            class="flex items-center justify-between p-4"
            :class="todo.has_hearts ? 'bg-hearts' : ''"
        >
            <div class="flex items-center gap-x-3">
                <button
                    v-if="props.showFinishButton"
                    type="button"
                    class="flex shrink-0 items-center justify-center rounded-full border-2 transition-colors"
                    :class="
                        props.todo.is_finished
                            ? 'border-green-500 bg-green-500 text-white'
                            : 'border-gray-300 hover:border-green-400 dark:border-gray-600'
                    "
                    style="width: 24px; height: 24px"
                    @click="handleFinish"
                >
                    <UIcon
                        v-if="props.todo.is_finished"
                        name="i-lucide-check"
                        class="size-3.5"
                    />
                </button>
                <div>
                    <h3
                        class="font-bold"
                        :class="
                            props.todo.is_finished
                                ? 'line-through opacity-60'
                                : ''
                        "
                    >
                        {{ props.todo.title }}
                    </h3>
                    <div class="flex items-center gap-x-1">
                        <UIcon
                            name="i-lucide-user-lock"
                            v-if="props.todo.is_private"
                            class="size-3"
                        />
                        <UIcon
                            :name="props.todo.priority.icon"
                            :class="props.todo.priority.icon_color"
                            class="size-3"
                        />
                        <UIcon
                            name="i-lucide-notepad-text"
                            v-if="
                                props.todo.description_without_meta &&
                                !descriptionIsOnlyLinks
                            "
                            class="size-3"
                        />
                        <UIcon
                            name="i-lucide-map-pinned"
                            v-if="props.todo.has_map_meta"
                            class="size-3"
                        />
                        <UIcon
                            name="i-lucide-link"
                            v-if="descriptionIsOnlyLinks"
                            class="size-3"
                        />
                        <UIcon
                            name="i-lucide-image"
                            v-if="props.todo.image_url"
                            class="size-3"
                        />
                        <p class="text-xs">
                            {{ props.todo.capybara.label }} |
                            {{ props.todo.deadline.label }}
                        </p>
                    </div>
                    <TagsList
                        :tags="props.todo.tags"
                        :can-delete="false"
                        class="mt-1"
                        v-if="props.todo.tags"
                    />
                </div>
            </div>
            <div>
                <img
                    class="h-14 w-14 rounded-md"
                    :src="props.todo.capybara.avatar.src"
                    :alt="props.todo.capybara.avatar.alt"
                />
            </div>
        </div>
    </UCard>
</template>
