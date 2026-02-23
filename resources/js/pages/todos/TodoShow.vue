<script setup lang="ts">
import TodoController from '@/actions/App/Http/Controllers/TodoController';
import ActionButtons from '@/components/buttons/ActionButtons.vue';
import TodoCard from '@/components/todos/TodoCard.vue';
import InfoCard from '@/components/ui/InfoCard.vue';
import PreviewCard from '@/components/ui/PreviewCard.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import type { Todo } from '@/types/Todo';
import { Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps<{
    todo: Todo;
}>();

const localTodo = ref<Todo>({ ...props.todo });

watch(
    () => props.todo,
    (newVal) => {
        localTodo.value = { ...newVal };
    },
    { deep: true }
);

function handleToggled() {
    localTodo.value.is_finished = !localTodo.value.is_finished;
}
</script>

<template>
    <Head :title="localTodo.title" />
    <AuthenticatedLayout>
        <div class="flex flex-col gap-y-4">
            <TodoCard 
                :todo="localTodo" 
                :show-finish-button="true" 
                @toggled="handleToggled" 
            />
            <InfoCard
                :author="props.todo.author"
                :created_at_human="props.todo.created_at_human"
                :updated_at_human="props.todo.updated_at_human"
                :description="props.todo.description_without_meta"
                :classes="props.todo.capybara.classes"
                :link-classes="props.todo.capybara.link_classes"
            />
            <PreviewCard
                v-if="props.todo.has_map_meta"
                :url="props.todo.meta?.map_preview?.url ?? ''"
                :image="props.todo.meta?.map_preview?.image ?? ''"
                :title="props.todo.meta?.map_preview?.title ?? ''"
                :classes="props.todo.capybara.classes"
            />
            <ActionButtons
                :duplicate-action="{
                    url: TodoController.create({
                        query: {
                            duplicate_todo_id: props.todo.id,
                        },
                    }).url,
                }"
                :edit-action="{ url: TodoController.edit.url(props.todo) }"
                :delete-action="{
                    url: TodoController.destroy.url(props.todo),
                    title: 'Smazat todo',
                    titleShort: 'Smazat',
                }"
                :class="props.todo.capybara.classes"
            >
                <template #event-modal-body>
                    <TodoCard :todo="props.todo" />
                </template>
            </ActionButtons>
        </div>
    </AuthenticatedLayout>
</template>
