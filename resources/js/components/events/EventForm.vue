<script setup lang="ts">
/* eslint-disable vue/no-mutating-props */
import PrimaryButton from '@/components/buttons/PrimaryButton.vue';
import TagSelectMenu from '@/components/tags/TagSelectMenu.vue';
import MacroAlert from '@/components/ui/MacroAlert.vue';
import { hasGoogleMapUrl } from '@/lib/utils';
import { Capybara } from '@/types/Capybara';
import type { EventFormData } from '@/types/EventFormData';
import { Tag } from '@/types/Tag';
import type { InertiaForm } from '@inertiajs/vue3';
import { Form } from '@inertiajs/vue3';
import { computed, onUnmounted, ref } from 'vue';

interface Props {
    form: InertiaForm<EventFormData>;
    isEditMode: boolean;
    capybaraOptions: Capybara[];
    availableTags?: Tag[];
    eventId?: number;
    imageUrl?: string;
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

const selectedTags = computed({
    get: () => props.form.tags || [],
    set: (value) => {
        props.form.tags = value;
    },
});

const emit = defineEmits<{
    (e: 'submit'): void;
}>();

// Image preview state (local preview before form submit)
const imagePreview = ref<string | null>(null);

const displayImageUrl = computed(() => {
    // If user marked for removal, show nothing
    if (props.form.remove_image) return null;
    // If a new file was picked, show its local preview
    if (imagePreview.value) return imagePreview.value;
    // Otherwise show existing server image
    return props.imageUrl || null;
});

function revokePreview() {
    if (imagePreview.value) {
        URL.revokeObjectURL(imagePreview.value);
        imagePreview.value = null;
    }
}

function onImageSelected(event: globalThis.Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    revokePreview();

    if (!file) {
        props.form.image = null;
        return;
    }

    props.form.image = file;
    props.form.remove_image = false;
    imagePreview.value = URL.createObjectURL(file);
}

function clearImage() {
    props.form.image = null;
    revokePreview();

    // If in edit mode with existing image, mark for removal
    if (props.isEditMode && props.imageUrl) {
        props.form.remove_image = true;
    }
}

onUnmounted(() => revokePreview());
</script>

<template>
    <Form @submit.prevent="emit('submit')">
        <div class="flex w-full flex-col gap-y-6 md:gap-y-8">
            <UFormField
                label="Název"
                name="title"
                :error="props.form.errors.title"
                required
            >
                <UInput v-model="props.form.title" class="w-full" size="md" />
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
                <UInput
                    v-model="props.form.date"
                    type="date"
                    class="w-full"
                    size="md"
                />
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
                        size="md"
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
                        size="md"
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
                    :rows="5"
                    size="md"
                />
                <MacroAlert
                    v-show="mapDetected"
                    class="mt-3"
                    icon="i-lucide-map-pinned"
                    label="Bude vytvořena náhledová karta mapy"
                />
            </UFormField>

            <!-- Image Section -->
            <UFormField
                label="Obrázek"
                name="image"
                :error="props.form.errors.image"
            >
                <div class="flex flex-col gap-3">
                    <!-- Image preview -->
                    <div
                        v-if="displayImageUrl"
                        class="relative overflow-hidden rounded-xl border border-gray-200 shadow-sm dark:border-gray-700"
                    >
                        <img
                            :src="displayImageUrl"
                            alt="Event image"
                            class="h-48 w-full object-cover"
                        />
                        <button
                            type="button"
                            class="absolute top-2 right-2 flex items-center gap-1 rounded-md bg-red-500/90 px-3 py-1.5 text-xs font-medium text-white shadow-sm transition hover:bg-red-600 focus:ring-2 focus:ring-red-500 focus:outline-none"
                            @click="clearImage()"
                        >
                            <UIcon name="i-lucide-trash-2" class="size-4" />
                            Odebrat
                        </button>
                    </div>

                    <label
                        class="flex h-32 cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50/50 px-3 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50 dark:hover:bg-gray-800"
                    >
                        <div
                            class="rounded-full bg-primary-50 p-2 text-primary-500 dark:bg-primary-900/30"
                        >
                            <UIcon
                                name="i-lucide-upload-cloud"
                                class="size-6"
                            />
                        </div>
                        <div class="text-center">
                            <span
                                class="text-sm font-medium text-primary-600 dark:text-primary-400"
                            >
                                {{
                                    displayImageUrl
                                        ? 'Změnit obrázek'
                                        : 'Nahrát obrázek (klikněte)'
                                }}
                            </span>
                            <p class="mt-1 text-xs text-gray-500">
                                PNG, JPG, GIF do 10MB
                            </p>
                        </div>
                        <input
                            type="file"
                            accept="image/*"
                            class="hidden"
                            @change="onImageSelected"
                        />
                    </label>
                </div>
            </UFormField>

            <UFormField label="Štítky" name="tags">
                <TagSelectMenu
                    v-model="selectedTags"
                    :items="props.availableTags || []"
                    placeholder="Vyber štítky..."
                    search-input-placeholder="Hledat štítek..."
                />
            </UFormField>

            <USwitch
                label="Soukromé (tajný kapybara event)"
                v-model="props.form.is_private"
                :error="props.form.errors.is_private"
            />

            <PrimaryButton
                class="w-full justify-center"
                type="submit"
                :loading="props.form.processing"
            >
                {{ props.isEditMode ? 'Upravit' : 'Přidat' }}
            </PrimaryButton>
        </div>
    </Form>
</template>
