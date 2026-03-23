import { router, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';

export function useUnsavedChangesWarning(form: { isDirty: boolean; processing?: boolean; wasSuccessful?: boolean }) {
    const beforeUnloadHandler = (e: BeforeUnloadEvent) => {
        if (form.isDirty && !form.processing && !form.wasSuccessful) {
            e.preventDefault();
            e.returnValue = '';
        }
    };

    const popstateHandler = (e: PopStateEvent) => {
        if (form.isDirty && !form.processing && !form.wasSuccessful) {
            if (!window.confirm('Máte neuložené změny. Opravdu chcete odejít?')) {
                // If user wants to stay, we must prevent Inertia from handling the popstate
                e.stopImmediatePropagation();
                e.preventDefault();

                // The browser's URL already changed. Revert it back to the current form URL.
                const page = usePage();
                history.pushState(null, '', page.url);
            }
        }
    };

    let removeBeforeEventListener: (() => void) | null = null;

    onMounted(() => {
        window.addEventListener('beforeunload', beforeUnloadHandler);
        window.addEventListener('popstate', popstateHandler, { capture: true });

        removeBeforeEventListener = router.on('before', (event) => {
            // Ignore form submissions (POST, PUT, DELETE, PATCH)
            // as well as navigation resulting directly from a successful submission or while processing
            if (
                event.detail.visit.method !== 'get' || 
                form.processing || 
                form.wasSuccessful
            ) {
                return;
            }

            if (form.isDirty) {
                if (!window.confirm('Máte neuložené změny. Opravdu chcete odejít?')) {
                    event.preventDefault();
                }
            }
        });
    });

    onUnmounted(() => {
        window.removeEventListener('beforeunload', beforeUnloadHandler);
        window.removeEventListener('popstate', popstateHandler, { capture: true });
        if (removeBeforeEventListener) {
            removeBeforeEventListener();
        }
    });
}
