<script setup lang="ts">
import {
    extractDomain,
    linkify,
    toLinkHref,
} from '@/composables/useLinkify';
import { computed } from 'vue';

const props = defineProps<{
    label: string;
    value: string;
    linkClasses?: string;
}>();

const segments = computed(() => linkify(props.value));

const baseLinkClasses = 'my-0.5 inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 transition';
const defaultThemeClasses = 'bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50';

const computedLinkClasses = computed(() => {
    return `${baseLinkClasses} ${props.linkClasses || defaultThemeClasses}`;
});
</script>

<template>
    <div>
        <h4 class="text-[10px] font-bold">{{ props.label }}</h4>
        <div class="whitespace-pre-wrap leading-relaxed"><template
                v-for="(segment, i) in segments"
                :key="i"
                ><a
                    v-if="segment.type === 'link'"
                    :href="toLinkHref(segment.value)"
                    target="_blank"
                    rel="noopener noreferrer"
                    :class="computedLinkClasses"
                    ><UIcon name="i-lucide-external-link" class="size-3 shrink-0" />{{ extractDomain(segment.value) }}</a
                ><template v-else>{{ segment.value }}</template></template
            ></div>
    </div>
</template>
