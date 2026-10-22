<script setup lang="ts">
import EventController from '@/actions/App/Http/Controllers/EventController';
import EventForm from '@/components/events/EventForm.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import { Capybara } from '@/types/Capybara';
import type { Event } from '@/types/Event';
import { EventFormData } from '@/types/EventFormData';
import type { EventKind } from '@/types/EventKind';
import { Tag } from '@/types/Tag';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    capybaraOptions: Capybara[];
    kindOptions: EventKind[];
    event: Event;
    availableTags: Tag[];
}>();

const form = useForm<EventFormData>({
    title: props.event.title,
    capybara: props.event.capybara.value,
    date: props.event.date.key,
    start_at: props.event.date.start_time,
    end_at: props.event.date.end_time,
    is_all_day: props.event.date.is_all_day,
    is_private: props.event.is_private,
    countdown_enabled: props.event.countdown_enabled,
    description: props.event.description ?? '',
    tags: props.event.tags?.map((tag) => tag.id) || [],
    image: null,
    remove_image: false,
    kind: props.event.kind?.value ?? 'standard',
    surprise_image: null,
    surprise_password: '',
    surprise_hint: props.event.surprise?.hint ?? '',
    remove_surprise_image: false,
});

function submit() {
    form.put(EventController.update.url(props.event), {
        forceFormData: true,
    });
}
</script>

<template>
    <Head title="Upravit event" />
    <AuthenticatedLayout :display-floating-action-button="false">
        <h2>
            Upravit event
            <span class="text-primary-500">{{ props.event.title }}</span>
        </h2>
        <EventForm
            :form="form"
            :is-edit-mode="true"
            :capybara-options="props.capybaraOptions"
            :kind-options="props.kindOptions"
            @submit="submit"
            :available-tags="props.availableTags"
            :event-id="props.event.id"
            :image-url="props.event.image_url"
            :has-surprise-image="Boolean(props.event.surprise)"
        />
    </AuthenticatedLayout>
</template>
