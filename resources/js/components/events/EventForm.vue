<script setup lang="ts">
/* eslint-disable vue/no-mutating-props */
import PrimaryButton from '@/components/buttons/PrimaryButton.vue';
import TagSelectMenu from '@/components/tags/TagSelectMenu.vue';
import MacroAlert from '@/components/ui/MacroAlert.vue';
import { getTodayDateString, hasGoogleMapUrl } from '@/lib/utils';
import { Capybara } from '@/types/Capybara';
import type { EventFormData } from '@/types/EventFormData';
import type { EventKind } from '@/types/EventKind';
import type { Tag } from '@/types/Tag';
import type { InertiaForm } from '@inertiajs/vue3';
import { computed, onUnmounted, ref, watch } from 'vue';

interface Props {
    form: InertiaForm<EventFormData>;
    isEditMode: boolean;
    capybaraOptions: Capybara[];
    kindOptions: EventKind[];
    availableTags?: Tag[];
    eventId?: number;
    imageUrl?: string;
    hasSurpriseImage?: boolean;
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

const selectedKind = computed(() =>
    props.kindOptions.find((option) => option.value === props.form.kind),
);

const allowsSurprise = computed(
    () => selectedKind.value?.allows_surprise ?? false,
);

const surprisePreview = ref<string | null>(null);

const hasStoredSurprise = computed(
    () =>
        Boolean(props.hasSurpriseImage) &&
        !props.form.remove_surprise_image &&
        !props.form.surprise_image,
);

function revokeSurprisePreview() {
    if (surprisePreview.value) {
        URL.revokeObjectURL(surprisePreview.value);
        surprisePreview.value = null;
    }
}

function onSurpriseImageSelected(event: globalThis.Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';

    revokeSurprisePreview();

    if (!file) {
        props.form.surprise_image = null;
        return;
    }

    props.form.surprise_image = file;
    props.form.remove_surprise_image = false;
    surprisePreview.value = URL.createObjectURL(file);
}

function clearSurpriseImage() {
    props.form.surprise_image = null;
    props.form.surprise_password = '';
    revokeSurprisePreview();

    if (props.hasSurpriseImage) {
        props.form.remove_surprise_image = true;
    }
}

watch(allowsSurprise, (allowed) => {
    if (!allowed) {
        props.form.surprise_image = null;
        props.form.surprise_password = '';
        revokeSurprisePreview();
    }
});

onUnmounted(() => {
    revokePreview();
    revokeSurprisePreview();
});
</script>

<template>
    <form @submit.prevent="emit('submit')">
        <div class="flex w-full flex-col gap-y-6 md:gap-y-8">
            <UFormField
                label="Název"
                name="title"
                :error="props.form.errors.title"
                required
            >
                <UInput v-model="props.form.title" class="w-full" required />
            </UFormField>

            <UFormField
                label="Typ"
                name="kind"
                :error="props.form.errors.kind"
                required
            >
                <USelect
                    v-model="props.form.kind"
                    class="w-full"
                    :items="kindOptions"
                    :icon="selectedKind?.icon"
                    required
                />
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
                    required
                />
            </UFormField>

            <UFormField
                label="Datum"
                name="date"
                :error="props.form.errors.start_at"
                required
            >
                <div class="flex items-stretch gap-2">
                    <UInput
                        v-model="props.form.date"
                        type="date"
                        class="w-full"
                        required
                    />
                    <UButton
                        type="button"
                        color="neutral"
                        variant="outline"
                        icon="i-lucide-calendar-check"
                        @click="props.form.date = getTodayDateString()"
                    >
                        Dnes
                    </UButton>
                </div>
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
                        required
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
                    :rows="5"
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

            <!-- Surprise Section -->
            <div
                v-if="allowsSurprise"
                class="flex flex-col gap-y-4 rounded-xl border border-gray-200 p-4 dark:border-gray-700"
            >
                <div>
                    <div class="flex items-center gap-x-2">
                        <UIcon name="i-lucide-gift" class="size-5" />
                        <h3 class="font-bold">Tajný obrázek</h3>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        Obrázek se v detailu eventu zobrazí až po zadání hesla.
                        Heslo znáš jen ty, druhé kapybaře pomůže nápověda.
                    </p>
                </div>

