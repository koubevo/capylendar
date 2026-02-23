<script setup lang="ts">
import { finish } from '@/actions/App/Http/Controllers/TodoController';
import TagsList from '@/components/tags/TagsList.vue';
import { isOnlyLinks as checkOnlyLinks } from '@/composables/useLinkify';
import type { Todo } from '@/types/Todo';
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

    const { url, method } = finish(props.todo);
    const xsrfToken = document.cookie
        .split('; ')
        .find((c) => c.startsWith('XSRF-TOKEN='))
        ?.split('=')[1];

    fetch(url, {
        method: method.toUpperCase(),
        credentials: 'same-origin',
        headers: {
            'X-XSRF-TOKEN': xsrfToken ? decodeURIComponent(xsrfToken) : '',
            Accept: 'application/json',
        },
    });
}
</script>

<template>
    <UCard :class="props.todo.capybara.classes" class="todo-card">
        <div
            class="flex items-center justify-between p-4"
            :class="todo.has_hearts ? 'bg-hearts' : ''"
        >
            <div>
                <h3 class="flex items-center gap-x-2 font-bold">
                    <button
                        v-if="props.showFinishButton"
                        type="button"
                        class="todo-checkbox inline-flex shrink-0 items-center justify-center rounded-full border-2 transition-all duration-200"
                        :class="[
                            props.todo.priority.checkbox_color,
                            props.todo.is_finished ? 'checked' : '',
                        ]"
                        @click="handleFinish"
                    >
                        <UIcon
                            name="i-lucide-check"
                            class="size-3 transition-opacity duration-200"
                            :class="
                                props.todo.is_finished
                                    ? 'text-white opacity-100'
                                    : 'opacity-0'
                            "
                        />
                    </button>
                    <span
                        class="transition-all duration-300"
                        :class="
                            props.todo.is_finished
                                ? 'line-through decoration-2 opacity-50'
                                : ''
                        "
                    >
                        {{ props.todo.title }}
                    </span>
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

<style scoped>
.todo-checkbox {
    width: 20px;
    height: 20px;
    min-width: 20px;
}

@media (pointer: coarse) {
    .todo-checkbox {
        width: 24px;
        height: 24px;
        min-width: 24px;
    }
}
</style>
