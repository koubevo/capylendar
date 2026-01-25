import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const isSupported = ref(false);
const isSubscribed = ref(false);
const permission = ref<NotificationPermission>('default');
const isLoading = ref(false);
const error = ref<string | null>(null);

export function usePushNotifications() {
    const page = usePage();

    const vapidPublicKey = computed(() => {
        return (page.props as { vapidPublicKey?: string }).vapidPublicKey || '';
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

    const checkSubscription = async () => {
        if (!isSupported.value) return;

        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription =
                await registration.pushManager.getSubscription();
            isSubscribed.value = !!subscription;
        } catch (e) {
            console.error('Error checking subscription:', e);
        }
    };

    const registerServiceWorker =
        async (): Promise<ServiceWorkerRegistration | null> => {
            try {
                const registration =
                    await navigator.serviceWorker.register('/sw.js');
                return registration;
            } catch (e) {
                console.error('Service worker registration failed:', e);
                error.value = 'Nepodařilo se registrovat service worker';
                return null;
            }
        };

    const requestPermission = async (): Promise<boolean> => {
        if (!isSupported.value) {
            error.value = 'Váš prohlížeč nepodporuje notifikace';
            return false;
        }

        try {
            const result = await Notification.requestPermission();
            permission.value = result;
            return result === 'granted';
        } catch (e) {
            console.error('Error requesting permission:', e);
            error.value = 'Nepodařilo se získat oprávnění pro notifikace';
            return false;
        }
    };

    const urlBase64ToUint8Array = (base64String: string): Uint8Array => {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding)
            .replace(/-/g, '+')
            .replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    };

    const subscribe = async (): Promise<boolean> => {
        if (!isSupported.value) {
            error.value = 'Váš prohlížeč nepodporuje notifikace';
            return false;
        }

        isLoading.value = true;
        error.value = null;

        try {
            // Request permission first
            const permissionGranted = await requestPermission();
            if (!permissionGranted) {
                error.value = 'Notifikace byly zamítnuty';
                isLoading.value = false;
                return false;
            }

            // Register service worker
            const registration = await registerServiceWorker();
            if (!registration) {
                isLoading.value = false;
                return false;
            }

            // Wait for service worker to be ready
            await navigator.serviceWorker.ready;

            // Subscribe to push
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(
                    vapidPublicKey.value,
                ),
            });

            // Send subscription to server
            const response = await fetch('/settings/push-subscription', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify(subscription.toJSON()),
            });

            if (!response.ok) {
                throw new Error('Failed to save subscription');
            }

            isSubscribed.value = true;
            isLoading.value = false;
            return true;
        } catch (e) {
            console.error('Error subscribing:', e);
            error.value = 'Nepodařilo se aktivovat notifikace';
            isLoading.value = false;
            return false;
        }
    };

    const unsubscribe = async (): Promise<boolean> => {
        isLoading.value = true;
        error.value = null;

        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription =
                await registration.pushManager.getSubscription();

            if (subscription) {
                // Remove from server first
                await fetch('/settings/push-subscription', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-XSRF-TOKEN': getCsrfToken(),
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ endpoint: subscription.endpoint }),
                });

                // Unsubscribe locally
                await subscription.unsubscribe();
            }

            isSubscribed.value = false;
            isLoading.value = false;
            return true;
        } catch (e) {
            console.error('Error unsubscribing:', e);
            error.value = 'Nepodařilo se deaktivovat notifikace';
            isLoading.value = false;
            return false;
        }
    };

    const getCsrfToken = (): string => {
        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        return match ? decodeURIComponent(match[1]) : '';
    };

    const init = async () => {
        checkSupport();
        checkPermission();
        if (isSupported.value) {
            await checkSubscription();
        }
    };

    onMounted(() => {
        init();
    });

    return {
        isSupported,
        isSubscribed,
        permission,
        isLoading,
        error,
        subscribe,
        unsubscribe,
        checkSubscription,
        init,
    };
}
