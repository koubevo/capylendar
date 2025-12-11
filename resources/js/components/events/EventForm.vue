<script setup lang="ts">
/* eslint-disable vue/no-mutating-props */
import PrimaryButton from '@/components/buttons/PrimaryButton.vue';
import MacroAlert from '@/components/ui/MacroAlert.vue';
import { hasGoogleMapUrl } from '@/lib/utils';
import { Capybara } from '@/types/Capybara';
import type { EventFormData } from '@/types/EventFormData';
import type { Form } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    form: Form<EventFormData>;
    isEditMode: boolean;
    capybaraOptions: Capybara[];
}

const props = defineProps<Props>();

const selectedAvatar = computed(() => {
    const selected = props.capybaraOptions.find(
        (option) => option.value === props.form.capybara,
    );

    return selected ? selected.avatar : undefined;
});

const mapDetected = computed(() => {
    return hasGoogleMapUrl(props.form.description);
});

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
                required
            >
                <UInput v-model="props.form.title" class="w-full" />
            </UFormField>

            <UFormField
                label="Pro"
                name="capybara"
                :error="props.form.errors.capybara"
                required
            >
                <USelect
                    v-model="props.form.capybara"
                    class="w-full"
                    :items="capybaraOptions"
                    placeholder="Vyber kapybaru"
                    :avatar="selectedAvatar"
                />
            </UFormField>

            <UFormField
                label="Datum"
                name="date"
                :error="props.form.errors.start_at"
                required
            >
                <UInput v-model="props.form.date" type="date" class="w-full" />
            </UFormField>

            <USwitch
                label="Celý den"
                v-model="props.form.is_all_day"
                :error="props.form.errors.is_all_day"
            />

            <div
                class="flex w-full flex-row gap-x-4"
                v-if="!props.form.is_all_day"
            >
                <UFormField
                    label="Od"
                    name="start_at"
                    class="w-1/2"
                    :error="props.form.errors.start_at"
                    required
                >
                    <UInput
                        v-model="props.form.start_at"
                        type="time"
                        class="w-full"
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
                    />
                </UFormField>
            </div>

            <UFormField
                label="Popis"
                name="description"
                :error="props.form.errors.description"
            >
                <UTextarea
                    v-model="props.form.description"
                    class="w-full"
                    rows="5"
                />
                <MacroAlert
                    v-show="mapDetected"
                    icon="i-lucide-map-pinned"
                    label="Bude vytvořena náhledová karta mapy"
                />
            </UFormField>

            <USwitch
                label="Soukromé (tajný kapybara event)"
                v-model="props.form.is_private"
                :error="props.form.errors.is_private"
            />

            <PrimaryButton class="w-full justify-center" type="submit">
                {{ props.isEditMode ? 'Upravit' : 'Přidat' }}
            </PrimaryButton>
        </div>
    </form>
</template>
