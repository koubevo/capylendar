<script setup lang="ts">
import EventForm from '@/components/events/EventForm.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import { Capybara } from '@/types/Capybara';
import { EventFormData } from '@/types/EventFormData';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    capybaraOptions: Capybara[];
}>();

const form = useForm<EventFormData>({
    title: '',
    capybara: 'pink',
    date: '',
    start_at: '',
    end_at: '',
    is_all_day: false,
    is_private: false,
});

function submit() {
    //TODO
    form.post(route('events.store'));
}
</script>

<template>
    <Head title="Přidat event" />
    <AuthenticatedLayout>
        <h2>Přidat event</h2>
        <EventForm
            :form="form"
            :is-edit-mode="false"
            :capybara-options="props.capybaraOptions"
            @submit="submit"
        />
    </AuthenticatedLayout>
</template>
