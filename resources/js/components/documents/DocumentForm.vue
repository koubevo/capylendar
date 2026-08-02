<script setup lang="ts">
import PrimaryButton from '@/components/buttons/PrimaryButton.vue';
import type { DocumentFormData } from '@/types/DocumentFormData';
import { useForm } from '@inertiajs/vue3';
import { nextTick, onUnmounted, ref, type ComponentPublicInstance } from 'vue';

type SubmitMethod = 'post' | 'put';
type HeadingLevel = 2 | 3;

const props = withDefaults(
    defineProps<{
        initialTitle?: string;
        initialBody?: string;
        isEditMode: boolean;
        submitUrl: string;
        submitMethod?: SubmitMethod;
    }>(),
    {
        initialTitle: '',
        initialBody: '',
        submitMethod: 'post',
    },
);

const form = useForm<DocumentFormData>({
    title: props.initialTitle,
    body: props.initialBody,
});

const bodyTextarea = ref<ComponentPublicInstance | null>(null);
const isTablePickerOpen = ref(false);
const selectedTableRows = ref(1);
const selectedTableColumns = ref(1);
const minTableSize = 1;
const maxTableRows = 10;
const maxTableColumns = 4;
const tableRows = Array.from(
    { length: maxTableRows - minTableSize + 1 },
    (_, index) => minTableSize + index,
);
const tableColumns = Array.from(
    { length: maxTableColumns - minTableSize + 1 },
    (_, index) => minTableSize + index,
);
let tablePickerCloseTimeout: ReturnType<typeof setTimeout> | null = null;

function submit(): void {
    if (props.submitMethod === 'put') {
        form.put(props.submitUrl);
        return;
    }

    form.post(props.submitUrl);
}

function getTextarea(): HTMLTextAreaElement | null {
    const element = bodyTextarea.value?.$el;

    if (element instanceof HTMLTextAreaElement) {
        return element;
    }

    if (element instanceof HTMLElement) {
        return element.querySelector('textarea');
    }

    return null;
}

async function focusBody(
    selectionStart?: number,
    selectionEnd = selectionStart,
): Promise<void> {
    await nextTick();

    const textarea = getTextarea();

    if (!textarea) {
        return;
    }

    textarea.focus();

    if (selectionStart !== undefined && selectionEnd !== undefined) {
        textarea.setSelectionRange(selectionStart, selectionEnd);
    }
}

async function wrapSelection(
    prefix: string,
    suffix: string,
    fallback: string,
): Promise<void> {
    const textarea = getTextarea();

    if (!textarea) {
        form.body += `${prefix}${fallback}${suffix}`;
        return;
    }

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selected = form.body.slice(start, end);
    const replacement = `${prefix}${selected || fallback}${suffix}`;

    form.body = form.body.slice(0, start) + replacement + form.body.slice(end);

    await focusBody(
        start + prefix.length,
        start + replacement.length - suffix.length,
    );
}

