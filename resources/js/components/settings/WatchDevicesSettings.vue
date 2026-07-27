<script setup lang="ts">
import { destroy } from '@/actions/App/Http/Controllers/Settings/WatchDeviceController';
import { store } from '@/actions/App/Http/Controllers/Settings/WatchPairingController';
import { router, useForm } from '@inertiajs/vue3';
import { useToast } from '@nuxt/ui/composables';

export interface WatchDevice {
    id: number;
    name: string;
    last_used_at: string | null;
}

export interface PendingWatchPairing {
    id: number;
    name: string;
    approved_at: string | null;
}

defineProps<{
    watchDevices: WatchDevice[];
    pendingPairings: PendingWatchPairing[];
}>();

const toast = useToast();
const pairingForm = useForm({
    user_code: '',
});

function approvePairing(): void {
    pairingForm.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            pairingForm.reset();
            toast.add({
                title: 'Hodinky schváleny',
                description: 'Párování se během několika sekund dokončí.',
                color: 'success',
                icon: 'i-lucide-watch',
            });
        },
    });
}

function revokeDevice(device: WatchDevice): void {
    router.delete(destroy.url(device.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                title: 'Hodinky odpojeny',
                description: `${device.name} už nemají přístup k todo.`,
                color: 'success',
                icon: 'i-lucide-unplug',
            });
        },
    });
}
</script>

<template>
    <div class="grid gap-6">
        <section
            class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 ring-inset dark:bg-gray-900 dark:ring-gray-800"
        >
            <div class="flex items-start gap-4">
                <div
                    class="rounded-full bg-primary-50 p-3 text-primary-600 dark:bg-primary-950/40 dark:text-primary-400"
                >
                    <UIcon name="i-lucide-watch" class="size-6" />
                </div>
                <div class="grid min-w-0 flex-1 gap-4">
                    <div>
                        <h2
                            class="text-lg font-bold text-gray-900 dark:text-white"
                        >
                            Připojit Wear OS hodinky
                        </h2>
                        <p
                            class="mt-1 text-sm text-gray-600 dark:text-gray-400"
                        >
                            Otevři Capylendar na hodinkách a zadej sem
                            jednorázový kód z jejich displeje. Kód platí 10
                            minut.
                        </p>
                    </div>

                    <form
                        class="grid gap-3 sm:grid-cols-[1fr_auto]"
                        @submit.prevent="approvePairing"
                    >
                        <UFormField
                            label="Párovací kód"
                            :error="pairingForm.errors.user_code"
                        >
                            <UInput
                                v-model="pairingForm.user_code"
                                autocomplete="one-time-code"
                                placeholder="ABCD-2345"
                                class="w-full font-mono uppercase"
                                maxlength="12"
                            />
                        </UFormField>

                        <UButton
                            type="submit"
                            icon="i-lucide-link"
                            class="justify-center self-end"
                            :loading="pairingForm.processing"
                            :disabled="!pairingForm.user_code"
                        >
                            Připojit
                        </UButton>
                    </form>
                </div>
            </div>
        </section>

        <section
            class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 ring-inset dark:bg-gray-900 dark:ring-gray-800"
        >
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                Připojená zařízení
            </h2>

            <div v-if="pendingPairings.length" class="mt-4 grid gap-3">
                <div
                    v-for="pairing in pendingPairings"
                    :key="pairing.id"
                    class="flex items-center gap-4 rounded-xl bg-amber-50 p-4 ring-1 ring-amber-200 dark:bg-amber-950/20 dark:ring-amber-900"
                >
                    <UIcon
                        name="i-lucide-loader-circle"
                        class="size-5 shrink-0 animate-spin text-amber-600 dark:text-amber-400"
                    />
                    <div class="min-w-0">
                        <p
                            class="truncate font-semibold text-gray-900 dark:text-white"
                        >
                            {{ pairing.name }}
                        </p>
                        <p class="text-sm text-amber-700 dark:text-amber-300">
                            Schváleno, čekám na dokončení v hodinkách
                        </p>
                    </div>
                </div>
            </div>

            <div v-if="watchDevices.length" class="mt-4 grid gap-3">
                <div
                    v-for="device in watchDevices"
                    :key="device.id"
                    class="flex items-center justify-between gap-4 rounded-xl bg-gray-50 p-4 ring-1 ring-gray-200 dark:bg-gray-800/50 dark:ring-gray-700"
                >
                    <div class="min-w-0">
                        <p
                            class="truncate font-semibold text-gray-900 dark:text-white"
                        >
                            {{ device.name }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{
                                device.last_used_at
                                    ? `Naposledy použito ${device.last_used_at}`
                                    : 'Zatím nepoužito'
                            }}
                        </p>
                    </div>

                    <UButton
                        color="error"
                        variant="soft"
                        icon="i-lucide-unplug"
                        aria-label="Odpojit hodinky"
                        @click="revokeDevice(device)"
                    >
                        Odpojit
                    </UButton>
                </div>
            </div>

            <p
                v-else-if="!pendingPairings.length"
                class="mt-4 text-sm text-gray-500 dark:text-gray-400"
            >
                Zatím nejsou připojené žádné hodinky.
            </p>
        </section>
    </div>
</template>
