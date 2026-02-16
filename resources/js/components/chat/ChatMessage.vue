<script setup lang="ts">
import {
    extractDomain,
    linkify,
    toLinkHref,
} from '@/composables/useLinkify';
import type { Message } from '@/types/Message';
import { computed } from 'vue';

interface Props {
    message: Message;
    isCurrentUser: boolean;
}

const props = defineProps<Props>();

const contentSegments = computed(() => linkify(props.message.content));
</script>

<template>
    <div
        class="flex gap-3"
        :class="{
            'flex-row-reverse': props.isCurrentUser,
        }"
    >
        <img
            :src="props.message.user.capybara.avatar.src"
            :alt="props.message.user.name"
            class="h-10 w-10 shrink-0 self-end rounded-md"
        />
        <div
            class="max-w-[75%] rounded-lg p-3"
            :class="props.message.user.capybara.classes"
        >
            <div class="mb-1 flex flex-wrap items-center gap-x-2">
                <span class="text-sm font-semibold">{{
                    props.message.user.name
                }}</span>
                <span class="text-xs text-gray-500">{{
                    props.message.created_at_human
                }}</span>
            </div>
            <div class="text-sm whitespace-pre-wrap"><template
                    v-for="(segment, i) in contentSegments"
                    :key="i"
                    ><a
                        v-if="segment.type === 'link'"
                        :href="toLinkHref(segment.value)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="my-0.5 inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 transition"
                        :class="props.message.user.capybara.link_classes"
                        ><UIcon name="i-lucide-external-link" class="size-3 shrink-0" />{{ extractDomain(segment.value) }}</a
                    ><template v-else>{{ segment.value }}</template></template
                ></div>
        </div>
    </div>
</template>
