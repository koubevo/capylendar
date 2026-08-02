<script setup lang="ts">
import MarkdownInline from '@/components/documents/MarkdownInline.vue';
import { computed } from 'vue';

type MarkdownBlock =
    | { type: 'heading'; level: number; text: string }
    | { type: 'list'; items: string[] }
    | { type: 'paragraph'; lines: string[] }
    | { type: 'separator' }
    | { type: 'table'; headers: string[]; rows: string[][] };

const props = defineProps<{
    content: string;
}>();

const blocks = computed<MarkdownBlock[]>(() => parseMarkdown(props.content));

function parseMarkdown(content: string): MarkdownBlock[] {
    const lines = content.replace(/\r\n/g, '\n').split('\n');
    const parsedBlocks: MarkdownBlock[] = [];
    let index = 0;

    while (index < lines.length) {
        const line = lines[index] ?? '';

        if (line.trim() === '') {
            index += 1;
            continue;
        }

        const heading = line.match(/^(#{1,3})\s+(.+)$/);

        if (heading) {
            parsedBlocks.push({
                type: 'heading',
                level: heading[1].length,
                text: heading[2].trim(),
            });
            index += 1;
            continue;
        }

        if (isTableStart(lines, index)) {
            const headers = normalizeCells(lines[index]);
            const rows: string[][] = [];
            index += 2;

            while (index < lines.length && lines[index]?.includes('|')) {
                rows.push(normalizeCells(lines[index] ?? ''));
                index += 1;
            }

            parsedBlocks.push({ type: 'table', headers, rows });
            continue;
        }

        if (isSeparator(line)) {
            parsedBlocks.push({ type: 'separator' });
            index += 1;
            continue;
        }

        if (/^\s*[-*]\s+/.test(line)) {
            const items: string[] = [];

            while (
                index < lines.length &&
                /^\s*[-*]\s+/.test(lines[index] ?? '')
            ) {
                items.push((lines[index] ?? '').replace(/^\s*[-*]\s+/, ''));
                index += 1;
            }

            parsedBlocks.push({ type: 'list', items });
            continue;
        }

        const paragraphLines: string[] = [];

        while (
            index < lines.length &&
            lines[index]?.trim() !== '' &&
            !/^(#{1,3})\s+/.test(lines[index] ?? '') &&
            !/^\s*[-*]\s+/.test(lines[index] ?? '') &&
            !isSeparator(lines[index] ?? '') &&
            !isTableStart(lines, index)
        ) {
            paragraphLines.push(lines[index] ?? '');
            index += 1;
        }

        parsedBlocks.push({ type: 'paragraph', lines: paragraphLines });
    }

    return parsedBlocks;
}

function isTableStart(lines: string[], index: number): boolean {
    const current = lines[index] ?? '';
    const next = lines[index + 1] ?? '';

    return (
        current.includes('|') &&
        /^\s*\|?\s*:?-{3,}:?\s*(\|\s*:?-{3,}:?\s*)+\|?\s*$/.test(next)
    );
}

function isSeparator(line: string): boolean {
    return /^\s*(?:-{3,}|\*{3,}|_{3,})\s*$/.test(line);
}

function normalizeCells(line: string): string[] {
    return line
        .trim()
        .replace(/^\|/, '')
        .replace(/\|$/, '')
        .split('|')
        .map((cell) => cell.trim());
}

function headingTag(level: number): string {
    if (level === 1) {
        return 'h2';
    }

    if (level === 2) {
        return 'h3';
    }

    return 'h4';
}

function headingClass(level: number): string {
    if (level === 1) {
        return 'text-2xl font-semibold';
    }

    if (level === 2) {
        return 'text-xl font-semibold';
    }

    return 'text-lg font-semibold';
}
</script>

<template>
    <div class="flex flex-col gap-y-5 text-neutral-800 dark:text-neutral-100">
        <template v-for="(block, blockIndex) in blocks" :key="blockIndex">
            <component
                :is="headingTag(block.level)"
                v-if="block.type === 'heading'"
                :class="headingClass(block.level)"
            >
                <MarkdownInline :content="block.text" />
            </component>

            <p
                v-else-if="block.type === 'paragraph'"
                class="leading-7 whitespace-pre-wrap"
            >
                <template
                    v-for="(line, lineIndex) in block.lines"
                    :key="lineIndex"
                >
                    <br v-if="lineIndex > 0" />
                    <MarkdownInline :content="line" />
                </template>
            </p>

            <ul
                v-else-if="block.type === 'list'"
                class="flex list-disc flex-col gap-y-2 pl-5"
            >
                <li v-for="(item, itemIndex) in block.items" :key="itemIndex">
                    <MarkdownInline :content="item" />
                </li>
            </ul>

            <hr
                v-else-if="block.type === 'separator'"
                class="border-t border-neutral-200 dark:border-neutral-800"
            />

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-120 text-left text-sm">
                    <thead>
                        <tr
                            class="border-b border-neutral-200 dark:border-neutral-800"
                        >
                            <th
                                v-for="(header, headerIndex) in block.headers"
                                :key="headerIndex"
                                class="px-3 py-2 font-semibold"
                            >
                                <MarkdownInline :content="header" />
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(row, rowIndex) in block.rows"
                            :key="rowIndex"
                            class="border-b border-neutral-100 last:border-b-0 dark:border-neutral-800"
                        >
                            <td
                                v-for="(cell, cellIndex) in row"
                                :key="cellIndex"
                                class="px-3 py-2 align-top"
                            >
                                <MarkdownInline :content="cell" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </div>
</template>
