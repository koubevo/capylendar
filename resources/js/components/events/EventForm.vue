<script setup lang="ts">
/* eslint-disable vue/no-mutating-props */
import PrimaryButton from '@/components/buttons/PrimaryButton.vue';
import MacroAlert from '@/components/ui/MacroAlert.vue';
import { hasGoogleMapUrl } from '@/lib/utils';
import { Capybara } from '@/types/Capybara';
import type { EventFormData } from '@/types/EventFormData';
import { Tag } from '@/types/Tag';
import type { Form } from '@inertiajs/vue3';
import { computed, onUnmounted, ref } from 'vue';

interface Props {
    form: Form<EventFormData>;
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

const availableTagsMap = computed(() => {
    if (!props.availableTags) {
        return new Map<number, Tag>();
    }
    return new Map(props.availableTags.map((tag) => [tag.id, tag]));
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
                        class="relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700"
                    >
                        <img
                            :src="displayImageUrl"
                            alt="Event image"
                            class="h-48 w-full object-cover"
                        />
                        <button
                            type="button"
                            class="absolute top-2 right-2 flex items-center gap-1 rounded-md bg-red-500/90 px-2 py-1 text-xs text-white transition hover:bg-red-600"
                            @click="clearImage()"
                        >
                            <UIcon name="i-lucide-trash-2" class="size-3" />
                            Odebrat
                        </button>
                    </div>

                    <label
                        class="flex cursor-pointer items-center gap-2 rounded-md border border-gray-300 px-3 py-2 text-sm transition hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-800"
                    >
                        <UIcon name="i-lucide-image-plus" class="size-4" />
                        {{
                            displayImageUrl
                                ? 'Změnit obrázek'
                                : 'Přidat obrázek'
                        }}
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
                <USelectMenu
                    v-model="selectedTags"
                    :items="props.availableTags || []"
                    multiple
                    placeholder="Vyber štítky..."
                    value-key="id"
                    :search-input="{ placeholder: 'Hledat štítek...' }"
                    class="w-full"
                >
                    <template #option="{ option }">
                        <span
                            class="h-2 w-2 flex-shrink-0 rounded-full"
                            :style="{ backgroundColor: option.color }"
                        ></span>
                        <span class="truncate">{{ option.label }}</span>
                    </template>

                    <template #label>
                        <div
                            v-if="selectedTags.length"
                            class="flex flex-wrap gap-1"
                        >
                            <span
                                v-for="tagId in selectedTags"
                                :key="tagId"
                                class="flex items-center gap-1 rounded bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-700"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full"
                                    :style="{
                                        backgroundColor:
                                            availableTagsMap.get(tagId)?.color,
                                    }"
                                ></span>
                                {{ availableTagsMap.get(tagId)?.label }}
                            </span>
                        </div>
                        <span v-else class="text-gray-500"
                            >Vyber štítky...</span
                        >
                    </template>
                </USelectMenu>
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
