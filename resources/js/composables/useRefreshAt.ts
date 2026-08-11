import { onUnmounted, toValue, watch, type MaybeRefOrGetter } from 'vue';

export function useRefreshAt(
    refreshAt: MaybeRefOrGetter<string | null | undefined>,
    refresh: () => void,
) {
    let timer: ReturnType<typeof setTimeout> | undefined;

    const stopWatching = watch(
        () => toValue(refreshAt),
        (value) => {
            if (timer) clearTimeout(timer);
            if (!value) return;

            const delay = new Date(value).getTime() - Date.now();
            timer = setTimeout(refresh, Math.max(0, delay));
        },
        { immediate: true },
    );

    onUnmounted(() => {
        stopWatching();
        if (timer) clearTimeout(timer);
    });
}
