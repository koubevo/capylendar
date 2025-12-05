<script setup lang="ts">
import EventController from '@/actions/App/Http/Controllers/EventController';
import EventForm from '@/components/events/EventForm.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import { Capybara } from '@/types/Capybara';
import type { Event } from '@/types/Event';
import { EventFormData } from '@/types/EventFormData';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();

const props = defineProps<{
    capybaraOptions: Capybara[];
    event?: Event;
}>();

const form = useForm<EventFormData>({
    title: props.event?.title || '',
    capybara: props.event?.capybara.value || page.props.auth.user.capybara,
    date: '',
    start_at: props.event?.date.start_time || '',
    end_at: props.event?.date.end_time || '',
    is_all_day: props.event?.date.is_all_day || false,
    is_private: props.event?.is_private || false,
    description: props.event?.description || '',
});

function submit() {
    form.post(EventController.store());
}
</script>

<template>
    <Head title="Přidat event" />
    <AuthenticatedLayout :display-floating-action-button="false">
        <h2>Přidat event</h2>
        <EventForm
            :form="form"
            :is-edit-mode="false"
            :capybara-options="props.capybaraOptions"
            @submit="submit"
        />
    </AuthenticatedLayout>
</template>
