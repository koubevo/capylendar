<script setup lang="ts">
/* eslint-disable vue/no-mutating-props */ // Toto je správně, ignoruje chybu ESLint
import PrimaryButton from '@/components/buttons/PrimaryButton.vue';
import type { EventFormData } from '@/types/EventFormData';
import type { Form } from '@inertiajs/vue3';

interface Props {
    form: Form<EventFormData>;
    isEditMode: boolean;
    capybaraOptions: Array<{
        value: string;
        label: string;
        avatar: string;
    }>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'submit'): void;
}>();
</script>

<template>
    <form @submit.prevent="emit('submit')">
        <div class="flex w-full flex-col gap-y-4">
            <UFormField
                label="Název"
                name="title"
                :error="props.form.errors.title"
            >
                <UInput v-model="props.form.title" class="w-full" />
            </UFormField>

            <UFormField
                label="Pro"
                name="capybara"
                :error="props.form.errors.capybara"
            >
                <USelect
                    v-model="props.form.capybara"
                    class="w-full"
                    :items="props.capybaraOptions"
                    value-attribute="value"
                    option-attribute="label"
                />
            </UFormField>

            <UFormField
                label="Datum"
                name="date"
                :error="props.form.errors.date"
            >
                <UInput v-model="props.form.date" type="date" class="w-full" />
            </UFormField>

            <div class="flex w-full flex-row gap-x-4">
                <UFormField
                    label="Od"
                    name="start_at"
                    class="w-1/2"
                    :error="props.form.errors.start_at"
                >
                    <UInput
                        v-model="props.form.start_at"
                        type="time"
                        class="w-full"
                        :disabled="props.form.is_all_day"
                    />
                </UFormField>

                <UFormField
                    label="Do"
                    name="end_at"
                    class="w-1/2"
                    :error="props.form.errors.end_at"
                >
                    <UInput
                        v-model="props.form.end_at"
                        type="time"
                        class="w-full"
                        :disabled="props.form.is_all_day"
                    />
                </UFormField>
            </div>

            <USwitch
                label="Celý den"
                v-model="props.form.is_all_day"
                :error="props.form.errors.is_all_day"
            />

            <PrimaryButton class="w-full justify-center">
                {{ props.isEditMode ? 'Upravit' : 'Přidat' }}
            </PrimaryButton>
        </div>
    </form>
</template>

<style scoped></style>
