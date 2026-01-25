<script setup lang="ts">
import { update } from '@/actions/App/Http/Controllers/Settings/NotificationSettingsController';
import { usePushNotifications } from '@/composables/usePushNotifications';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const {
    isSupported,
    isSubscribed,
    permission,
    isLoading,
    error,
    subscribe,
    unsubscribe,
    init,
} = usePushNotifications();

const localEnabled = ref(isSubscribed.value);

watch(isSubscribed, (val) => {
    localEnabled.value = val;
});

const statusText = computed(() => {
    if (!isSupported.value) {
        return 'Váš prohlížeč nepodporuje push notifikace';
    }
    if (permission.value === 'denied') {
        return 'Notifikace jsou blokovány v nastavení prohlížeče';
    }
    if (isSubscribed.value) {
        return 'Notifikace jsou aktivní';
    }
    return 'Notifikace jsou vypnuté';
});

const statusColor = computed(() => {
    if (!isSupported.value || permission.value === 'denied') {
        return 'error';
    }
    if (isSubscribed.value) {
        return 'success';
    }
    return 'neutral';
});

async function handleToggle() {
    if (isSubscribed.value) {
        const success = await unsubscribe();
        if (success) {
            router.put(
                update.url(),
                { notifications_enabled: false },
                { preserveScroll: true },
            );
        }
    } else {
        const success = await subscribe();
        if (success) {
            router.put(
                update.url(),
                { notifications_enabled: true },
                { preserveScroll: true },
            );
        }
    }
}

init();
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <h3 class="text-base font-medium">Denní notifikace</h3>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">
                    Každý den obdržíte upozornění na eventy naplánované na další
                    den.
                </p>
            </div>
            <USwitch
                v-model="localEnabled"
                :disabled="!isSupported || permission === 'denied' || isLoading"
                :loading="isLoading"
                @update:model-value="handleToggle"
            />
        </div>

        <UAlert
            v-if="statusText"
            :color="statusColor"
            :title="statusText"
            :icon="isSubscribed ? 'i-lucide-bell-ring' : 'i-lucide-bell-off'"
        />

        <UAlert
            v-if="error"
            color="error"
            :title="error"
            icon="i-lucide-alert-circle"
        />

        <div
            v-if="isSupported && permission !== 'denied'"
            class="text-sm text-neutral-500 dark:text-neutral-400"
        >
            <p class="flex items-center gap-2">
                <UIcon name="i-lucide-info" class="h-4 w-4" />
                Notifikace budou odeslány každý den ráno (s dnešními eventy) a
                večer (s přehledem eventů na další den).
            </p>
        </div>

        <div
            v-if="permission === 'denied'"
            class="text-sm text-neutral-500 dark:text-neutral-400"
        >
            <p class="flex items-center gap-2">
                <UIcon name="i-lucide-settings" class="h-4 w-4" />
                Pro povolení notifikací změňte nastavení v prohlížeči.
            </p>
        </div>
    </div>
</template>
