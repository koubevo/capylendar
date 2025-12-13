<script setup lang="ts">
import { Capybara } from '@/types/Capybara';
import { EventFilters } from '@/types/Filters';
import { Tag } from '@/types/Tag';
import { useDebounceFn } from '@vueuse/core';
import { ref, watch } from 'vue';

interface Props {
    filters: EventFilters;
    capybaraOptions: Capybara[];
    availableTags: Tag[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'change', filters: typeof props.filters): void;
}>();

const form = ref({
    search: props.filters?.search || '',
    capybara: props.filters?.capybara || null,
    tags: props.filters?.tags?.map(Number) || [],
});

const emitChange = useDebounceFn(() => {
    emit('change', form.value);
}, 300);

watch(form, () => emitChange(), { deep: true });

const reset = () => {
    form.value = {
        search: '',
        capybara: null,
        tags: [],
    };
};
</script>

<template>
    <div class="flex flex-col gap-y-4 py-2">
        <UFormField label="Hledat" name="search">
            <UInput
                v-model="form.search"
                placeholder="Název nebo popis..."
                class="w-full"
            />
        </UFormField>

        <UFormField label="Pro" name="capybara">
            <USelect
                v-model="form.capybara"
                :items="props.capybaraOptions"
                placeholder="Všechny"
                class="w-full"
                clearable
            />
        </UFormField>

        <UFormField label="Štítky" name="tags">
            <USelectMenu
                v-model="form.tags"
                :items="props.availableTags"
                multiple
                value-key="id"
                placeholder="Filtrovat štítky..."
                class="w-full"
            >
                <template #option="{ option }">
                    <span
                        class="h-2 w-2 rounded-full"
                        :style="{ backgroundColor: option.color }"
                    ></span>
                    <span class="truncate">{{ option.label }}</span>
                </template>
            </USelectMenu>
        </UFormField>
        <div class="flex justify-end text-sm">
            <UButton
                color="neutral"
                variant="ghost"
                icon="i-lucide-x"
                size="sm"
                @click="reset"
            >
                Vyresetovat filtry
            </UButton>
        </div>
    </div>
</template>
