<script setup lang="ts">
import { computed } from 'vue';
import InfoParagraph from '@/components/ui/InfoParagraph.vue';

const props = defineProps<{
    url: string;
    title?: string;
    description?: string;
    image?: string;
    classes?: string;
}>();

const domain = computed(() => {
    try {
        const urlObj = new URL(props.url);
        return urlObj.hostname.replace('www.', '').toUpperCase();
    } catch (e) {
        return '';
    }
});
</script>

<template>
    <a
        :href="url"
        target="_blank"
        rel="noopener noreferrer"
        class="block group no-underline rounded-md overflow-hidden"
    >
        <div class="flex flex-col overflow-hidden"
             :class="props.classes">

            <div class="h-48 w-full bg-gray-100 relative overflow-hidden border-b border-gray-100">

                <img
                    v-if="image"
                    :src="image"
                    :alt="title"
                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105 rounded-t-md"
                    loading="lazy"
                    referrerpolicy="no-referrer"
                />

                <div v-else class="h-full w-full flex items-center justify-center bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-gray-300">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                </div>
            </div>

            <div class="flex flex-col gap-y-3 p-5">
                <InfoParagraph
                    :label="domain"
                    :value="title || url"
                    class="truncate"
                />

                <p v-if="description" class="text-sm text-gray-600 line-clamp-3 leading-relaxed">
                    {{ description }}
                </p>
            </div>
        </div>
    </a>
</template>
