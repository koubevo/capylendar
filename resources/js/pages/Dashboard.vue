<script setup lang="ts">
import DashboardController from '@/actions/App/Http/Controllers/DashboardController';
import DashboardList from '@/components/dashboard/DashboardList.vue';
import EventFilterForm from '@/components/events/EventFilterForm.vue';
import EventsList from '@/components/events/EventsList.vue';
import TodosList from '@/components/todos/TodosList.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import { Capybara } from '@/types/Capybara';
import type { Event } from '@/types/Event';
import { EventFilters } from '@/types/Filters';
import { Tag } from '@/types/Tag';
import type { Todo } from '@/types/Todo';
import { InfiniteScroll, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface DashboardMonth {
    key: string;
    label: string;
    events: Event[];
    todos: Todo[];
}

interface Props {
    dashboardMonths: { data: DashboardMonth[] };
    eventFilters: EventFilters;
    capybaraOptions: Capybara[];
    availableTags: Tag[];
    scrollToDate?: string;
    highlightEvent?: number;
    highlightTodo?: number;
}

const props = defineProps<Props>();

const upcomingEvents = computed(() =>
    props.dashboardMonths.data.flatMap((month) => month.events),
);

const loadedTodos = computed(() =>
    props.dashboardMonths.data.flatMap((month) => month.todos),
);

const handleFilterChange = (newFilters: typeof props.eventFilters) => {
    router.get(DashboardController().url, newFilters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['dashboardMonths', 'eventFilters'],
        reset: ['dashboardMonths'],
    });
};

const isScrolled = ref(false);

const handleScrollFinished = () => {
    isScrolled.value = true;
    if (props.scrollToDate || props.highlightEvent || props.highlightTodo) {
        const url = new URL(window.location.href);
        url.searchParams.delete('scrollToDate');
        url.searchParams.delete('highlightEvent');
        url.searchParams.delete('highlightTodo');
        window.history.replaceState({}, '', url.toString());
    }
};

const activeEventFiltersCount = computed(() => {
    const count = Object.values(props.eventFilters).filter(Boolean).length;
    return count > 0 ? count : null;
});

const eventFiltersLabel = computed(() => {
    return activeEventFiltersCount.value
        ? 'Filtrování (' + activeEventFiltersCount.value + ')'
        : 'Filtrování';
});

const items = [
    {
        label: 'Vše',
        icon: 'i-lucide-rocket',
        slot: 'all',
    },
    {
        label: 'Eventy',
        icon: 'i-lucide-calendar',
        slot: 'events',
    },
    {
        label: 'Todos',
        icon: 'i-lucide-list-todo',
        slot: 'todos',
    },
];

const localTodos = ref<Todo[]>([]);

watch(
    loadedTodos,
    (newTodos) => {
        localTodos.value = newTodos.map((todo) => ({ ...todo }));
    },
    { immediate: true },
);

function handleToggled(todoId: number) {
    localTodos.value = localTodos.value.map((todo) =>
        todo.id === todoId ? { ...todo, is_finished: !todo.is_finished } : todo,
    );
}
</script>

<template>
    <AuthenticatedLayout :display-footer="true">
        <UCollapsible class="mb-4 flex w-full flex-col gap-2">
            <UButton
                :label="eventFiltersLabel"
                color="primary"
                variant="subtle"
                trailing-icon="i-lucide-chevron-down"
                block
            />

            <template #content>
                <EventFilterForm
                    :eventFilters="props.eventFilters"
                    :capybara-options="props.capybaraOptions"
                    :available-tags="props.availableTags"
                    @change="handleFilterChange"
                />
            </template>
        </UCollapsible>

        <InfiniteScroll data="dashboardMonths" preserve-url>
            <UTabs :items="items">
                <template #all>
                    <DashboardList
                        heading="Aktuální"
                        :events="upcomingEvents"
                        :todos="localTodos"
                        :create-if-empty="true"
                        :scroll-to-date="props.scrollToDate"
                        :highlight-event="props.highlightEvent"
                        :highlight-todo="props.highlightTodo"
                        :is-scrolled="isScrolled"
                        @scrolled="handleScrollFinished"
                        @toggled="handleToggled"
                    />
                </template>

                <template #events>
                    <EventsList
                        heading="Eventy"
                        :events="upcomingEvents"
                        :create-event-if-empty="true"
                        :scroll-to-date="props.scrollToDate"
                        :highlight-event="props.highlightEvent"
                        :is-scrolled="isScrolled"
                        @scrolled="handleScrollFinished"
                    />
                </template>

                <template #todos>
                    <TodosList
                        heading="Todos"
                        :todos="localTodos"
                        :create-todo-if-empty="true"
                        :show-finish-button="true"
                        :scroll-to-date="props.scrollToDate"
                        :highlight-todo="props.highlightTodo"
                        :is-scrolled="isScrolled"
                        @scrolled="handleScrollFinished"
                        @toggled="handleToggled"
                    />
                </template>
            </UTabs>

            <template #loading>
                <div
                    class="flex items-center justify-center gap-2 py-8 text-sm text-neutral-500"
                    role="status"
                >
                    <UIcon
                        name="i-lucide-loader-circle"
                        class="size-5 animate-spin"
                    />
                    <span>Načítám další měsíc</span>
                </div>
            </template>
        </InfiniteScroll>
    </AuthenticatedLayout>
</template>
