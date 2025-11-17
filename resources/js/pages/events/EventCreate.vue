<script setup lang="ts">
import EventForm from '@/components/events/EventForm.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import { EventFormData } from '@/types/EventFormData';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    capybaraOptions: Array<{
        value: string;
        label: string;
        avatar: string;
    }>;
}>();

const form = useForm<EventFormData>({
    title: '',
    capybara: 'pink',
    date: '',
    start_at: '',
    end_at: '',
    is_all_day: false,
});

function submit() {
    form.transform((data) => ({
        ...data,
        start_at: data.is_all_day
            ? `${data.date} 00:00:00`
            : `${data.date} ${data.start_at}`,
        end_at:
            data.is_all_day || !data.end_at
                ? null
                : `${data.date} ${data.end_at}`,
    })).post(route('events.store'));
}
</script>

<template>
    <Head title="Přidat event" />
    <AuthenticatedLayout class="w-full sm:max-w-xl">
        <h1 class="mb-4 text-2xl font-bold">Přidat event</h1>
        <EventForm
            :form="form"
            :is-edit-mode="false"
            :capybara-options="props.capybaraOptions"
            @submit="submit"
        />
    </AuthenticatedLayout>
</template>

<style scoped></style>
