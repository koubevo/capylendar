import {
    destroy as destroyPushSubscription,
    store as storePushSubscription,
} from '@/actions/App/Http/Controllers/PushSubscriptionController';
import type { AppPageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const SUBSCRIPTION_RENEWAL_INTERVAL_MS = 30 * 24 * 60 * 60 * 1000;
const SUBSCRIPTION_EXPIRY_BUFFER_MS = 7 * 24 * 60 * 60 * 1000;
const LAST_RENEWED_AT_KEY = 'capylendar:push-subscription-renewed-at';

const isSupported = ref(false);
const isSubscribed = ref(false);
const permission = ref<NotificationPermission>('default');
const isLoading = ref(false);
const error = ref<string | null>(null);

let stopLifecycle: (() => void) | null = null;
let reconciliationPromise: Promise<void> | null = null;

export function usePushNotifications() {
    const page = usePage();

    const pageProps = computed(() => page.props as AppPageProps);

    const vapidPublicKey = computed(() => {
        return pageProps.value.vapidPublicKey || '';
    });

    const serverNotificationsEnabled = computed(() => {
        return pageProps.value.auth?.user?.notifications_enabled ?? false;
    });

    const isAuthenticated = computed(() => {
        return !!pageProps.value.auth?.user;
    });

    const checkSupport = () => {
        isSupported.value =
            'serviceWorker' in navigator &&
            'PushManager' in window &&
            'Notification' in window;
    };

    const checkPermission = () => {
        if ('Notification' in window) {
            permission.value = Notification.permission;
        }
    };

    const getRegistration =
        async (): Promise<ServiceWorkerRegistration | null> => {
            return (await navigator.serviceWorker.getRegistration('/')) ?? null;
        };

    const checkSubscription = async (): Promise<PushSubscription | null> => {
        if (!isSupported.value) return null;

        try {
            const registration = await getRegistration();
            if (!registration) {
                isSubscribed.value = false;
                return null;
            }

            const subscription =
                await registration.pushManager.getSubscription();
            isSubscribed.value = !!subscription;
            return subscription;
        } catch (subscriptionError) {
            console.error(
                'Error checking push subscription:',
                subscriptionError,
            );
            isSubscribed.value = false;
            return null;
        }
    };

    const registerServiceWorker =
        async (): Promise<ServiceWorkerRegistration | null> => {
            try {
                await navigator.serviceWorker.register('/sw.js');
                return navigator.serviceWorker.ready;
            } catch (registrationError) {
                console.error(
                    'Service worker registration failed:',
                    registrationError,
                );
                error.value = 'Nepodařilo se registrovat service worker';
                return null;
            }
        };

    const requestPermission = async (): Promise<boolean> => {
        if (!isSupported.value) {
            error.value = 'Váš prohlížeč nepodporuje notifikace';
            return false;
        }

        if (permission.value === 'granted') {
            return true;
        }

        try {
            const result = await Notification.requestPermission();
            permission.value = result;
            return result === 'granted';
        } catch (permissionError) {
            console.error(
                'Error requesting notification permission:',
                permissionError,
            );
            error.value = 'Nepodařilo se získat oprávnění pro notifikace';
            return false;
        }
    };

    const urlBase64ToArrayBuffer = (base64String: string): ArrayBuffer => {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding)
            .replace(/-/g, '+')
            .replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let index = 0; index < rawData.length; index++) {
            outputArray[index] = rawData.charCodeAt(index);
        }

        return outputArray.buffer;
    };

    const getCsrfToken = (): string => {
        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        return match ? decodeURIComponent(match[1]) : '';
    };

    const saveSubscription = async (
        subscription: PushSubscription,
    ): Promise<void> => {
        const storeRoute = storePushSubscription();
        const response = await fetch(storeRoute.url, {
            method: storeRoute.method,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify(subscription.toJSON()),
        });

        if (!response.ok) {
            throw new Error('Failed to save push subscription');
        }
    };

    const removeSubscriptionFromServer = async (
        endpoint: string,
    ): Promise<void> => {
        const destroyRoute = destroyPushSubscription();
        const response = await fetch(destroyRoute.url, {
            method: destroyRoute.method,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({ endpoint }),
        });

        if (!response.ok) {
            throw new Error('Failed to remove push subscription');
        }
    };

    const markSubscriptionRenewed = () => {
        try {
            window.localStorage.setItem(
                LAST_RENEWED_AT_KEY,
                Date.now().toString(),
            );
        } catch {
            // Storage may be unavailable in strict privacy modes.
        }
    };

    const isApplicationServerKeyCurrent = (
        subscription: PushSubscription,
    ): boolean => {
        const currentKey = subscription.options.applicationServerKey;
        if (!currentKey || !vapidPublicKey.value) {
            return false;
        }

        const expectedKey = new Uint8Array(
            urlBase64ToArrayBuffer(vapidPublicKey.value),
        );
        const actualKey = new Uint8Array(currentKey);

        return (
            actualKey.length === expectedKey.length &&
            actualKey.every((value, index) => value === expectedKey[index])
        );
    };

    const shouldRenewSubscription = (
        subscription: PushSubscription,
    ): boolean => {
        if (!isApplicationServerKeyCurrent(subscription)) {
            return true;
        }

        if (
            subscription.expirationTime !== null &&
            subscription.expirationTime <=
                Date.now() + SUBSCRIPTION_EXPIRY_BUFFER_MS
        ) {
            return true;
        }

        try {
            const lastRenewedAt = Number(
                window.localStorage.getItem(LAST_RENEWED_AT_KEY),
            );

            if (!Number.isFinite(lastRenewedAt) || lastRenewedAt <= 0) {
                markSubscriptionRenewed();
                return false;
            }

            return (
                Date.now() - lastRenewedAt >= SUBSCRIPTION_RENEWAL_INTERVAL_MS
            );
        } catch {
            return false;
        }
    };

    const createSubscription = async (
        registration: ServiceWorkerRegistration,
    ): Promise<PushSubscription> => {
        if (!vapidPublicKey.value) {
            throw new Error('VAPID public key is missing');
        }

        return registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToArrayBuffer(vapidPublicKey.value),
        });
    };

    const subscribe = async (forceNew = false): Promise<boolean> => {
        if (!isSupported.value) {
            error.value = 'Váš prohlížeč nepodporuje notifikace';
            return false;
        }

        isLoading.value = true;
        error.value = null;

        try {
            const permissionGranted = await requestPermission();
            if (!permissionGranted) {
                error.value = 'Notifikace byly zamítnuty';
                return false;
            }

            const registration = await registerServiceWorker();
            if (!registration) {
                return false;
            }

            const currentSubscription =
                await registration.pushManager.getSubscription();
            const renewCurrentSubscription =
                currentSubscription &&
                (forceNew || shouldRenewSubscription(currentSubscription));

            if (currentSubscription && !renewCurrentSubscription) {
                await saveSubscription(currentSubscription);
                isSubscribed.value = true;
                return true;
            }

            const previousEndpoint = currentSubscription?.endpoint;
            if (currentSubscription) {
                await currentSubscription.unsubscribe();
            }

            const subscription = await createSubscription(registration);
            await saveSubscription(subscription);
            markSubscriptionRenewed();

            if (
                previousEndpoint &&
                previousEndpoint !== subscription.endpoint
            ) {
                try {
                    await removeSubscriptionFromServer(previousEndpoint);
                } catch (cleanupError) {
                    console.warn(
                        'Failed to remove stale push subscription:',
                        cleanupError,
                    );
                }
            }

            isSubscribed.value = true;
            return true;
        } catch (subscriptionError) {
            console.error('Error subscribing to push:', subscriptionError);
            error.value = 'Nepodařilo se aktivovat notifikace';
            isSubscribed.value = false;
            return false;
        } finally {
            isLoading.value = false;
        }
    };

    const unsubscribe = async (): Promise<boolean> => {
        isLoading.value = true;
        error.value = null;

        try {
            const registration = await getRegistration();
            if (!registration) {
                isSubscribed.value = false;
                return true;
            }

            const subscription =
                await registration.pushManager.getSubscription();

            if (subscription) {
                await removeSubscriptionFromServer(subscription.endpoint);
                await subscription.unsubscribe();
            }

            try {
                window.localStorage.removeItem(LAST_RENEWED_AT_KEY);
            } catch {
                // Storage may be unavailable in strict privacy modes.
            }

            isSubscribed.value = false;
            return true;
        } catch (unsubscribeError) {
            console.error('Error unsubscribing from push:', unsubscribeError);
            error.value = 'Nepodařilo se deaktivovat notifikace';
            return false;
        } finally {
            isLoading.value = false;
        }
    };

    const reconcileSubscription = async (): Promise<void> => {
        checkSupport();
        checkPermission();

        if (!isSupported.value) {
            return;
        }

        const subscription = await checkSubscription();

        if (!isAuthenticated.value) {
            return;
        }

        if (!serverNotificationsEnabled.value) {
            if (subscription) {
                await unsubscribe();
            }

            return;
        }

        if (permission.value === 'granted') {
            await subscribe();
        }
    };

    const init = async (): Promise<void> => {
        if (reconciliationPromise) {
            return reconciliationPromise;
        }

        reconciliationPromise = reconcileSubscription().finally(() => {
            reconciliationPromise = null;
        });

        return reconciliationPromise;
    };

    const startLifecycle = () => {
        if (stopLifecycle) {
            return;
        }

        const stopWatch = watch(
            [serverNotificationsEnabled, isAuthenticated],
            () => {
                void init();
            },
            { immediate: true },
        );

        const reconcileWhenVisible = () => {
            if (document.visibilityState === 'visible') {
                void init();
            }
        };
        const reconcileWhenOnline = () => {
            void init();
        };

        document.addEventListener('visibilitychange', reconcileWhenVisible);
        window.addEventListener('online', reconcileWhenOnline);
        window.addEventListener('pageshow', reconcileWhenOnline);

        const cleanup = () => {
            stopWatch();
            document.removeEventListener(
                'visibilitychange',
                reconcileWhenVisible,
            );
            window.removeEventListener('online', reconcileWhenOnline);
            window.removeEventListener('pageshow', reconcileWhenOnline);
            stopLifecycle = null;
        };

        stopLifecycle = cleanup;

        onBeforeUnmount(() => {
            if (stopLifecycle === cleanup) {
                cleanup();
            }
        });
    };

    return {
        isSupported,
        isSubscribed,
        permission,
        isLoading,
        error,
        subscribe,
        unsubscribe,
        checkSubscription,
        serverNotificationsEnabled,
        isAuthenticated,
        init,
        startLifecycle,
    };
}
