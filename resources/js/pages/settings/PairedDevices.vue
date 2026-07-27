<script setup lang="ts">
import WatchDevicesSettings, {
    type PendingWatchPairing,
    type WatchDevice,
} from '@/components/settings/WatchDevicesSettings.vue';
import AuthenticatedLayout from '@/layouts/app/AuthenticatedLayout.vue';
import { Head, usePoll } from '@inertiajs/vue3';

defineProps<{
    watchDevices: WatchDevice[];
    pendingPairings: PendingWatchPairing[];
}>();

usePoll(3000, {
    only: ['watchDevices', 'pendingPairings'],
});
</script>

<template>
    <Head title="Spárovaná zařízení" />

    <AuthenticatedLayout
        :display-footer="true"
        :display-floating-action-button="false"
    >
        <section class="flex flex-col gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Spárovaná zařízení</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Připoj hodinky nebo odeber zařízení, které už nepoužíváš.
                </p>
            </div>

            <WatchDevicesSettings
                :watch-devices="watchDevices"
                :pending-pairings="pendingPairings"
            />
        </section>
    </AuthenticatedLayout>
</template>
