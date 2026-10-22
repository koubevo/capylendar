<script setup lang="ts">
import { create, show } from '@/actions/App/Http/Controllers/EventController';
import EventCard from '@/components/events/EventCard.vue';
import Nothing from '@/components/Nothing.vue';
import type { Event } from '@/types/Event';
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
    createEventIfEmpty?: boolean;
    scrollToDate?: string;
    highlightEvent?: number;
    isScrolled?: boolean;
    interactive?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    createEventIfEmpty: false,
    scrollToDate: undefined,
    interactive: true,
});

const emit = defineEmits<{
    scrolled: [];
}>();

const button = props.createEventIfEmpty
    ? { to: create(), label: 'Přidat event' }
    : undefined;

type GroupedEvents = Record<string, Event[]>;

const groupedEvents = computed(() => {
    return props.events.reduce((acc, event) => {
        const key = event.date.key;

        if (!acc[key]) {
            acc[key] = [];
        }
        acc[key].push(event);

        return acc;
    }, {} as GroupedEvents);
});

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
    <div>
        <h2 class="mt-4">{{ props.heading }}</h2>

        <div v-if="props.events.length" class="space-y-4">
            <section
                v-for="(events, dateKey) in groupedEvents"
                :key="dateKey"
                :ref="(el) => setDateSectionRef(el, dateKey as string)"
            >
                <h3 class="lowercase">
                    {{ events[0].date.label }}
                </h3>

                <div class="grid grid-cols-1 gap-4">
                    <EventCard
                        v-for="event in events"
                        :key="event.id"
                        :event="event"
                        view="list"
                        :href="show.url(event)"
                        :interactive="props.interactive"
                        :class="{
                            'card-highlight':
                                props.isScrolled &&
                                props.highlightEvent === event.id,
                        }"
                    />
                </div>
            </section>
        </div>

        <Nothing v-if="!props.events.length" :button="button" />
    </div>
</template>