async function insertHeading(level: HeadingLevel): Promise<void> {
    const headingPrefix = '#'.repeat(level);
    const textarea = getTextarea();

    if (!textarea) {
        form.body += '\n' + headingPrefix + ' Nadpis';
        return;
    }

    const start = textarea.selectionStart;
    const lineStart = form.body.lastIndexOf('\n', start - 1) + 1;
    const lineEndIndex = form.body.indexOf('\n', start);
    const lineEnd = lineEndIndex === -1 ? form.body.length : lineEndIndex;
    const line = form.body.slice(lineStart, lineEnd);
    const heading = line.match(/^(#{1,6})\s+(.+)$/);
    const hasHeading = heading?.[1].length === level;
    const headingText = heading?.[2] ?? line;
    const nextLine = hasHeading
        ? headingText
        : headingPrefix + ' ' + (headingText || 'Nadpis');

    form.body =
        form.body.slice(0, lineStart) + nextLine + form.body.slice(lineEnd);

    const selectionStart = hasHeading
        ? lineStart
        : lineStart + headingPrefix.length + 1;
    const selectionEnd = lineStart + nextLine.length;

    await focusBody(selectionStart, selectionEnd);
}

async function insertSeparator(): Promise<void> {
    const separator = '---';
    const textarea = getTextarea();

    if (!textarea) {
        form.body += '\n' + separator;
        return;
    }

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const needsLeadingBreak =
        start > 0 && !form.body.slice(0, start).endsWith('\n\n');
    const needsTrailingBreak =
        end < form.body.length && !form.body.slice(end).startsWith('\n');
    const leadingBreak = needsLeadingBreak ? '\n\n' : '';
    const trailingBreak = needsTrailingBreak ? '\n\n' : '';
    const replacement = leadingBreak + separator + trailingBreak;

    form.body = form.body.slice(0, start) + replacement + form.body.slice(end);

    const cursorPosition = start + leadingBreak.length + separator.length;

    await focusBody(cursorPosition, cursorPosition);
}

async function continueListOnEnter(event: KeyboardEvent): Promise<void> {
    const textarea =
        event.target instanceof HTMLTextAreaElement
            ? event.target
            : getTextarea();

    if (!textarea || textarea.selectionStart !== textarea.selectionEnd) {
        return;
    }

    const start = textarea.selectionStart;
    const lineStart = form.body.lastIndexOf('\n', start - 1) + 1;
    const lineEndIndex = form.body.indexOf('\n', start);
    const lineEnd = lineEndIndex === -1 ? form.body.length : lineEndIndex;
    const line = form.body.slice(lineStart, lineEnd);
    const beforeCaret = form.body.slice(lineStart, start);
    const bullet = beforeCaret.match(/^(\s*[-*]\s+)/);

    if (!bullet) {
        return;
    }

    event.preventDefault();

    if (/^\s*[-*]\s*$/.test(line)) {
        form.body = form.body.slice(0, lineStart) + form.body.slice(lineEnd);
        await focusBody(lineStart, lineStart);
        return;
    }

    const replacement = `\n${bullet[1]}`;

    form.body =
        form.body.slice(0, start) + replacement + form.body.slice(start);

    await focusBody(start + replacement.length, start + replacement.length);
}

function openTablePicker(): void {
    if (tablePickerCloseTimeout) {
        clearTimeout(tablePickerCloseTimeout);
        tablePickerCloseTimeout = null;
    }

    isTablePickerOpen.value = true;
}

function closeTablePickerSoon(): void {
    if (tablePickerCloseTimeout) {
        clearTimeout(tablePickerCloseTimeout);
    }

    tablePickerCloseTimeout = setTimeout(() => {
        isTablePickerOpen.value = false;
        selectTableSize(1, 1);
        tablePickerCloseTimeout = null;
    }, 180);
}

function selectTableSize(rows: number, columns: number): void {
    selectedTableRows.value = clampTableRows(rows);
    selectedTableColumns.value = clampTableColumns(columns);
}

function clampTableRows(rows: number): number {
    return Math.min(maxTableRows, Math.max(minTableSize, rows));
}

function clampTableColumns(columns: number): number {
    return Math.min(maxTableColumns, Math.max(minTableSize, columns));
}

async function insertTable(
    rows = selectedTableRows.value,
    columns = selectedTableColumns.value,
): Promise<void> {
    const normalizedRows = clampTableRows(rows);
    const normalizedColumns = clampTableColumns(columns);

    isTablePickerOpen.value = false;

    const textarea = getTextarea();
    const table = buildTable(normalizedRows, normalizedColumns);

    if (!textarea) {
        form.body += `\n${table}`;
        return;
    }

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const needsLeadingBreak =
        start > 0 && !form.body.slice(0, start).endsWith('\n\n');
    const needsTrailingBreak =
        end < form.body.length && !form.body.slice(end).startsWith('\n');
    const leadingBreak = needsLeadingBreak ? '\n\n' : '';
    const trailingBreak = needsTrailingBreak ? '\n\n' : '';
    const replacement = `${leadingBreak}${table}${trailingBreak}`;

    form.body = form.body.slice(0, start) + replacement + form.body.slice(end);

    const firstCellStart = start + leadingBreak.length + table.indexOf('Text');

    await focusBody(firstCellStart, firstCellStart + 'Text'.length);
}

function buildTable(rows: number, columns: number): string {
    const headers = Array.from(
        { length: columns },
        (_, index) => `Sloupec ${index + 1}`,
    );
    const separator = Array.from({ length: columns }, () => '---');
    const bodyRows = Array.from({ length: rows - 1 }, () =>
        Array.from({ length: columns }, () => 'Text'),
    );
    const tableRows = [headers, separator, ...bodyRows];

    return tableRows.map((row) => `| ${row.join(' | ')} |`).join('\n');
}

onUnmounted(() => {
    if (tablePickerCloseTimeout) {
        clearTimeout(tablePickerCloseTimeout);
    }
});
</script>

<template>
    <form @submit.prevent="submit">
        <div class="flex w-full flex-col gap-y-6 md:gap-y-8">
            <UFormField
                label="Nazev"
                name="title"
                :error="form.errors.title"
                required
            >
                <UInput v-model="form.title" class="w-full" />
            </UFormField>

            <UFormField label="Obsah" name="body" :error="form.errors.body">
                <div
                    class="rounded-xl border border-neutral-200 bg-white shadow-sm ring-1 ring-black/5 transition focus-within:border-primary focus-within:ring-primary/20 dark:border-neutral-800 dark:bg-neutral-950 dark:ring-white/10"
                >
                    <div
                        class="flex items-center gap-1 rounded-t-xl border-b border-neutral-200 bg-neutral-50/80 px-2 py-1.5 dark:border-neutral-800 dark:bg-neutral-900/80"
                    >
                        <UTooltip text="Tucne (Ctrl+B)">
                            <UButton
                                type="button"
                                color="neutral"
                                variant="ghost"
                                size="sm"
                                icon="i-lucide-bold"
                                aria-label="Tucne"
                                class="h-9 w-9 justify-center rounded-md"
                                @click="wrapSelection('**', '**', 'tucny text')"
                            />
                        </UTooltip>

                        <UTooltip text="Nadpis 2">
                            <UButton
                                type="button"
                                color="neutral"
                                variant="ghost"
                                size="sm"
                                icon="i-lucide-heading-2"
                                aria-label="Nadpis 2"
                                class="h-9 w-9 justify-center rounded-md"
                                @click="insertHeading(2)"
                            />
                        </UTooltip>

                        <UTooltip text="Nadpis 3">
                            <UButton
                                type="button"
                                color="neutral"
                                variant="ghost"
                                size="sm"
                                icon="i-lucide-heading-3"
                                aria-label="Nadpis 3"
                                class="h-9 w-9 justify-center rounded-md"
                                @click="insertHeading(3)"
                            />
                        </UTooltip>

                        <UTooltip text="Oddelovac">
                            <UButton
                                type="button"
                                color="neutral"
                                variant="ghost"
                                size="sm"
                                icon="i-lucide-separator-horizontal"
                                aria-label="Oddelovac"
                                class="h-9 w-9 justify-center rounded-md"
                                @click="insertSeparator"
                            />
                        </UTooltip>

                        <div
                            class="relative"
                            @mouseenter="openTablePicker"
                            @mouseleave="closeTablePickerSoon"
                            @focusin="openTablePicker"
                            @focusout="closeTablePickerSoon"
                        >
                            <UTooltip text="Tabulka">
                                <UButton
                                    type="button"
                                    color="neutral"
                                    variant="ghost"
                                    size="sm"
                                    icon="i-lucide-table"
                                    aria-label="Tabulka"
                                    class="h-9 w-9 justify-center rounded-md"
                                    @click.stop="openTablePicker"
                                />
                            </UTooltip>

                            <div
                                class="absolute top-full left-0 z-20 h-3 w-56"
                            />

                            <div
                                v-show="isTablePickerOpen"
                                class="absolute top-full left-0 z-20 mt-2 w-50 rounded-lg border border-neutral-200 bg-white p-3 shadow-lg ring-1 ring-black/5 dark:border-neutral-800 dark:bg-neutral-950 dark:ring-white/10"
                            >
                                <div
                                    class="mb-2 flex items-center justify-between gap-3 text-xs text-neutral-500 dark:text-neutral-400"
                                >
                                    <span>Tabulka</span>
                                    <span>
                                        {{ selectedTableColumns }} sl. x
                                        {{ selectedTableRows }} rad.
                                    </span>
                                </div>

                                <div
                                    class="grid grid-cols-4 gap-1"
                                    @mouseleave="selectTableSize(1, 1)"
                                >
                                    <template
                                        v-for="row in tableRows"
                                        :key="row"
                                    >
                                        <button
                                            v-for="column in tableColumns"
                                            :key="`${row}-${column}`"
                                            type="button"
                                            class="h-6 rounded border transition"
                                            :class="
                                                row <= selectedTableRows &&
                                                column <= selectedTableColumns
                                                    ? 'border-primary bg-primary/15'
                                                    : 'border-neutral-200 bg-neutral-50 hover:border-primary/50 dark:border-neutral-800 dark:bg-neutral-900'
                                            "
                                            :aria-label="`Vlozit tabulku ${column} sloupcu x ${row} radku`"
                                            @mouseenter="
                                                selectTableSize(row, column)
                                            "
                                            @focus="
                                                selectTableSize(row, column)
                                            "
                                            @click="insertTable(row, column)"
                                        />
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <UTextarea
                        ref="bodyTextarea"
                        v-model="form.body"
                        variant="none"
                        class="w-full font-mono text-sm leading-6"
                        :ui="{
                            base: 'min-h-96 resize-y rounded-none px-4 py-3 focus-visible:ring-0',
                        }"
                        :rows="18"
                        @keydown.ctrl.b.prevent="
                            wrapSelection('**', '**', 'tucny text')
                        "
                        @keydown.meta.b.prevent="
                            wrapSelection('**', '**', 'tucny text')
                        "
                        @keydown.enter="continueListOnEnter"
                    />
                </div>
            </UFormField>

            <PrimaryButton
                class="w-full justify-center"
                type="submit"
                :loading="form.processing"
            >
                {{ props.isEditMode ? 'Upravit' : 'Pridat' }}
            </PrimaryButton>
        </div>
    </form>
</template>
