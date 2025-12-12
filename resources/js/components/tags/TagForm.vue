<script setup lang="ts">
/* eslint-disable vue/no-mutating-props */
import PrimaryButton from '@/components/buttons/PrimaryButton.vue';
import { TagFormData } from '@/types/TagFormData';
import type { Form } from '@inertiajs/vue3';

interface Props {
    form: Form<TagFormData>;
    isEditMode: boolean;
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
                label="Text"
                name="label"
                :error="props.form.errors.label"
                required
            >
                <UInput v-model="props.form.label" class="w-full" />
            </UFormField>

            <UFormField
                label="Barva"
                name="color"
                :error="props.form.errors.color"
                required
            >
                <UColorPicker v-model="props.form.color" />
            </UFormField>

            <PrimaryButton class="w-full justify-center" type="submit">
                {{ props.isEditMode ? 'Upravit' : 'Přidat' }}
            </PrimaryButton>
        </div>
    </form>
</template>
