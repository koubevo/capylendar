<script setup lang="ts">
/* eslint-disable vue/no-mutating-props */
import PrimaryButton from '@/components/buttons/PrimaryButton.vue';
import MacroAlert from '@/components/ui/MacroAlert.vue';
import { hasGoogleMapUrl } from '@/lib/utils';
import { Capybara } from '@/types/Capybara';
import type { TodoFormData } from '@/types/TodoFormData';
import type { TodoPriority } from '@/types/Todo';
import { Tag } from '@/types/Tag';
import type { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    form: InertiaForm<TodoFormData>;
    isEditMode: boolean;
    capybaraOptions: Capybara[];
    priorityOptions: TodoPriority[];
    availableTags?: Tag[];
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
                label="Deadline"
                name="deadline"
                :error="props.form.errors.deadline"
                required
            >
                <UInput
                    v-model="props.form.deadline"
                    type="date"
                    class="w-full"
                />
            </UFormField>

            <UFormField
                label="Priorita"
                name="priority"
                :error="props.form.errors.priority"
                required
            >
                <USelect
                    v-model="props.form.priority"
                    class="w-full"
                    :items="priorityOptions"
                    placeholder="Vyber prioritu"
                />
            </UFormField>

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
                label="Soukromé (tajné kapybara todo)"
                v-model="props.form.is_private"
                :error="props.form.errors.is_private"
            />

            <PrimaryButton class="w-full justify-center" type="submit">
                {{ props.isEditMode ? 'Upravit' : 'Přidat' }}
            </PrimaryButton>
        </div>
    </form>
</template>
