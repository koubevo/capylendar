<script setup lang="ts">
import EventController from '@/actions/App/Http/Controllers/EventController';
import TodoController from '@/actions/App/Http/Controllers/TodoController';
import NeutralButton from '@/components/buttons/NeutralButton.vue';
import PrimaryButton from '@/components/buttons/PrimaryButton.vue';
import EventCard from '@/components/events/EventCard.vue';
import Nothing from '@/components/Nothing.vue';
import TodoCard from '@/components/todos/TodoCard.vue';
import type { Event } from '@/types/Event';
import type { Todo } from '@/types/Todo';
import { Link, router } from '@inertiajs/vue3';
import {
    type ComponentPublicInstance,
    computed,
    nextTick,
    onMounted,
    ref,
    watch,
} from 'vue';

interface Props {
    heading: string;
    events: Event[];
    todos: Todo[];
    createIfEmpty?: boolean;
    scrollToDate?: string;
    highlightEvent?: number;
    highlightTodo?: number;
    isScrolled?: boolean;
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

    items.sort((a, b) => {
        const dateCompare = a.dateKey.localeCompare(b.dateKey);
        if (dateCompare !== 0) return dateCompare;

        if (a.type !== b.type) return a.type === 'event' ? -1 : 1;

        return 0;
    });

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

const isHighlighted = (item: DashboardItem) => {
    if (!props.isScrolled) return false;
    if (item.type === 'event') return props.highlightEvent === item.data.id;
    if (item.type === 'todo') return props.highlightTodo === item.data.id;
    return false;
};

const hasTodosInGroup = (items: DashboardItem[]) => {
    return items.some(
        (item) => item.type === 'todo' && !(item.data as Todo).is_finished,
    );
};

const postponingDate = ref<string | null>(null);

const handlePostponeByDate = (dateKey: string, close: () => void) => {
    postponingDate.value = dateKey;
    router.post(
        TodoController.postponeByDate.url(),
        { date: dateKey },
        {
            onSuccess: () => close(),
            onFinish: () => {
                postponingDate.value = null;
            },
        },
    );
};

onMounted(() => {
    scrollToDateSection();
});

watch(
    () => props.scrollToDate,
    (newVal) => {
        if (newVal) {
            scrollToDateSection();
        }
    },
);
</script>

<template>
    <h2 class="mt-4">{{ props.heading }}</h2>

    <div v-if="hasItems" class="space-y-4">
        <section
            v-for="(items, dateKey) in groupedItems"
            :key="dateKey"
            :ref="(el) => setDateSectionRef(el, dateKey as string)"
        >
            <div class="flex items-center justify-between">
                <h3 class="lowercase">
                    {{ items[0].dateLabel }}
                </h3>
                <UModal
                    v-if="hasTodosInGroup(items)"
                    title="Přesunout todos"
                    :ui="{ footer: 'justify-end', title: 'm-0' }"
                >
                    <button
                        type="button"
                        aria-label="Přesunout todos na další den"
                        class="flex cursor-pointer items-center gap-1 rounded-md px-1.5 py-0.5 text-xs text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                    >
                        <UIcon name="i-lucide-arrow-right" class="size-3" />
                    </button>

                    <template #body>
                        <p>
                            Opravdu chceš přesunout všechna todos z
                            <strong class="lowercase">{{
                                items[0].dateLabel
                            }}</strong>
                            na další den?
                        </p>
                    </template>

                    <template #footer="{ close }">
                        <NeutralButton label="Zrušit" @click="close" />
                        <PrimaryButton
                            label="Přesunout"
                            :loading="postponingDate === (dateKey as string)"
                            @click="
                                handlePostponeByDate(dateKey as string, close)
                            "
                        />
                    </template>
                </UModal>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <template
                    v-for="item in items"
                    :key="`${item.type}-${item.data.id}`"
                >
                    <Link
                        v-if="item.type === 'event'"
                        :href="EventController.show(item.data as Event)"
                        class="block"
                    >
                        <EventCard
                            :event="item.data as Event"
                            view="list"
                            :class="{ 'card-highlight': isHighlighted(item) }"
                        />
                    </Link>
                    <Link
                        v-else
                        :href="TodoController.show(item.data as Todo)"
                        class="block"
                    >
                        <TodoCard
                            :todo="item.data as Todo"
                            :show-finish-button="true"
                            :class="{ 'card-highlight': isHighlighted(item) }"
                            @toggled="emit('toggled', $event)"
                        />
                    </Link>
                </template>
            </div>
        </section>
    </div>

    <Nothing v-if="!hasItems" :button="button" />
</template>
