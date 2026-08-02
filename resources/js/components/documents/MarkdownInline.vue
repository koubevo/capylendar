<script setup lang="ts">
import { extractDomain, linkify, toLinkHref } from '@/composables/useLinkify';
import { computed } from 'vue';

type InlineSegment =
    | { type: 'text' | 'strong'; text: string }
    | { type: 'link'; text: string; href: string };

const props = defineProps<{
    content: string;
}>();

const segments = computed(() => parseInline(props.content));

function parseInline(content: string): InlineSegment[] {
    const parsedSegments: InlineSegment[] = [];
    const strongPattern = /\*\*(.+?)\*\*/g;
    let lastIndex = 0;
    let match: RegExpExecArray | null;

    while ((match = strongPattern.exec(content)) !== null) {
        appendLinkifiedText(
            parsedSegments,
            content.slice(lastIndex, match.index),
            'text',
        );
        appendLinkifiedText(parsedSegments, match[1], 'strong');
        lastIndex = strongPattern.lastIndex;
    }

    appendLinkifiedText(parsedSegments, content.slice(lastIndex), 'text');

    return parsedSegments;
}

function appendLinkifiedText(
    segments: InlineSegment[],
    value: string,
    textType: 'text' | 'strong',
): void {
    for (const segment of linkify(value)) {
        if (segment.type === 'link') {
            segments.push({
                type: 'link',
                text: extractDomain(segment.value),
                href: toLinkHref(segment.value),
            });
            continue;
        }

        segments.push({
            type: textType,
            text: segment.value,
        });
    }
}
</script>

<template>
    <span>
        <template v-for="(segment, index) in segments" :key="index">
            <strong v-if="segment.type === 'strong'">
                {{ segment.text }}
            </strong>
            <a
                v-else-if="segment.type === 'link'"
                :href="segment.href"
                target="_blank"
                rel="noopener noreferrer"
                class="my-0.5 inline-flex items-center gap-1 rounded-md bg-blue-50 px-1.5 py-0.5 text-blue-700 transition hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50"
            >
                <UIcon name="i-lucide-external-link" class="size-3 shrink-0" />
                {{ segment.text }}
            </a>
            <span v-else>{{ segment.text }}</span>
        </template>
    </span>
</template>
