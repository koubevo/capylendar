<script setup lang="ts">
/* eslint-disable vue/no-mutating-props */
import PrimaryButton from '@/components/buttons/PrimaryButton.vue';
import type { DocumentFormData } from '@/types/DocumentFormData';
import type { InertiaForm } from '@inertiajs/vue3';
import { Form } from '@inertiajs/vue3';
import { nextTick, ref, type ComponentPublicInstance } from 'vue';

const props = defineProps<{
    form: InertiaForm<DocumentFormData>;
    isEditMode: boolean;
}>();

const emit = defineEmits<{
    (e: 'submit'): void;
}>();

const bodyTextarea = ref<ComponentPublicInstance | null>(null);
const isTablePickerOpen = ref(false);
const selectedTableRows = ref(2);
const selectedTableColumns = ref(2);
const tableRows = [1, 2, 3, 4, 5, 6];
const tableColumns = [1, 2, 3, 4];
let tablePickerCloseTimeout: ReturnType<typeof setTimeout> | null = null;

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
        props.form.body += `${prefix}${fallback}${suffix}`;
        return;
    }

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selected = props.form.body.slice(start, end);
    const replacement = `${prefix}${selected || fallback}${suffix}`;

    props.form.body =
        props.form.body.slice(0, start) +
        replacement +
        props.form.body.slice(end);

    await focusBody(
        start + prefix.length,
        start + replacement.length - suffix.length,
    );
}

async function insertHeading(): Promise<void> {
    const textarea = getTextarea();

    if (!textarea) {
        props.form.body += '\n## Nadpis';
        return;
    }

    const start = textarea.selectionStart;
    const lineStart = props.form.body.lastIndexOf('\n', start - 1) + 1;
    const lineEndIndex = props.form.body.indexOf('\n', start);
    const lineEnd = lineEndIndex === -1 ? props.form.body.length : lineEndIndex;
    const line = props.form.body.slice(lineStart, lineEnd);
    const hasHeading = line.startsWith('## ');
    const nextLine = hasHeading ? line.slice(3) : `## ${line || 'Nadpis'}`;

    props.form.body =
        props.form.body.slice(0, lineStart) +
        nextLine +
        props.form.body.slice(lineEnd);

    const selectionStart = hasHeading ? lineStart : lineStart + 3;
    const selectionEnd = lineStart + nextLine.length;

    await focusBody(selectionStart, selectionEnd);
}

function openTablePicker(): void {
    if (tablePickerCloseTimeout) {
        clearTimeout(tablePickerCloseTimeout);
    }

    isTablePickerOpen.value = true;
}

function closeTablePickerSoon(): void {
    if (tablePickerCloseTimeout) {
        clearTimeout(tablePickerCloseTimeout);
    }

    tablePickerCloseTimeout = setTimeout(() => {
        isTablePickerOpen.value = false;
        selectTableSize(2, 2);
    }, 180);
}

function selectTableSize(rows: number, columns: number): void {
    selectedTableRows.value = Math.max(2, rows);
    selectedTableColumns.value = Math.max(2, columns);
}

async function insertTable(
    rows = selectedTableRows.value,
    columns = selectedTableColumns.value,
): Promise<void> {
    isTablePickerOpen.value = false;

    const textarea = getTextarea();
    const table = buildTable(rows, columns);

    if (!textarea) {
        props.form.body += `\n${table}`;
        return;
    }

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const needsLeadingBreak =
        start > 0 && !props.form.body.slice(0, start).endsWith('\n\n');
    const needsTrailingBreak =
        end < props.form.body.length &&
        !props.form.body.slice(end).startsWith('\n');
    const leadingBreak = needsLeadingBreak ? '\n\n' : '';
    const trailingBreak = needsTrailingBreak ? '\n\n' : '';
    const replacement = `${leadingBreak}${table}${trailingBreak}`;

    props.form.body =
        props.form.body.slice(0, start) +
        replacement +
        props.form.body.slice(end);

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
</script>

<template>
    <Form @submit.prevent="emit('submit')">
        <div class="flex w-full flex-col gap-y-6 md:gap-y-8">
            <UFormField
                label="Nazev"
                name="title"
                :error="props.form.errors.title"
                required
            >
                <UInput v-model="props.form.title" class="w-full" />
            </UFormField>

            <UFormField
                label="Obsah"
                name="body"
                :error="props.form.errors.body"
            >
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

                        <UTooltip text="Nadpis">
                            <UButton
                                type="button"
                                color="neutral"
                                variant="ghost"
                                size="sm"
                                icon="i-lucide-heading-2"
                                aria-label="Nadpis"
                                class="h-9 w-9 justify-center rounded-md"
                                @click="insertHeading"
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
                                    @mouseleave="selectTableSize(2, 2)"
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
                        v-model="props.form.body"
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
                    />
                </div>
            </UFormField>

            <PrimaryButton
                class="w-full justify-center"
                type="submit"
                :loading="props.form.processing"
            >
                {{ props.isEditMode ? 'Upravit' : 'Pridat' }}
            </PrimaryButton>
        </div>
    </Form>
</template>
