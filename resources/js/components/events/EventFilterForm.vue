<script setup lang="ts">
import NeutralButton from '@/components/buttons/NeutralButton.vue';
import { Capybara } from '@/types/Capybara';
import { EventFilters } from '@/types/Filters';
import { Tag } from '@/types/Tag';
import { useDebounceFn } from '@vueuse/core';
import { ref, watch } from 'vue';

interface Props {
    eventFilters: EventFilters;
    capybaraOptions: Capybara[];
    availableTags: Tag[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'change', eventFilters: typeof props.eventFilters): void;
}>();

const form = ref({
    search: props.eventFilters?.search || '',
    capybara: props.eventFilters?.capybara || undefined,
    tags: props.eventFilters?.tags?.map(Number) || [],
});

const emitChange = useDebounceFn(() => {
    emit('change', form.value);
}, 300);

watch(form, () => emitChange(), { deep: true });

const reset = () => {
    form.value = {
        search: '',
        capybara: undefined,
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
                <template #item="{ item }">
                    <span
                        class="h-2 w-2 rounded-full"
                        :style="{ backgroundColor: item.color }"
                    ></span>
                    <span class="truncate">{{ item.label }}</span>
                </template>
            </USelectMenu>
        </UFormField>
        <div class="flex justify-end text-sm">
            <NeutralButton
                variant="ghost"
                icon="i-lucide-x"
                size="sm"
                @click="reset"
            >
                Vyresetovat filtry
            </NeutralButton>
        </div>
    </div>
</template>
