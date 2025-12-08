<script setup lang="ts">
import { User } from '@/types';
import InfoParagraph from '@/components/ui/InfoParagraph.vue';

const props = defineProps<{
    description?: string;
    updated_at_human?: string;
    created_at_human: string;
    author: User;
    classes: string;
}>();
</script>

<template>
    <UCard :class="props.classes">
        <InfoParagraph v-if="props.description" label="Popis" :value="props.description" />
        <InfoParagraph v-if="props.updated_at_human" label="Upraveno" :value="props.updated_at_human" />
        <InfoParagraph v-else label="Přidáno" :value="props.created_at_human" />
        <InfoParagraph label="Autor" :value="props.author.name" />
    </UCard>
</template>
