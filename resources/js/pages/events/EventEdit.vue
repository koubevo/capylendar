<script setup lang="ts">
import EventController from '@/actions/App/Http/Controllers/EventController';
import EventForm from '@/components/events/EventForm.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import { Capybara } from '@/types/Capybara';
import type { Event } from '@/types/Event';
import { EventFormData } from '@/types/EventFormData';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    capybaraOptions: Capybara[];
    event: Event;
}>();

const form = useForm<EventFormData>({
    title: props.event.title,
    capybara: props.event.capybara.value,
    date: props.event.date.key,
    start_at: props.event.date.start_time,
    end_at: props.event.date.end_time,
    is_all_day: props.event.date.is_all_day,
    is_private: props.event.is_private,
    description: props.event.description,
});

function submit() {
    form.patch(EventController.update(props.event));
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
            @submit="submit"
        />
    </AuthenticatedLayout>
</template>
