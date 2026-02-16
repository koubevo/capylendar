<script setup lang="ts">
import { User } from '@/types';
import InfoParagraph from '@/components/ui/InfoParagraph.vue';

const props = defineProps<{
    description?: string;
    updated_at_human?: string;
    created_at_human: string;
    author: User;
    classes: string;
    linkClasses?: string;
}>();
</script>

<template>
    <UCard :class="props.classes">
        <div class="flex flex-col gap-y-4">
            <InfoParagraph v-if="props.description" label="Popis" :value="props.description" :link-classes="props.linkClasses" />
            <InfoParagraph v-if="props.updated_at_human" label="Upraveno" :value="props.updated_at_human" />
            <InfoParagraph v-else label="Přidáno" :value="props.created_at_human" />
            <InfoParagraph label="Autor" :value="props.author.name" />
        </div>
    </UCard>
</template>