                <UFormField
                    label="Obrázek za heslem"
                    name="surprise_image"
                    :error="props.form.errors.surprise_image"
                >
                    <div class="flex flex-col gap-3">
                        <div
                            v-if="surprisePreview"
                            class="relative overflow-hidden rounded-xl border border-gray-200 shadow-sm dark:border-gray-700"
                        >
                            <img
                                :src="surprisePreview"
                                alt="Tajný obrázek"
                                class="h-48 w-full object-cover"
                            />
                            <button
                                type="button"
                                class="absolute top-2 right-2 flex items-center gap-1 rounded-md bg-red-500/90 px-3 py-1.5 text-xs font-medium text-white shadow-sm transition hover:bg-red-600 focus:ring-2 focus:ring-red-500 focus:outline-none"
                                @click="clearSurpriseImage()"
                            >
                                <UIcon name="i-lucide-trash-2" class="size-4" />
                                Odebrat
                            </button>
                        </div>

                        <div
                            v-else-if="hasStoredSurprise"
                            class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 p-3 text-sm dark:border-gray-700"
                        >
                            <span class="flex items-center gap-2">
                                <UIcon name="i-lucide-lock" class="size-4" />
                                Tajný obrázek je nahraný a zamčený
                            </span>
                            <UButton
                                type="button"
                                color="error"
                                variant="soft"
                                size="xs"
                                icon="i-lucide-trash-2"
                                @click="clearSurpriseImage()"
                            >
                                Odebrat
                            </UButton>
                        </div>

                        <label
                            class="flex h-32 cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50/50 px-3 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50 dark:hover:bg-gray-800"
                        >
                            <div
                                class="rounded-full bg-primary-50 p-2 text-primary-500 dark:bg-primary-900/30"
                            >
                                <UIcon name="i-lucide-gift" class="size-6" />
                            </div>
                            <div class="text-center">
                                <span
                                    class="text-sm font-medium text-primary-600 dark:text-primary-400"
                                >
                                    {{
                                        surprisePreview || hasStoredSurprise
                                            ? 'Nahrát jiný tajný obrázek'
                                            : 'Nahrát tajný obrázek (klikněte)'
                                    }}
                                </span>
                                <p class="mt-1 text-xs text-gray-500">
                                    PNG, JPG, GIF do 5MB
                                </p>
                            </div>
                            <input
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="onSurpriseImageSelected"
                            />
                        </label>
                    </div>
                </UFormField>

                <UFormField
                    v-if="props.form.surprise_image"
                    label="Heslo"
                    name="surprise_password"
                    help="Na velikosti písmen nezáleží."
                    :error="props.form.errors.surprise_password"
                    required
                >
                    <UInput
                        v-model="props.form.surprise_password"
                        class="w-full"
                        autocomplete="off"
                        required
                    />
                </UFormField>

                <UFormField
                    v-if="props.form.surprise_image || hasStoredSurprise"
                    label="Nápověda k heslu"
                    name="surprise_hint"
                    :error="props.form.errors.surprise_hint"
                >
                    <UInput
                        v-model="props.form.surprise_hint"
                        class="w-full"
                        maxlength="255"
                        placeholder="Třeba: kam jsme jeli na první výlet?"
                    />
                </UFormField>
            </div>

            <UFormField label="Štítky" name="tags">
                <TagSelectMenu
                    v-model="selectedTags"
                    :items="props.availableTags || []"
                    placeholder="Vyber štítky..."
                    search-input-placeholder="Hledat štítek..."
                />
            </UFormField>

            <USwitch
                label="Zobrazovat odpočet"
                v-model="props.form.countdown_enabled"
                :error="props.form.errors.countdown_enabled"
            />

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
    </form>
</template>
