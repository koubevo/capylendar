<script setup lang="ts">
import EventController from '@/actions/App/Http/Controllers/EventController';
import TodoController from '@/actions/App/Http/Controllers/TodoController';
import EventCard from '@/components/events/EventCard.vue';
import Nothing from '@/components/Nothing.vue';
import TodoCard from '@/components/todos/TodoCard.vue';
import type { Event } from '@/types/Event';
import type { Todo } from '@/types/Todo';
import { Link } from '@inertiajs/vue3';
import {
    type ComponentPublicInstance,
    computed,
    nextTick,
    onMounted,
    ref,
} from 'vue';

interface Props {
    heading: string;
    events: Event[];
    todos: Todo[];
    createIfEmpty?: boolean;
    scrollToDate?: string;
}

const props = withDefaults(defineProps<Props>(), {
    createIfEmpty: false,
    scrollToDate: undefined,
});

const emit = defineEmits<{
    scrolled: [];
    toggled: [todoId: number];
}>();

const button = props.createIfEmpty
    ? { to: EventController.create(), label: 'Přidat event' }
    : undefined;

interface DashboardItem {
    type: 'event' | 'todo';
    dateKey: string;
    dateLabel: string;
    data: Event | Todo;
}

const mergedItems = computed<DashboardItem[]>(() => {
    const items: DashboardItem[] = [];

    for (const event of props.events) {
        items.push({
            type: 'event',
            dateKey: event.date.key,
            dateLabel: event.date.label,
            data: event,
        });
    }

    for (const todo of props.todos) {
        items.push({
            type: 'todo',
            dateKey: todo.deadline.key,
            dateLabel: todo.deadline.label,
            data: todo,
        });
    }

    items.sort((a, b) => a.dateKey.localeCompare(b.dateKey));

    return items;
});

type GroupedItems = Record<string, DashboardItem[]>;

const groupedItems = computed<GroupedItems>(() => {
    return mergedItems.value.reduce((acc, item) => {
        if (!acc[item.dateKey]) {
            acc[item.dateKey] = [];
        }
        acc[item.dateKey].push(item);
        return acc;
    }, {} as GroupedItems);
});

const hasItems = computed(() => mergedItems.value.length > 0);

// Scroll-to-date support
const dateSectionRefs = ref<Record<string, HTMLElement>>({});

const setDateSectionRef = (
    el: Element | ComponentPublicInstance | null,
    dateKey: string,
) => {
    if (el instanceof HTMLElement) {
        dateSectionRefs.value[dateKey] = el;
    }
};

const scrollToDateSection = async () => {
    if (!props.scrollToDate) return;

    await nextTick();

    const targetSection = dateSectionRefs.value[props.scrollToDate];
    if (targetSection) {
        const headerOffset = 80;
        const elementPosition = targetSection.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.scrollY - headerOffset;

        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth',
        });

        setTimeout(() => {
            emit('scrolled');
        }, 500);
    }
};

onMounted(() => {
    scrollToDateSection();
});
</script>

<template>
    <h2 class="mt-4">{{ props.heading }}</h2>

    <div v-if="hasItems" class="space-y-4">
        <section
            v-for="(items, dateKey) in groupedItems"
            :key="dateKey"
            :ref="(el) => setDateSectionRef(el, dateKey as string)"
        >
            <h3 class="lowercase">
                {{ items[0].dateLabel }}
            </h3>

            <div class="grid grid-cols-1 gap-4">
                <template
                    v-for="item in items"
                    :key="`${item.type}-${item.data.id}`"
                >
                    <Link
                        v-if="item.type === 'event'"
                        :href="EventController.show(item.data as Event)"
                    >
                        <EventCard :event="item.data as Event" view="list" />
                    </Link>
                    <Link v-else :href="TodoController.show(item.data as Todo)">
                        <TodoCard
                            :todo="item.data as Todo"
                            :show-finish-button="true"
                            @toggled="emit('toggled', $event)"
                        />
                    </Link>
                </template>
            </div>
        </section>
    </div>

    <Nothing v-if="!hasItems" :button="button" />
</template>
