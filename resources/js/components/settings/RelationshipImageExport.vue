<script setup lang="ts">
import {
    canCopyRelationshipImage,
    canShareRelationshipImage,
    createRelationshipImage,
} from '@/lib/relationshipImage';
import { computed, ref, shallowRef } from 'vue';

const props = defineProps<{ card: HTMLElement | null }>();
const open = ref(false);
const preparing = ref(false);
const processing = ref(false);
const file = shallowRef<File | null>(null);
const previewUrl = ref('');
const message = ref('');
const error = ref('');
const canCopy = computed(() => !!file.value && canCopyRelationshipImage());
const canShare = computed(
    () => !!file.value && canShareRelationshipImage(file.value),
);

function clearPreview(): void {
    previewUrl.value = '';
    file.value = null;
}

async function prepare(): Promise<void> {
    if (!props.card || preparing.value) return;
    open.value = true;
    preparing.value = true;
    clearPreview();
    error.value = '';
    message.value = '';
    try {
        const blob = await createRelationshipImage(props.card);
        previewUrl.value = await new Promise<string>((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result as string);
            reader.onerror = () => reject(reader.error);
            reader.readAsDataURL(blob);
        });
        file.value = new File([blob], 'capylendar-spolu.png', {
            type: 'image/png',
        });
    } catch {
        clearPreview();
        error.value = 'Obrázek se nepodařilo připravit. Zkuste to znovu.';
    } finally {
        preparing.value = false;
    }
}

async function exportImage(
    action: 'copy' | 'share' | 'download',
): Promise<void> {
    if (!file.value || processing.value) return;
    processing.value = true;
    error.value = '';
    message.value = '';
    try {
        if (action === 'copy') {
            await navigator.clipboard.write([
                new ClipboardItem({ 'image/png': file.value }),
            ]);
            message.value = 'Obrázek je zkopírovaný do schránky.';
        } else if (action === 'share') {
            await navigator.share({ files: [file.value] });
        } else {
            const link = document.createElement('a');
            link.href = previewUrl.value;
            link.download = file.value.name;
            document.body.append(link);
            link.click();
            link.remove();
        }
    } catch (cause) {
        if (
            action !== 'share' ||
            !(cause instanceof DOMException) ||
            cause.name !== 'AbortError'
        ) {
            error.value =
                action === 'copy'
                    ? 'Kopírování se nepodařilo. Obrázek můžete stáhnout.'
                    : action === 'share'
                      ? 'Sdílení se nepodařilo. Obrázek můžete stáhnout.'
                      : 'Stažení se nepodařilo. Zkuste to znovu.';
        }
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <div>
        <UButton
            icon="i-lucide-image"
            color="neutral"
            variant="outline"
            :disabled="!card || preparing"
            @click="prepare"
            >Sdílet obrázek</UButton
        >
        <UModal
            v-model:open="open"
            title="Obrázek vztahu"
            :ui="{ title: 'm-0' }"
        >
            <template #body>
                <div class="flex flex-col gap-4">
                    <p v-if="preparing" role="status">Připravuji obrázek…</p>
                    <img
                        v-if="previewUrl"
                        :src="previewUrl"
                        alt="Náhled obrázku vztahu"
                        class="w-full rounded-lg"
                    />
                    <p v-if="message" role="status" class="text-sm text-muted">
                        {{ message }}
                    </p>
                    <p v-if="error" role="alert" class="text-sm text-error">
                        {{ error }}
                    </p>
                    <UButton
                        v-if="!file && !preparing"
                        variant="outline"
                        class="self-start"
                        @click="prepare"
                        >Zkusit znovu</UButton
                    >
                </div>
            </template>
            <template #footer>
                <div class="flex flex-wrap gap-2">
                    <UButton
                        icon="i-lucide-download"
                        :disabled="!file || processing"
                        @click="exportImage('download')"
                        >Stáhnout PNG</UButton
                    >
                    <UButton
                        v-if="canCopy"
                        icon="i-lucide-copy"
                        color="neutral"
                        variant="outline"
                        :disabled="processing"
                        @click="exportImage('copy')"
                        >Zkopírovat</UButton
                    >
                    <UButton
                        v-if="canShare"
                        icon="i-lucide-share-2"
                        color="neutral"
                        variant="outline"
                        :disabled="processing"
                        @click="exportImage('share')"
                        >Sdílet</UButton
                    >
                </div>
            </template>
        </UModal>
    </div>
</template>
