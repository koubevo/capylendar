<script setup lang="ts">
import { unlock } from '@/actions/App/Http/Controllers/EventSurpriseController';
import type { EventSurprise } from '@/types/EventKind';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    eventId: number;
    surprise: EventSurprise;
    classes: string;
}>();

const password = ref('');
const error = ref<string | null>(null);
const isUnlocking = ref(false);

const wrongPasswordError = 'Tohle heslo není správně. Zkus to znovu.';
const tooManyAttemptsError = 'Moc pokusů najednou. Zkus to za chvilku znovu.';
const requestError = 'Překvapení se nepodařilo odemknout. Zkus to znovu.';

function getCsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

async function submit() {
    if (isUnlocking.value || password.value.trim() === '') {
        return;
    }

    isUnlocking.value = true;
    error.value = null;

    try {
        const response = await fetch(unlock.url(props.eventId), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({ password: password.value }),
        });

        if (response.status === 422) {
            error.value = wrongPasswordError;
            return;
        }

        if (response.status === 429) {
            error.value = tooManyAttemptsError;
            return;
        }

        if (!response.ok) {
            throw new Error('Surprise unlock request failed');
        }

        password.value = '';
        router.reload({ only: ['event'] });
    } catch {
        error.value = requestError;
    } finally {
        isUnlocking.value = false;
    }
}
</script>

<template>
    <UCard :class="props.classes">
        <img
            v-if="props.surprise.is_unlocked && props.surprise.image_url"
            :src="props.surprise.image_url"
            alt="Tajný obrázek"
            class="w-full rounded-md object-cover"
        />
        <form v-else class="flex flex-col gap-y-3" @submit.prevent="submit">
            <div class="flex items-center gap-x-2">
                <UIcon name="i-lucide-gift" class="size-5" />
                <h3 class="font-bold">Tajné překvapení</h3>
            </div>
            <p class="text-sm">
                Tady se schovává obrázek. Zobrazí se po zadání hesla.
            </p>
            <p v-if="props.surprise.hint" class="text-sm">
                <span class="font-semibold">Nápověda:</span>
                {{ props.surprise.hint }}
            </p>
            <UFormField
                label="Heslo"
                name="surprise-password"
                :error="error ?? undefined"
            >
                <UInput
                    v-model="password"
                    type="password"
                    class="w-full"
                    autocomplete="off"
                />
            </UFormField>
            <UButton
                type="submit"
                color="neutral"
                icon="i-lucide-lock-open"
                class="justify-center"
                :loading="isUnlocking"
            >
                Odemknout
            </UButton>
        </form>
    </UCard>
</template>
